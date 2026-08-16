<?php

namespace App\Models;

use Database\Factories\ReservationNightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reservation_id
 * @property int $room_type_id
 * @property Carbon $stay_date
 * @property numeric-string $rate
 */
class ReservationNight extends Model
{
    /** @use HasFactory<ReservationNightFactory> */
    use HasFactory;

    protected $fillable = ['reservation_id', 'room_type_id', 'stay_date', 'rate'];

    protected function casts(): array
    {
        return [
            'stay_date' => 'date',
            'rate' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** @return BelongsTo<RoomType, $this> */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
