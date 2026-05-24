<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add referral fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 8)->unique()->nullable()->after('phone_verified_at');
            $table->foreignId('referred_by_user_id')
                ->nullable()
                ->after('referral_code')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Create referrals table
        Schema::create('referrals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamp('referrer_rewarded_at')->nullable();
            $table->timestamp('referred_rewarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by_user_id']);
        });
    }
};
