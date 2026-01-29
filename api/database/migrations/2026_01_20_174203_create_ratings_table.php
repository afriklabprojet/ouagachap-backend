<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('rater_id')->constrained('users')->onDelete('cascade'); // Qui note
            $table->foreignId('rated_id')->constrained('users')->onDelete('cascade'); // Qui est noté
            $table->enum('type', ['client_to_courier', 'courier_to_client']);
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('comment')->nullable();
            $table->json('tags')->nullable(); // ["rapide", "professionnel", "aimable"]
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['order_id', 'type']); // Une seule note par type par commande
            $table->index(['rated_id', 'type']);
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
