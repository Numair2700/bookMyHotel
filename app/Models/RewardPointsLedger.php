<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardPointsLedger extends Model
{
    // Eloquent would otherwise look for "reward_points_ledgers".
    protected $table = 'reward_points_ledger';

    protected $fillable = ['user_id', 'reservation_id', 'points', 'reason'];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
