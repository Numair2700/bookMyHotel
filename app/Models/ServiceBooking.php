<?php

namespace App\Models;

use Database\Factories\ServiceBookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceBooking extends Model
{
    /** @use HasFactory<ServiceBookingFactory> */
    use HasFactory;

    protected $fillable = ['reservation_id', 'service_id', 'service_date', 'quantity', 'unit_price'];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
