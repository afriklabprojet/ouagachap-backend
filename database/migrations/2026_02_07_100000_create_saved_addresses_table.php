<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Address details
            $table->string('label'); // "Maison", "Bureau", "Chez Maman", etc.
            $table->string('address'); // Full address text
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            
            // Contact info (optional)
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->text('instructions')->nullable(); // "Portail bleu", "2ème étage", etc.
            
            // Flags
            $table->boolean('is_default')->default(false);
            $table->enum('type', ['home', 'work', 'other'])->default('other');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'is_default']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_addresses');
    }
};
