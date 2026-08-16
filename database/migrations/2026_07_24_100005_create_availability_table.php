<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained('room_types')->cascadeOnDelete();
            $table->date('date');
            $table->integer('rooms_available');
            $table->decimal('rate', 10, 2);
            $table->timestamps();

            // Search and booking load: one row per room type per date.
            $table->unique(['room_type_id', 'date']);
        });

        // Backstop against overselling if application logic ever fails (NFR5).
        DB::statement('ALTER TABLE availability ADD CONSTRAINT chk_rooms_available CHECK (rooms_available >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
