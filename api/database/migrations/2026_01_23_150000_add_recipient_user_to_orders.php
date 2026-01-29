<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute recipient_user_id pour lier le destinataire à un compte utilisateur
     * Cela permet aux utilisateurs de voir les colis qu'ils vont RECEVOIR
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // ID du destinataire s'il a un compte dans l'app
            $table->foreignId('recipient_user_id')
                ->nullable()
                ->after('client_id')
                ->constrained('users')
                ->nullOnDelete();
            
            // Code de confirmation pour le destinataire
            $table->string('recipient_confirmation_code', 6)
                ->nullable()
                ->after('dropoff_instructions');
            
            // Le destinataire a-t-il confirmé la réception ?
            $table->boolean('recipient_confirmed')
                ->default(false)
                ->after('recipient_confirmation_code');
            
            // Index pour rechercher les colis entrants d'un utilisateur
            $table->index('recipient_user_id', 'idx_recipient');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['recipient_user_id']);
            $table->dropIndex('idx_recipient');
            $table->dropColumn([
                'recipient_user_id',
                'recipient_confirmation_code',
                'recipient_confirmed'
            ]);
        });
    }
};
