<?php

namespace App\Http\Requests;

use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'promotion_code' => ['nullable', 'string', 'max:50'],
            'redeem_points' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Reject a party larger than the room type can hold.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roomType = RoomType::find((int) $this->input('room_type_id'));

            if ($roomType !== null && (int) $this->input('guests') > $roomType->max_occupancy) {
                $validator->errors()->add('guests', 'This room type accommodates at most '.$roomType->max_occupancy.' guests.');
            }
        });
    }

    /**
     * Validated booking input as typed scalars, dates normalised to Y-m-d.
     *
     * @return array{room_type_id: int, check_in: string, check_out: string, guests: int, promotion_code: string|null, redeem_points: int}
     */
    public function bookingData(): array
    {
        return [
            'room_type_id' => (int) $this->input('room_type_id'),
            'check_in' => Carbon::parse((string) $this->input('check_in'))->toDateString(),
            'check_out' => Carbon::parse((string) $this->input('check_out'))->toDateString(),
            'guests' => (int) $this->input('guests'),
            'promotion_code' => $this->filled('promotion_code') ? (string) $this->input('promotion_code') : null,
            'redeem_points' => (int) $this->input('redeem_points', 0),
        ];
    }
}
