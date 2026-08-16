<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // Restrict: payments are financial records.
            $table->foreignId('reservation_id')->constrained('reservations')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['card', 'paypal', 'bank_transfer']);
            $table->string('gateway_reference')->nullable(); // token/ref only, never card data
            $table->enum('status', ['pending', 'succeeded', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
