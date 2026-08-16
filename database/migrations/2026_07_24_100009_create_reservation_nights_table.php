<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_nights', function (Blueprint $table) {
            $table->id();
            // Cascade: nights are child records of a reservation.
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            // Restrict: a room type with booked nights must not be deleted.
            $table->foreignId('room_type_id')->constrained('room_types')->restrictOnDelete();
            $table->date('stay_date');
            $table->decimal('rate', 10, 2); // rate charged that night, from availability
            $table->timestamps();

            // One row per room type per night of the stay.
            $table->unique(['reservation_id', 'room_type_id', 'stay_date']);
            // Drives all analytics (room nights, revenue, ADR) filtered by date.
            $table->index('stay_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_nights');
    }
};
