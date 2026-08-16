<?php

namespace App\Models;

use Database\Factories\AvailabilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_type_id
 * @property Carbon $date
 * @property int $rooms_available
 * @property numeric-string $rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Availability extends Model
{
    /** @use HasFactory<AvailabilityFactory> */
    use HasFactory;

    // Table is singular; Eloquent would otherwise look for "availabilities".
    protected $table = 'availability';

    protected $fillable = ['room_type_id', 'date', 'rooms_available', 'rate'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'rooms_available' => 'integer',
            'rate' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<RoomType, $this> */
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
