<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime les colonnes de localisation legacy `latitude` et `longitude` de la
 * table `users`. La position live du coursier est stockée dans
 * `current_latitude` / `current_longitude` (mis à jour par updateLocation()).
 *
 * Ces colonnes legacy n'ont jamais été alimentées en production ; leur présence
 * créait une ambiguïté pour tout développeur lisant le modèle User.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('current_longitude');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }
};
