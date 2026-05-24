<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renommer la table existante
        if (Schema::hasTable('jeko_transactions') && ! Schema::hasTable('sappay_transactions')) {
            Schema::rename('jeko_transactions', 'sappay_transactions');
        }

        // Si la table n'existait pas encore, la créer directement
        if (! Schema::hasTable('sappay_transactions')) {
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
        } else {
            // La table vient d'être renommée : ajouter les nouvelles colonnes Sappay
            Schema::table('sappay_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('sappay_transactions', 'invoice_id')) {
                    $table->string('invoice_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn('sappay_transactions', 'payment_processor_id')) {
                    $table->string('payment_processor_id')->nullable()->after('payment_method');
                }
                if (! Schema::hasColumn('sappay_transactions', 'customer_msisdn')) {
                    $table->string('customer_msisdn')->nullable()->after('payment_processor_id');
                }
                if (! Schema::hasColumn('sappay_transactions', 'requires_otp')) {
                    $table->boolean('requires_otp')->default(false)->after('status');
                }
                // Supprimer les colonnes spécifiques à Jeko
                $columnsToRemove = ['jeko_id', 'jeko_transaction_id', 'redirect_url', 'counterpart_label', 'counterpart_identifier', 'fees'];
                foreach ($columnsToRemove as $col) {
                    if (Schema::hasColumn('sappay_transactions', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sappay_transactions') && ! Schema::hasTable('jeko_transactions')) {
            Schema::rename('sappay_transactions', 'jeko_transactions');
        }
    }
};
