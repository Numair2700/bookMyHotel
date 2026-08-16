<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    /** FR11 — a guest can submit an enquiry through the contact form. */
    public function test_a_visitor_can_submit_an_enquiry(): void
    {
        $this->post(route('enquiries.store'), [
            'name' => 'Jamie Guest',
            'email' => 'jamie@example.com',
            'subject' => 'Group booking',
            'message' => 'Do you offer rates for a party of ten?',
        ])->assertRedirect();

        $this->assertDatabaseHas('enquiries', [
            'email' => 'jamie@example.com',
            'subject' => 'Group booking',
            'status' => 'new',
        ]);
    }

    public function test_an_enquiry_requires_a_valid_email_and_message(): void
    {
        $this->post(route('enquiries.store'), [
            'name' => 'Jamie Guest',
            'email' => 'not-an-email',
            'subject' => 'Hi',
        ])->assertSessionHasErrors(['email', 'message']);

        $this->assertDatabaseCount('enquiries', 0);
    }
}
