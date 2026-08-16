<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerPromotionTest extends TestCase
{
    use RefreshDatabase;

    /** FR14 — a manager can create and withdraw a promotion. */
    public function test_a_manager_can_create_and_withdraw_a_promotion(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $hotel = Hotel::factory()->create();

        $this->actingAs($manager)->post(route('manager.promotions.store'), [
            'hotel_id' => $hotel->id,
            'code' => 'WELCOME15',
            'description' => '15% off your first stay',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'valid_from' => now()->toDateString(),
            'valid_to' => now()->addMonths(2)->toDateString(),
            'active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('promotions', ['code' => 'WELCOME15', 'hotel_id' => $hotel->id]);

        $promotion = Promotion::where('code', 'WELCOME15')->firstOrFail();
        $this->actingAs($manager)->delete(route('manager.promotions.destroy', $promotion))->assertRedirect();
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_a_guest_cannot_manage_promotions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']))
            ->get(route('manager.promotions.index'))->assertForbidden();
    }

    /** An admin has full access, so may also manage promotions. */
    public function test_an_admin_can_also_manage_promotions(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('manager.promotions.index'))->assertOk();
    }
}
