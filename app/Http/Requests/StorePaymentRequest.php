<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Only a payment method and a gateway token are accepted — never card
     * numbers, which are tokenised client-side (security checklist).
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'in:card,paypal,bank_transfer'],
            'token' => ['required', 'string', 'max:255'],
        ];
    }
}
