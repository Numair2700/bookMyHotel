<?php

namespace App\Http\Requests;

use App\Modules\Availability\Data\SearchCriteria;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Search is public.
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // Only enforce max >= min when a minimum was actually supplied,
        // otherwise `gte:min_price` fails against an absent field.
        $maxPrice = ['nullable', 'numeric', 'min:0'];
        if ($this->filled('min_price')) {
            $maxPrice[] = 'gte:min_price';
        }

        return [
            'destination' => ['nullable', 'string', 'max:100'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:20'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => $maxPrice,
            'room_type' => ['nullable', 'string', 'max:100'],
            'sustainable_only' => ['nullable', 'boolean'],
            'has_workspace' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Map the validated request into the module's typed criteria object.
     */
    public function criteria(): SearchCriteria
    {
        return new SearchCriteria(
            checkIn: $this->date('check_in')->toDateString(),
            checkOut: $this->date('check_out')->toDateString(),
            destination: $this->filled('destination') ? (string) $this->input('destination') : null,
            guests: $this->filled('guests') ? $this->integer('guests') : null,
            minPrice: $this->filled('min_price') ? (float) $this->input('min_price') : null,
            maxPrice: $this->filled('max_price') ? (float) $this->input('max_price') : null,
            roomType: $this->filled('room_type') ? (string) $this->input('room_type') : null,
            sustainableOnly: $this->boolean('sustainable_only'),
            requiresWorkspace: $this->boolean('has_workspace'),
        );
    }
}
