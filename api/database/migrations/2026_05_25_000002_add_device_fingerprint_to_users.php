<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('device_fingerprint', 64)
                ->nullable()
                ->after('firebase_uid')
                ->index('idx_users_device_fingerprint');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_device_fingerprint');
            $table->dropColumn('device_fingerprint');
        });
    }
};
