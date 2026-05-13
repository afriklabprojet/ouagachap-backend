<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class CourierAppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================================
        // Groupe : App Coursier — paramètres affichés dans l'app mobile coursier
        // =====================================================================
        SiteSetting::updateOrCreate(['key' => 'courier_app_min_version'], [
            'label' => 'Version minimale app coursier',
            'value' => '1.0.0',
            'type' => SiteSetting::TYPE_TEXT,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Version en-dessous de laquelle l\'app force une mise à jour.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_welcome_bonus_xof'], [
            'label' => 'Bonus de bienvenue (FCFA)',
            'value' => '500',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Montant crédité sur le portefeuille du coursier à sa première activation.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_referral_bonus_xof'], [
            'label' => 'Bonus parrainage coursier (FCFA)',
            'value' => '1000',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Bonus versé au coursier qui en parraine un autre après sa 1re livraison.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_support_phone'], [
            'label' => 'Téléphone support coursier',
            'value' => '+226 70 00 00 00',
            'type' => SiteSetting::TYPE_TEXT,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Numéro affiché dans l\'app coursier pour contacter le support.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_support_whatsapp'], [
            'label' => 'WhatsApp support coursier',
            'value' => '+226 70 00 00 00',
            'type' => SiteSetting::TYPE_TEXT,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Numéro WhatsApp affiché dans l\'app coursier.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_max_active_orders'], [
            'label' => 'Commandes simultanées max',
            'value' => '3',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Nombre maximum de commandes qu\'un coursier peut accepter en même temps.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_gps_interval_seconds'], [
            'label' => 'Intervalle GPS (secondes)',
            'value' => '10',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Fréquence d\'envoi de la position GPS du coursier au serveur.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_maintenance_mode'], [
            'label' => 'Mode maintenance app coursier',
            'value' => '0',
            'type' => SiteSetting::TYPE_BOOLEAN,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Si activé, l\'app coursier affiche un écran de maintenance et bloque la connexion.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'courier_app_maintenance_message'], [
            'label' => 'Message maintenance',
            'value' => 'Maintenance en cours. Veuillez réessayer dans quelques minutes.',
            'type' => SiteSetting::TYPE_TEXTAREA,
            'group' => SiteSetting::GROUP_APP_COURIER,
            'description' => 'Texte affiché aux coursiers lors d\'une maintenance.',
        ]);

        // =====================================================================
        // Groupe : Dispatch & Affectation
        // =====================================================================
        SiteSetting::updateOrCreate(['key' => 'dispatch_accept_timeout_seconds'], [
            'label' => 'Délai d\'acceptation (secondes)',
            'value' => '30',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Temps dont dispose un coursier pour accepter ou refuser une commande avant qu\'elle soit proposée au suivant.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'dispatch_radius_km'], [
            'label' => 'Rayon de recherche (km)',
            'value' => '5',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Rayon maximal (en km) dans lequel chercher un coursier disponible autour du point de collecte.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'dispatch_max_attempts'], [
            'label' => 'Tentatives max de dispatch',
            'value' => '5',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Nombre de coursiers contactés avant de marquer la commande comme non assignable.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'dispatch_auto_assign_enabled'], [
            'label' => 'Dispatch automatique activé',
            'value' => '1',
            'type' => SiteSetting::TYPE_BOOLEAN,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Si désactivé, les commandes restent en attente jusqu\'à une affectation manuelle par l\'admin.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'dispatch_algorithm'], [
            'label' => 'Algorithme de dispatch',
            'value' => 'nearest',
            'type' => SiteSetting::TYPE_TEXT,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Stratégie : nearest (le plus proche), rating (meilleure note), round_robin (rotation équitable).',
        ]);
        SiteSetting::updateOrCreate(['key' => 'dispatch_peak_hours'], [
            'label' => 'Heures de pointe',
            'value' => json_encode([
                ['start' => '07:00', 'end' => '09:30', 'label' => 'Matin'],
                ['start' => '11:30', 'end' => '14:00', 'label' => 'Midi'],
                ['start' => '17:00', 'end' => '20:00', 'label' => 'Soir'],
            ]),
            'type' => SiteSetting::TYPE_JSON,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Plages horaires de forte demande. Format JSON : [{start, end, label}].',
        ]);
        SiteSetting::updateOrCreate(['key' => 'dispatch_surge_multiplier_max'], [
            'label' => 'Multiplicateur surge maximum',
            'value' => '2.0',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_DISPATCH,
            'description' => 'Plafond du multiplicateur de prix en période de forte demande (surge). Ex: 2.0 = max +100%.',
        ]);

        // =====================================================================
        // Groupe : Portefeuille & Retraits
        // =====================================================================
        SiteSetting::updateOrCreate(['key' => 'wallet_min_withdrawal_xof'], [
            'label' => 'Retrait minimum (FCFA)',
            'value' => '500',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Montant minimum qu\'un coursier peut retirer en une fois.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'wallet_max_withdrawal_xof'], [
            'label' => 'Retrait maximum (FCFA)',
            'value' => '100000',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Montant maximum par retrait.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'wallet_withdrawal_fee_percent'], [
            'label' => 'Frais de retrait (%)',
            'value' => '0',
            'type' => SiteSetting::TYPE_NUMBER,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Pourcentage prélevé sur chaque retrait. 0 = gratuit.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'wallet_withdrawal_methods'], [
            'label' => 'Méthodes de retrait activées',
            'value' => json_encode(['orange_money', 'moov_money']),
            'type' => SiteSetting::TYPE_JSON,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Liste des opérateurs Mobile Money acceptés pour les retraits.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'wallet_auto_payout_enabled'], [
            'label' => 'Virement automatique activé',
            'value' => '0',
            'type' => SiteSetting::TYPE_BOOLEAN,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Si activé, les retraits validés sont envoyés automatiquement via l\'API Mobile Money.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'wallet_payout_schedule'], [
            'label' => 'Fréquence de paiement',
            'value' => 'daily',
            'type' => SiteSetting::TYPE_TEXT,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Fréquence des virements auto : daily, weekly, manual.',
        ]);
        SiteSetting::updateOrCreate(['key' => 'wallet_commission_display_enabled'], [
            'label' => 'Afficher détail commission dans l\'app',
            'value' => '1',
            'type' => SiteSetting::TYPE_BOOLEAN,
            'group' => SiteSetting::GROUP_WALLET,
            'description' => 'Si activé, le coursier voit le détail de la commission prélevée par commande.',
        ]);
    }
}
