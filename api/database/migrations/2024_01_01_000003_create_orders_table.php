<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            
            // Relations
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            
            // Status
            $table->enum('status', array_column(OrderStatus::cases(), 'value'))->default(OrderStatus::PENDING->value);
            
            // Pickup details
            $table->string('pickup_address');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            $table->string('pickup_contact_name');
            $table->string('pickup_contact_phone', 20);
            $table->text('pickup_instructions')->nullable();
            
            // Dropoff details
            $table->string('dropoff_address');
            $table->decimal('dropoff_latitude', 10, 8);
            $table->decimal('dropoff_longitude', 11, 8);
            $table->string('dropoff_contact_name');
            $table->string('dropoff_contact_phone', 20);
            $table->text('dropoff_instructions')->nullable();
            
            // Package details
            $table->string('package_description');
            $table->string('package_size')->default('small'); // small, medium, large
            
            // Pricing
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('distance_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('courier_earnings', 10, 2)->default(0);
            
            // Timestamps
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            
            // Ratings
            $table->unsignedTinyInteger('client_rating')->nullable();
            $table->text('client_review')->nullable();
            $table->unsignedTinyInteger('courier_rating')->nullable();
            $table->text('courier_review')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index('order_number');
            $table->index(['client_id', 'status']);
            $table->index(['courier_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
