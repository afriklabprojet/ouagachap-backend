<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration initiale : crée toutes les tables de base du projet.
 * Nécessaire pour les tests SQLite (les tables de base proviennent de schema.sql
 * en production, mais les tests utilisent RefreshDatabase avec migrations).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Zones (pas de FK)
        if (! Schema::hasTable('zones')) {
            Schema::create('zones', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->decimal('base_price', 10, 2)->default(500.00);
                $table->decimal('price_per_km', 10, 2)->default(200.00);
                $table->decimal('commission_rate', 5, 4)->default(0.1500);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Users (pas de FK)
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 20)->unique();
                $table->string('firebase_uid')->unique()->nullable();
                $table->string('name')->nullable();
                $table->string('email')->unique()->nullable();
                $table->string('avatar')->nullable();
                $table->string('password')->nullable();
                $table->string('role')->default('client'); // enum: client, courier, admin
                $table->string('status')->default('active'); // enum: pending, active, suspended, rejected
                $table->string('vehicle_type')->nullable();
                $table->string('vehicle_plate')->nullable();
                $table->string('vehicle_model')->nullable();
                $table->boolean('is_available')->default(false);
                $table->decimal('current_latitude', 10, 8)->nullable();
                $table->decimal('current_longitude', 11, 8)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamp('location_updated_at')->nullable();
                $table->unsignedInteger('total_orders')->default(0);
                $table->decimal('average_rating', 3, 2)->default(0.00);
                $table->unsignedInteger('total_ratings')->default(0);
                $table->decimal('wallet_balance', 12, 2)->default(0.00);
                $table->string('fcm_token')->nullable();
                $table->json('notification_preferences')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->string('device_type')->nullable();
                $table->timestamp('fcm_token_updated_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Sanctum personal_access_tokens
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Password reset tokens
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // 5. Sessions
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // 6. Cache
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }
        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // 7. Jobs
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }
        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // 8. Orders (refs users, zones)
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('order_number')->unique();
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('recipient_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
                $table->string('status')->default('pending'); // enum: pending, assigned, accepted, picking_up, picked_up, in_transit, delivered, cancelled
                $table->string('pickup_address');
                $table->decimal('pickup_latitude', 10, 8);
                $table->decimal('pickup_longitude', 11, 8);
                $table->string('pickup_contact_name');
                $table->string('pickup_contact_phone', 20);
                $table->text('pickup_instructions')->nullable();
                $table->string('dropoff_address');
                $table->decimal('dropoff_latitude', 10, 8);
                $table->decimal('dropoff_longitude', 11, 8);
                $table->string('dropoff_contact_name');
                $table->string('dropoff_contact_phone', 20);
                $table->text('dropoff_instructions')->nullable();
                $table->string('recipient_confirmation_code', 6)->nullable();
                $table->boolean('recipient_confirmed')->default(false);
                $table->string('package_description');
                $table->string('package_size')->default('small');
                $table->string('payment_method')->default('cash');
                $table->decimal('distance_km', 8, 2)->nullable();
                $table->decimal('base_price', 10, 2);
                $table->decimal('distance_price', 10, 2)->default(0.00);
                $table->decimal('total_price', 10, 2);
                $table->decimal('commission_amount', 10, 2)->default(0.00);
                $table->decimal('courier_earnings', 10, 2)->default(0.00);
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('picked_up_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancellation_reason')->nullable();
                $table->unsignedTinyInteger('client_rating')->nullable();
                $table->text('client_review')->nullable();
                $table->unsignedTinyInteger('courier_rating')->nullable();
                $table->text('courier_review')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 9. Payments (refs users, orders)
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique();
                $table->string('order_id', 36);
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->decimal('amount', 12, 2);
                $table->string('method'); // enum: orange_money, moov_money, cash, wave, mtn_money, djamo
                $table->string('status')->default('pending'); // enum: pending, success, failed
                $table->string('payment_type')->default('order');
                $table->string('phone_number', 20);
                $table->string('provider_transaction_id')->nullable();
                $table->text('provider_response')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->string('failure_reason')->nullable();
                $table->timestamps();
            });
        }

        // 10. Wallets (refs users)
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
                $table->decimal('balance', 12, 2)->default(0.00);
                $table->decimal('pending_balance', 12, 2)->default(0.00);
                $table->decimal('total_earned', 12, 2)->default(0.00);
                $table->decimal('total_withdrawn', 12, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 11. Wallet transactions (refs users)
        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('transaction_id')->unique();
                $table->decimal('amount', 12, 2);
                $table->string('type')->default('recharge'); // enum: recharge, debit, refund
                $table->string('method'); // enum: orange_money, moov_money, cash, bank_transfer, wave, mtn_money, djamo
                $table->string('phone_number')->nullable();
                $table->string('status')->default('pending'); // enum: pending, success, failed
                $table->string('provider_transaction_id')->nullable();
                $table->text('provider_response')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('failure_reason')->nullable();
                $table->timestamps();
            });
        }

        // 12. Withdrawals (refs wallets, users)
        if (! Schema::hasTable('withdrawals')) {
            Schema::create('withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
                $table->decimal('amount', 12, 2);
                $table->string('status')->default('pending'); // pending|approved|rejected|completed
                $table->string('payment_method')->default('mobile_money'); // mobile_money|bank_transfer
                $table->string('payment_phone')->nullable();
                $table->string('payment_provider')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account')->nullable();
                $table->string('transaction_reference')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 13. Notifications
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // 14. In-app notifications
        if (! Schema::hasTable('in_app_notifications')) {
            Schema::create('in_app_notifications', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('type')->nullable();
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('icon')->nullable();
                $table->string('color')->nullable();
                $table->json('data')->nullable();
                $table->string('action_url')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        // 15. OTP codes
        if (! Schema::hasTable('otp_codes')) {
            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 20);
                $table->string('code', 6);
                $table->timestamp('expires_at');
                $table->boolean('is_used')->default(false);
                $table->integer('attempts')->default(0);
                $table->integer('max_attempts')->default(3);
                $table->string('purpose')->default('login');
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
                $table->index(['phone', 'code', 'is_used']);
                $table->index('phone');
            });
        }

        // 16. Saved addresses
        if (! Schema::hasTable('saved_addresses')) {
            Schema::create('saved_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('label');
                $table->string('address');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->string('contact_name')->nullable();
                $table->string('contact_phone', 20)->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('is_default')->default(false);
                $table->string('type')->default('other'); // home, work, other
                $table->timestamps();
            });
        }

        // 17. Order status histories (refs orders, users)
        if (! Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table) {
                $table->id();
                $table->string('order_id', 36);
                $table->string('status');
                $table->string('previous_status')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('note')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->timestamps();
            });
        }

        // 18. Sappay transactions (anciennement jeko_transactions)
        if (! Schema::hasTable('sappay_transactions') && ! Schema::hasTable('jeko_transactions')) {
            Schema::create('sappay_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('invoice_id')->nullable()->index();
                $table->string('reference')->unique();
                $table->string('type')->default('wallet_recharge');
                $table->string('payment_method');
                $table->string('payment_processor_id')->nullable();
                $table->string('customer_msisdn')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('XOF');
                $table->string('status')->default('pending');
                $table->boolean('requires_otp')->default(false);
                $table->json('metadata')->nullable();
                $table->json('webhook_payload')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
            });
        }

        // 19–20. Geofences et geofence_logs sont créées par
        // 2026_01_20_200000_create_advanced_features_tables (migration #6)
        // — ne pas les créer ici pour éviter le conflit.

        // 21. Traffic incidents
        if (! Schema::hasTable('traffic_incidents')) {
            Schema::create('traffic_incidents', function (Blueprint $table) {
                $table->string('id', 36)->primary();
                $table->unsignedBigInteger('reporter_id')->nullable();
                $table->string('type');
                $table->string('severity')->default('moderate');
                $table->decimal('latitude', 10, 8);
                $table->decimal('longitude', 11, 8);
                $table->string('address')->nullable();
                $table->text('description')->nullable();
                $table->integer('confirmations')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamps();
            });
        }

        // 22. FAQs
        if (! Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('question');
                $table->text('answer');
                $table->string('category')->nullable();
                $table->string('target')->default('all');
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->unsignedInteger('views')->default(0);
                $table->timestamps();
            });
        }

        // 23. Legal pages
        if (! Schema::hasTable('legal_pages')) {
            Schema::create('legal_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('title');
                $table->longText('content');
                $table->string('meta_title')->nullable();
                $table->string('meta_description')->nullable();
                $table->boolean('is_published')->default(true);
                $table->unsignedSmallInteger('order')->default(0);
                $table->timestamps();
            });
        }

        // 24. Site settings
        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('text');
                $table->string('group')->nullable();
                $table->string('label')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 25. Spatie laravel-permission tables
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }
        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }
        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }
        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->primary(['permission_id', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('legal_pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('traffic_incidents');
        Schema::dropIfExists('sappay_transactions');
        Schema::dropIfExists('jeko_transactions');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('saved_addresses');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('in_app_notifications');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('zones');
    }
};
