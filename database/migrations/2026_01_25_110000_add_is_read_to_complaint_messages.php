<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne is_read à la table complaint_messages
     */
    public function up(): void
    {
        Schema::table('complaint_messages', function (Blueprint $table) {
            $table->boolean('is_read')->default(false)->after('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaint_messages', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
    }
};
