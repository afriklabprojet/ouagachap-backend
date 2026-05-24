<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute des index de performance pour MySQL en production
     */
    public function up(): void
    {
        // Vérifier si on est sur MySQL
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Index sur orders
        Schema::table('orders', function (Blueprint $table) {
            // Index pour les statistiques par date
            $table->index(['status', 'created_at'], 'idx_orders_status_date');

            // Index pour les revenus
            $table->index(['status', 'delivered_at'], 'idx_orders_delivered');
        });

        // Index sur users pour la recherche des coursiers
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role'], 'idx_users_role');
        });

        // Index sur courier_profiles pour la recherche géographique
        if (Schema::hasTable('courier_profiles')) {
            Schema::table('courier_profiles', function (Blueprint $table) {
                $table->index(['is_available', 'current_latitude', 'current_longitude'], 'idx_courier_geo');
            });
        }

        // Index sur payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_payments_status_date');
        });

        // Index sur withdrawals
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'idx_withdrawals_status_date');
        });
    }

    /**
     * Supprime les index
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_status_date');
            $table->dropIndex('idx_orders_delivered');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
        });

        if (Schema::hasTable('courier_profiles')) {
            Schema::table('courier_profiles', function (Blueprint $table) {
                $table->dropIndex('idx_courier_geo');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status_date');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropIndex('idx_withdrawals_status_date');
        });
    }
};
