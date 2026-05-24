<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // order_messages: accélérer le comptage des messages non lus
        Schema::table('order_messages', function (Blueprint $table) {
            $table->index(['order_id', 'is_read', 'created_at'], 'order_messages_unread_index');
        });

        // geofence_logs: accélérer les requêtes de polyline par utilisateur + date
        Schema::table('geofence_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'geofence_logs_user_timeline_index');
        });

        // activity_logs: index composé pour filtrage admin log_type + created_at
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['log_type', 'created_at'], 'activity_logs_type_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_messages', function (Blueprint $table) {
            $table->dropIndex('order_messages_unread_index');
        });

        Schema::table('geofence_logs', function (Blueprint $table) {
            $table->dropIndex('geofence_logs_user_timeline_index');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_logs_type_date_index');
        });
    }
};
