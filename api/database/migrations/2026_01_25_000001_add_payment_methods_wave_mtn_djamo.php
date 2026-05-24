<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajouter les méthodes de paiement Wave, MTN et Djamo aux tables payments et wallet_transactions.
     */
    public function up(): void
    {
        // MySQL seulement : SQLite ne supporte pas MODIFY COLUMN
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Modifier l'enum 'method' de la table payments
        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('orange_money', 'moov_money', 'cash', 'wave', 'mtn_money', 'djamo') NOT NULL");

        // Modifier l'enum 'method' de la table wallet_transactions
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN method ENUM('orange_money', 'moov_money', 'cash', 'bank_transfer', 'wave', 'mtn_money', 'djamo') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('orange_money', 'moov_money', 'cash') NOT NULL");
        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN method ENUM('orange_money', 'moov_money', 'cash', 'bank_transfer') NOT NULL");
    }
};
