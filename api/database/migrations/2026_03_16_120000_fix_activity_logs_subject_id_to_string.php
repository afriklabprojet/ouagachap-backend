<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix subject_id column to support UUIDs (Order uses HasUuids).
     * The original migration uses nullableUuidMorphs but the column
     * may have been created as integer on some deployments.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('subject_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        // No rollback - keeping as string is safe for both int and UUID
    }
};
