<?php

namespace App\Models;

use Database\Factories\CancellationPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicy extends Model
{
    /** @use HasFactory<CancellationPolicyFactory> */
    use HasFactory;

    protected $fillable = ['hotel_id', 'name', 'free_cancellation_hours', 'penalty_percentage'];

    protected function casts(): array
    {
        return [
            'free_cancellation_hours' => 'integer',
            'penalty_percentage' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Hotel, $this> */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}
