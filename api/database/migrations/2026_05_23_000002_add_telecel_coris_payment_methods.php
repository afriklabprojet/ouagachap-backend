<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('orange_money','moov_money','telecel_money','coris_money','cash','wave','mtn_money','djamo') NOT NULL");

        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN method ENUM('orange_money','moov_money','telecel_money','coris_money','cash','bank_transfer','wave','mtn_money','djamo') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('orange_money','moov_money','cash','wave','mtn_money','djamo') NOT NULL");

        DB::statement("ALTER TABLE wallet_transactions MODIFY COLUMN method ENUM('orange_money','moov_money','cash','bank_transfer','wave','mtn_money','djamo') NOT NULL");
    }
};
