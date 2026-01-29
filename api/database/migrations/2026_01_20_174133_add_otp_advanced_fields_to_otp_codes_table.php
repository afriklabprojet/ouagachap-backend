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
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->integer('attempts')->default(0)->after('is_used');
            $table->integer('max_attempts')->default(3)->after('attempts');
            $table->string('purpose')->default('login')->after('max_attempts');
            $table->string('ip_address')->nullable()->after('purpose');
            $table->string('user_agent')->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'max_attempts', 'purpose', 'ip_address', 'user_agent']);
        });
    }
};
