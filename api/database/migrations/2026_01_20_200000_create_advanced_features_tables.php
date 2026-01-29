<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table des réclamations/litiges
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('courier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['delivery_issue', 'payment_issue', 'courier_behavior', 'app_bug', 'other']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->string('subject');
            $table->text('description');
            $table->text('resolution')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // Table des messages de réclamation
        Schema::create('complaint_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        // Table des bannières/annonces
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('action_url')->nullable();
            $table->enum('type', ['promo', 'announcement', 'alert', 'info'])->default('announcement');
            $table->enum('target', ['all', 'clients', 'couriers'])->default('all');
            $table->enum('position', ['home_top', 'home_bottom', 'orders_list', 'profile'])->default('home_top');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // Table des messages de chat support
        Schema::create('support_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_chat_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // Table des alertes automatiques
        Schema::create('auto_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('trigger_type', [
                'order_delayed',
                'courier_offline',
                'low_couriers',
                'high_pending_orders',
                'withdrawal_pending',
                'negative_rating',
            ]);
            $table->json('conditions')->nullable();
            $table->json('actions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('cooldown_minutes')->default(30);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });

        // Table des zones de géofencing
        Schema::create('geofences', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('coordinates'); // Polygone de coordonnées
            $table->enum('type', ['allowed', 'restricted', 'surge'])->default('allowed');
            $table->decimal('surge_multiplier', 3, 2)->default(1.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Logs de géofencing
        Schema::create('geofence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('geofence_id')->constrained()->onDelete('cascade');
            $table->enum('event', ['entered', 'exited']);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofence_logs');
        Schema::dropIfExists('geofences');
        Schema::dropIfExists('auto_alerts');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_chats');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('complaint_messages');
        Schema::dropIfExists('complaints');
    }
};
