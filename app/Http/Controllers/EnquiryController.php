<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;

class EnquiryController extends Controller
{
    /** FR11 — submit an enquiry through the contact form. */
    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        Enquiry::create([
            'name' => (string) $request->input('name'),
            'email' => (string) $request->input('email'),
            'subject' => (string) $request->input('subject'),
            'message' => (string) $request->input('message'),
            'status' => 'new',
        ]);

        return back()->with('success', 'Thanks for getting in touch. We will reply by email shortly.');
    }
}
