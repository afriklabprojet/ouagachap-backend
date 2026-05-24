<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('heading')->nullable();
            $table->float('speed')->nullable();
            $table->float('accuracy')->nullable();
            $table->timestamp('recorded_at');

            // Partial index: time-range queries for a given order
            $table->index(['order_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_location_history');
    }
};
