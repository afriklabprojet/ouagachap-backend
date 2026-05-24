<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL seulement : SQLite ne supporte pas MODIFY COLUMN
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','assigned','accepted','picking_up','picked_up','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE order_status_histories MODIFY COLUMN status ENUM('pending','assigned','accepted','picking_up','picked_up','in_transit','delivered','cancelled') NOT NULL");
        DB::statement("ALTER TABLE order_status_histories MODIFY COLUMN previous_status ENUM('pending','assigned','accepted','picking_up','picked_up','in_transit','delivered','cancelled') DEFAULT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','assigned','picked_up','delivered','cancelled') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE order_status_histories MODIFY COLUMN status ENUM('pending','assigned','picked_up','delivered','cancelled') NOT NULL");
        DB::statement("ALTER TABLE order_status_histories MODIFY COLUMN previous_status ENUM('pending','assigned','picked_up','delivered','cancelled') DEFAULT NULL");
    }
};
