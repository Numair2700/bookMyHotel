<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for creating or editing a hotel (FR16). Authorisation is handled
 * by the role:admin middleware on the route.
 */
class HotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'chain_id' => ['required', 'integer', 'exists:hotel_chains,id'],
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'region' => ['required', 'in:asia,europe'],
            'address' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'star_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'wifi_speed_mbps' => ['nullable', 'integer', 'min:0'],
            'has_workspace' => ['boolean'],
            'sustainability_certified' => ['boolean'],
        ];
    }
}
