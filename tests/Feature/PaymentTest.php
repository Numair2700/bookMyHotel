<?php

namespace Tests\Feature;

use App\Mail\ReservationConfirmedMail;
use App\Models\Reservation;
use App\Models\RewardPointsLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /** FR12 — a successful payment confirms the reservation and is recorded. */
    public function test_a_successful_payment_confirms_the_reservation(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->for($user)->pending()->create(['total_amount' => 400]);

        $this->actingAs($user)
            ->post(route('reservations.pay', $reservation), ['method' => 'card', 'token' => 'tok_ok'])
            ->assertRedirect(route('reservations.show', $reservation));

        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'status' => 'succeeded',
            'method' => 'card',
        ]);
    }

    /** FR13 — reward points are earned only on sustainability-certified hotels. */
    public function test_reward_points_are_awarded_only_for_sustainable_bookings(): void
    {
        $green = User::factory()->create();
        $sustainable = Reservation::factory()->for($green)->pending()->sustainable()->create(['total_amount' => 400]);

        $this->actingAs($green)
            ->post(route('reservations.pay', $sustainable), ['method' => 'card', 'token' => 'tok_ok']);

        // 400 / 10 = 40 points.
        $this->assertSame(40, (int) $green->fresh()->reward_points_balance);

        $plain = User::factory()->create();
        $notSustainable = Reservation::factory()->for($plain)->pending()->create([
            'total_amount' => 400, 'is_sustainable' => false,
        ]);

        $this->actingAs($plain)
            ->post(route('reservations.pay', $notSustainable), ['method' => 'card', 'token' => 'tok_ok']);

        $this->assertSame(0, (int) $plain->fresh()->reward_points_balance);
        $this->assertDatabaseCount('reward_points_ledger', 1); // only the sustainable one
    }

    /** FR13 — the balance always equals the sum of the ledger. */
    public function test_reward_ledger_sum_equals_the_user_balance(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->for($user)->pending()->sustainable()->create(['total_amount' => 655]);

        $this->actingAs($user)
            ->post(route('reservations.pay', $reservation), ['method' => 'card', 'token' => 'tok_ok']);

        $balance = (int) $user->fresh()->reward_points_balance;
        $ledger = (int) RewardPointsLedger::where('user_id', $user->id)->sum('points');

        $this->assertSame(65, $balance); // floor(655 / 10)
        $this->assertSame($ledger, $balance);
    }

    /** A declined charge keeps the reservation pending and records the failure. */
    public function test_a_declined_payment_leaves_the_reservation_pending(): void
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->for($user)->pending()->create(['total_amount' => 400]);

        $this->actingAs($user)
            ->post(route('reservations.pay', $reservation), ['method' => 'card', 'token' => 'tok_fail'])
            ->assertSessionHasErrors('payment');

        $this->assertSame('pending', $reservation->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservation->id,
            'status' => 'failed',
        ]);
    }

    /** §4.2 / NFR3 — the confirmation email is queued, not sent inline. */
    public function test_a_successful_payment_queues_a_confirmation_email(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $reservation = Reservation::factory()->for($user)->pending()->create();

        $this->actingAs($user)
            ->post(route('reservations.pay', $reservation), ['method' => 'card', 'token' => 'tok_ok']);

        Mail::assertQueued(ReservationConfirmedMail::class);
    }

    public function test_a_stranger_cannot_pay_for_someone_elses_reservation(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $reservation = Reservation::factory()->for($owner)->pending()->create();

        $this->actingAs($stranger)
            ->post(route('reservations.pay', $reservation), ['method' => 'card', 'token' => 'tok_ok'])
            ->assertForbidden();

        $this->assertSame('pending', $reservation->fresh()->status);
    }
}
