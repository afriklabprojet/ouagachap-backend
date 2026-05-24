<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_contact_name')->nullable()->change();
            $table->string('pickup_contact_phone', 20)->nullable()->change();
            $table->string('package_description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pickup_contact_name')->nullable(false)->change();
            $table->string('pickup_contact_phone', 20)->nullable(false)->change();
            $table->string('package_description')->nullable(false)->change();
        });
    }
};
