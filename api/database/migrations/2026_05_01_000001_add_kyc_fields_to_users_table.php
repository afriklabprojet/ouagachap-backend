<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('identity_document_url')->nullable()->after('device_type');
            $table->string('selfie_url')->nullable()->after('identity_document_url');
            $table->timestamp('documents_submitted_at')->nullable()->after('selfie_url');
            $table->timestamp('documents_verified_at')->nullable()->after('documents_submitted_at');
            $table->string('kyc_status')->default('none')->after('documents_verified_at');
            // kyc_status: none | pending | approved | rejected
            $table->text('kyc_rejection_reason')->nullable()->after('kyc_status');

            $table->index('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'identity_document_url',
                'selfie_url',
                'documents_submitted_at',
                'documents_verified_at',
                'kyc_status',
                'kyc_rejection_reason',
            ]);
        });
    }
};
