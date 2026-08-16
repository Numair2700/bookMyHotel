<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chain_id')->constrained('hotel_chains')->cascadeOnDelete();
            $table->string('name');
            $table->string('city');
            $table->string('country');
            $table->enum('region', ['asia', 'europe']);
            $table->string('address');
            $table->text('description');
            $table->tinyInteger('star_rating');
            $table->integer('wifi_speed_mbps')->nullable();
            $table->boolean('has_workspace')->default(false);
            $table->boolean('sustainability_certified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
