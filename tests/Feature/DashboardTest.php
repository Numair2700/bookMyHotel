<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_a_guest_is_routed_to_their_bookings()
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']));

        $this->get(route('dashboard'))
            ->assertRedirect(route('reservations.index'));
    }

    public function test_a_manager_is_routed_to_the_manager_area()
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']));

        $this->get(route('dashboard'))
            ->assertRedirect(route('manager.promotions.index'));
    }

    public function test_an_admin_is_routed_to_the_analytics_dashboard()
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('dashboard'))
            ->assertRedirect(route('admin.analytics.index'));
    }
}
