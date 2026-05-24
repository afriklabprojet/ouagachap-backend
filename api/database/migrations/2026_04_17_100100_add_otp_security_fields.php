<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->uuid('challenge_id')->nullable()->unique()->after('id');
            $table->string('nonce', 64)->nullable()->after('code');
            $table->string('code_hash', 255)->nullable()->after('code');
            $table->unsignedBigInteger('issued_at')->nullable()->after('expires_at');
            $table->unsignedBigInteger('verified_at')->nullable()->after('issued_at');
            $table->unsignedBigInteger('firebase_auth_time')->nullable()->after('verified_at');
            $table->timestamp('locked_until')->nullable()->after('max_attempts');
            $table->unsignedInteger('resend_count')->default(0)->after('locked_until');
            $table->string('device_fingerprint', 64)->nullable()->after('user_agent');
            $table->boolean('is_challenge_closed')->default(false)->after('is_used');

            $table->index(['phone', 'challenge_id']);
            $table->index(['phone', 'is_challenge_closed', 'expires_at']);
            $table->index(['device_fingerprint', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex(['phone', 'challenge_id']);
            $table->dropIndex(['phone', 'is_challenge_closed', 'expires_at']);
            $table->dropIndex(['device_fingerprint', 'created_at']);

            $table->dropColumn([
                'challenge_id',
                'nonce',
                'code_hash',
                'issued_at',
                'verified_at',
                'firebase_auth_time',
                'locked_until',
                'resend_count',
                'device_fingerprint',
                'is_challenge_closed',
            ]);
        });
    }
};
