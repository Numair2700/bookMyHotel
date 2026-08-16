<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gated by role:manager,admin on the route.
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'date' => ['required', 'date'],
            'rooms_available' => ['required', 'integer', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
