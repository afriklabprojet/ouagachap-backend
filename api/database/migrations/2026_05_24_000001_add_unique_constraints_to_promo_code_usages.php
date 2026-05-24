<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute deux contraintes d'unicité sur promo_code_usages :
     *
     * 1. UNIQUE(promo_code_id, order_id)
     *    — une commande ne peut recevoir qu'un seul code promo.
     *    — remplace la vérification applicative dans PromoCodeController::apply()
     *      et protège contre les race conditions (requêtes parallèles).
     *
     * 2. INDEX(promo_code_id, user_id) existait déjà (index simple) — conservé.
     *    On ne met PAS UNIQUE(promo_code_id, user_id) car max_uses_per_user peut
     *    être > 1, ce qui nécessite plusieurs lignes pour le même couple.
     */
    public function up(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            // Une commande ne peut avoir qu'un seul code promo appliqué
            $table->unique(['promo_code_id', 'order_id'], 'unique_promo_per_order');
        });
    }

    public function down(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->dropUnique('unique_promo_per_order');
        });
    }
};
