<?php

namespace App\Models;

use Database\Factories\HotelChainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotelChain extends Model
{
    /** @use HasFactory<HotelChainFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    /** @return HasMany<Hotel, $this> */
    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class, 'chain_id');
    }
}
