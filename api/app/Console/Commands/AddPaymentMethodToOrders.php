<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration one-time : ajouter payment_method à la table orders.
 * Anciennement accessible via curl https://ouagachap.com/scripts/add_payment_method.php
 * Usage : php artisan orders:add-payment-method
 */
class AddPaymentMethodToOrders extends Command
{
    protected $signature = 'orders:add-payment-method';
    protected $description = 'Ajouter la colonne payment_method à la table orders';

    public function handle(): int
    {
        if (Schema::hasColumn('orders', 'payment_method')) {
            $this->info("✅ Colonne 'payment_method' existe déjà dans la table 'orders'.");
            return Command::SUCCESS;
        }

        DB::statement("ALTER TABLE `orders` ADD COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash' AFTER `package_size`");
        $this->info("✅ Colonne 'payment_method' ajoutée avec succès à la table 'orders'.");

        return Command::SUCCESS;
    }
}
