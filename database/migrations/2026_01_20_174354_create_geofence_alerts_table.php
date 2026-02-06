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
        Schema::create('geofence_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('courier_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['enter', 'exit', 'proximity_pickup', 'proximity_delivery', 'out_of_bounds']);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('distance_meters', 10, 2)->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['order_id', 'type']);
            $table->index(['courier_id', 'created_at']);
        });

        // Ajouter les champs de tarification dynamique aux zones
        Schema::table('zones', function (Blueprint $table) {
            $table->decimal('surge_multiplier', 3, 2)->default(1.00)->after('is_active');
            $table->boolean('surge_active')->default(false)->after('surge_multiplier');
            $table->json('surge_schedule')->nullable()->after('surge_active'); // Heures de pointe
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofence_alerts');
        
        Schema::table('zones', function (Blueprint $table) {
            $table->dropColumn(['surge_multiplier', 'surge_active', 'surge_schedule']);
        });
    }
};
