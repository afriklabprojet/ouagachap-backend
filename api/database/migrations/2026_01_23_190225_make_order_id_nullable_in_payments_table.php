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
        // SQLite ne supporte pas directement la modification de colonnes
        // On doit recréer la table ou utiliser une approche différente
        
        // Pour SQLite en dev, on va simplement ajouter une colonne de type
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_type')->default('order')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });
    }
};
