<?php

namespace App\Models;

use Database\Factories\HotelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    /** @use HasFactory<HotelFactory> */
    use HasFactory;

    protected $fillable = [
        'chain_id', 'name', 'city', 'country', 'region', 'address',
        'description', 'star_rating', 'wifi_speed_mbps',
        'has_workspace', 'sustainability_certified',
    ];

    protected function casts(): array
    {
        return [
            'star_rating' => 'integer',
            'wifi_speed_mbps' => 'integer',
            'has_workspace' => 'boolean',
            'sustainability_certified' => 'boolean',
        ];
    }

    /** @return BelongsTo<HotelChain, $this> */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(HotelChain::class, 'chain_id');
    }

    /** @return HasMany<RoomType, $this> */
    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<Promotion, $this> */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /** @return HasMany<CancellationPolicy, $this> */
    public function cancellationPolicies(): HasMany
    {
        return $this->hasMany(CancellationPolicy::class);
    }

    /** @return HasMany<Reservation, $this> */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
