<?php

namespace App\Models;

use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'hotel_id', 'name', 'description', 'max_occupancy', 'base_rate', 'total_rooms',
    ];

    protected function casts(): array
    {
        return [
            'max_occupancy' => 'integer',
            'base_rate' => 'decimal:2',
            'total_rooms' => 'integer',
        ];
    }

    /** @return BelongsTo<Hotel, $this> */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /** @return HasMany<Room, $this> */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /** @return HasMany<Availability, $this> */
    public function availability(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    /** @return HasMany<ReservationNight, $this> */
    public function reservationNights(): HasMany
    {
        return $this->hasMany(ReservationNight::class);
    }
}
