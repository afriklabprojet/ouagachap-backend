<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_auth_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone', 20)->index();
            $table->string('device_name')->default('mobile');
            $table->string('device_fingerprint', 64)->index();
            $table->string('refresh_token_hash', 64)->unique();
            $table->unsignedBigInteger('refresh_expires_at');
            $table->unsignedBigInteger('last_rotated_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_auth_sessions');
    }
};
