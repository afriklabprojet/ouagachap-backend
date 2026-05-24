<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('battery_level')->unsigned()->nullable()->after('location_updated_at')
                ->comment('Niveau de batterie du téléphone du coursier (0-100), null si inconnu');
            $table->timestamp('battery_updated_at')->nullable()->after('battery_level')
                ->comment('Dernière mise à jour du niveau de batterie');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['battery_level', 'battery_updated_at']);
        });
    }
};
