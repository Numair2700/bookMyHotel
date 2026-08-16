<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            // Restrict: reservations are financial records and must not be
            // deleted by removing the parent user or hotel.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->restrictOnDelete();
            $table->string('reference')->unique(); // BMH-XXXXXXXX
            $table->date('check_in');
            $table->date('check_out');
            $table->tinyInteger('guests');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('promotion_id')->nullable()->constrained('promotions')->nullOnDelete();
            $table->boolean('is_sustainable')->default(false);
            $table->timestamps();

            // reservations(user_id) index is provided by the foreign key above.
            $table->index(['check_in', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
