<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pour stocker les transactions JEKO (Mobile Money)
     */
    public function up(): void
    {
        Schema::create('jeko_transactions', function (Blueprint $table) {
            $table->id();
            
            // Utilisateur
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Identifiants JEKO
            $table->string('jeko_id')->nullable()->index(); // ID de la demande de paiement
            $table->string('jeko_transaction_id')->nullable()->index(); // ID de la transaction finale
            $table->string('reference')->unique(); // Notre référence unique
            
            // Type et méthode
            $table->enum('type', ['wallet_recharge', 'order_payment', 'other'])->default('wallet_recharge');
            $table->string('payment_method'); // wave, orange, mtn, moov, djamo
            
            // Montant
            $table->decimal('amount', 12, 2); // Montant en FCFA
            $table->string('currency', 3)->default('XOF');
            $table->decimal('fees', 12, 2)->default(0); // Frais éventuels
            
            // Statut
            $table->enum('status', [
                'pending',    // En attente de paiement
                'success',    // Paiement réussi
                'error',      // Paiement échoué
                'expired',    // Délai dépassé
                'cancelled',  // Annulé
            ])->default('pending');
            
            // URLs et redirection
            $table->string('redirect_url', 500)->nullable();
            
            // Informations du payeur (reçues via webhook)
            $table->string('counterpart_label')->nullable(); // Nom du payeur
            $table->string('counterpart_identifier')->nullable(); // Numéro de téléphone
            
            // Metadata (données supplémentaires: order_id, etc.)
            $table->json('metadata')->nullable();
            
            // Payload complet du webhook (pour debug)
            $table->json('webhook_payload')->nullable();
            
            // Timestamps
            $table->timestamp('executed_at')->nullable(); // Date d'exécution du paiement
            $table->timestamps();
            
            // Index
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jeko_transactions');
    }
};
