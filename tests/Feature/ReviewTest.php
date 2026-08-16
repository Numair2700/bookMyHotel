<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /** FR10 — a review is only allowed on a completed reservation the user owns. */
    public function test_a_review_requires_a_completed_reservation_owned_by_the_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $completed = Reservation::factory()->for($owner)->completed()->create();

        // A stranger cannot review someone else's stay.
        $this->actingAs($stranger)
            ->post(route('reservations.review', $completed), ['rating' => 8, 'comment' => 'Lovely'])
            ->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);

        // The owner cannot review a stay that is not completed.
        $upcoming = Reservation::factory()->for($owner)->create(['status' => 'confirmed']);
        $this->actingAs($owner)
            ->post(route('reservations.review', $upcoming), ['rating' => 8, 'comment' => 'Too soon'])
            ->assertSessionHasErrors('review');
        $this->assertDatabaseCount('reviews', 0);

        // The owner can review their completed stay; it starts unapproved.
        $this->actingAs($owner)
            ->post(route('reservations.review', $completed), ['rating' => 8, 'comment' => 'Great stay'])
            ->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'reservation_id' => $completed->id,
            'rating' => 8,
            'approved' => false,
        ]);
    }

    /** FR10 — a reservation can be reviewed only once. */
    public function test_a_reservation_can_only_be_reviewed_once(): void
    {
        $owner = User::factory()->create();
        $completed = Reservation::factory()->for($owner)->completed()->create();

        $this->actingAs($owner)
            ->post(route('reservations.review', $completed), ['rating' => 9, 'comment' => 'First'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('reservations.review', $completed), ['rating' => 3, 'comment' => 'Second'])
            ->assertSessionHasErrors('review');

        $this->assertDatabaseCount('reviews', 1);
    }
}
