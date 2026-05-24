<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_delivery_reports', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->index();
            $table->string('phone', 20)->index();
            $table->string('sender', 30)->nullable();
            $table->string('status_group', 30);       // PENDING, DELIVERED, EXPIRED, REJECTED, UNDELIVERABLE
            $table->string('status_name', 50);         // PENDING_ENROUTE, DELIVERED_TO_HANDSET, etc.
            $table->text('status_description')->nullable();
            $table->integer('error_code')->default(0);
            $table->string('error_name', 50)->nullable();
            $table->text('error_description')->nullable();
            $table->decimal('price', 8, 4)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('callback_data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->integer('sms_count')->default(1);
            $table->timestamps();

            $table->index(['status_group', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_delivery_reports');
    }
};
