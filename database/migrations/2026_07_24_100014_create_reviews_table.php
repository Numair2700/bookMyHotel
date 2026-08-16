<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Unique: one review per reservation, and only real guests can review.
            $table->foreignId('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->tinyInteger('rating'); // 1 to 10
            $table->text('comment');
            $table->boolean('approved')->default(false);
            $table->timestamps();
            // reviews(hotel_id) index is provided by the foreign key above.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
