<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->addIndexIfNotExists('orders', '`status`, `courier_id`', 'idx_orders_status_courier');
        $this->addIndexIfNotExists('orders', '`client_id`, `status`', 'idx_orders_client_status');
        $this->addIndexIfNotExists('orders', '`created_at`', 'idx_orders_created_at');
        $this->addIndexIfNotExists('orders', '`zone_id`', 'idx_orders_zone');
        $this->addIndexIfNotExists('otp_codes', '`phone`, `is_used`, `expires_at`', 'idx_otp_validation');

        if (Schema::hasTable('order_status_histories')) {
            $this->addIndexIfNotExists('order_status_histories', '`order_id`, `created_at`', 'idx_status_history_order');
        }

        if (Schema::hasTable('locations')) {
            $this->addIndexIfNotExists('locations', '`user_id`, `created_at`', 'idx_locations_user_time');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->dropIndexIfExists('orders', 'idx_orders_status_courier');
        $this->dropIndexIfExists('orders', 'idx_orders_client_status');
        $this->dropIndexIfExists('orders', 'idx_orders_created_at');
        $this->dropIndexIfExists('orders', 'idx_orders_zone');
        $this->dropIndexIfExists('otp_codes', 'idx_otp_validation');

        if (Schema::hasTable('order_status_histories')) {
            $this->dropIndexIfExists('order_status_histories', 'idx_status_history_order');
        }

        if (Schema::hasTable('locations')) {
            $this->dropIndexIfExists('locations', 'idx_locations_user_time');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return count(DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        )) > 0;
    }

    private function addIndexIfNotExists(string $table, string $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            DB::statement("CREATE INDEX `{$indexName}` ON `{$table}` ({$columns})");
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("DROP INDEX `{$indexName}` ON `{$table}`");
        }
    }
};
