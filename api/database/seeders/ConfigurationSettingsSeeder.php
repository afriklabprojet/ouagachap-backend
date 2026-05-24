<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class ConfigurationSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ====================================================
            // APP CLIENT
            // ====================================================
            ['key' => 'client_app_min_version',              'value' => '1.0.0',   'type' => 'text',    'group' => 'app_client', 'label' => 'Version minimale requise'],
            ['key' => 'client_app_latest_version',           'value' => '1.0.0',   'type' => 'text',    'group' => 'app_client', 'label' => 'Dernière version disponible'],
            ['key' => 'client_app_google_play_url',          'value' => '',        'type' => 'text',    'group' => 'app_client', 'label' => 'Lien Google Play'],
            ['key' => 'client_app_app_store_url',            'value' => '',        'type' => 'text',    'group' => 'app_client', 'label' => 'Lien App Store'],
            ['key' => 'client_app_apk_direct_url',           'value' => '',        'type' => 'text',    'group' => 'app_client', 'label' => 'Lien APK direct'],
            ['key' => 'client_app_maintenance_mode',         'value' => '0',       'type' => 'boolean', 'group' => 'app_client', 'label' => 'Mode maintenance'],
            ['key' => 'client_app_maintenance_message',      'value' => 'Maintenance en cours. Veuillez réessayer dans quelques minutes.', 'type' => 'textarea', 'group' => 'app_client', 'label' => 'Message maintenance'],
            ['key' => 'client_app_max_orders_per_day',       'value' => '10',      'type' => 'number',  'group' => 'app_client', 'label' => 'Commandes max/jour'],
            ['key' => 'client_app_order_cancel_delay_minutes','value' => '5',      'type' => 'number',  'group' => 'app_client', 'label' => 'Délai annulation (min)'],
            ['key' => 'client_app_support_phone',            'value' => '+22670000000', 'type' => 'text', 'group' => 'app_client', 'label' => 'Téléphone support client'],
            ['key' => 'client_app_support_whatsapp',         'value' => '+22670000000', 'type' => 'text', 'group' => 'app_client', 'label' => 'WhatsApp support client'],

            // ====================================================
            // TARIFICATION GLOBALE
            // ====================================================
            ['key' => 'pricing_base_price_xof',              'value' => '500',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Prix de départ (FCFA)'],
            ['key' => 'pricing_price_per_km_xof',            'value' => '200',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Prix par km (FCFA)'],
            ['key' => 'pricing_min_order_price_xof',         'value' => '500',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Prix minimum commande (FCFA)'],
            ['key' => 'pricing_commission_rate_percent',     'value' => '15',    'type' => 'number',  'group' => 'pricing_global', 'label' => 'Taux commission (%)'],
            ['key' => 'pricing_commission_min_xof',          'value' => '50',    'type' => 'number',  'group' => 'pricing_global', 'label' => 'Commission minimum (FCFA)'],
            ['key' => 'pricing_commission_max_xof',          'value' => '5000',  'type' => 'number',  'group' => 'pricing_global', 'label' => 'Commission maximum (FCFA)'],
            ['key' => 'pricing_surge_enabled',               'value' => '0',     'type' => 'boolean', 'group' => 'pricing_global', 'label' => 'Surge pricing activé'],
            ['key' => 'pricing_surge_multiplier_max',        'value' => '2.0',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Multiplicateur surge max'],
            ['key' => 'pricing_surge_min_orders_threshold',  'value' => '10',    'type' => 'number',  'group' => 'pricing_global', 'label' => 'Seuil commandes surge'],
            ['key' => 'pricing_surge_low_couriers_threshold','value' => '3',     'type' => 'number',  'group' => 'pricing_global', 'label' => 'Seuil bas coursiers surge'],
            ['key' => 'pricing_extra_stop_fee_xof',          'value' => '100',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Frais arrêt supp. (FCFA)'],
            ['key' => 'pricing_night_fee_percent',           'value' => '20',    'type' => 'number',  'group' => 'pricing_global', 'label' => 'Supplément nuit (%)'],
            ['key' => 'pricing_fragile_fee_xof',             'value' => '200',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Frais colis fragile (FCFA)'],
            ['key' => 'pricing_heavy_fee_xof',               'value' => '300',   'type' => 'number',  'group' => 'pricing_global', 'label' => 'Frais colis lourd (FCFA)'],

            // ====================================================
            // HEURES D'OUVERTURE
            // ====================================================
            ['key' => 'hours_service_24h',      'value' => '0',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Service 24h/24'],
            ['key' => 'hours_monday_open',       'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Lundi ouvert'],
            ['key' => 'hours_monday_from',       'value' => '06:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Lundi ouverture'],
            ['key' => 'hours_monday_to',         'value' => '22:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Lundi fermeture'],
            ['key' => 'hours_tuesday_open',      'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Mardi ouvert'],
            ['key' => 'hours_tuesday_from',      'value' => '06:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Mardi ouverture'],
            ['key' => 'hours_tuesday_to',        'value' => '22:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Mardi fermeture'],
            ['key' => 'hours_wednesday_open',    'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Mercredi ouvert'],
            ['key' => 'hours_wednesday_from',    'value' => '06:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Mercredi ouverture'],
            ['key' => 'hours_wednesday_to',      'value' => '22:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Mercredi fermeture'],
            ['key' => 'hours_thursday_open',     'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Jeudi ouvert'],
            ['key' => 'hours_thursday_from',     'value' => '06:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Jeudi ouverture'],
            ['key' => 'hours_thursday_to',       'value' => '22:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Jeudi fermeture'],
            ['key' => 'hours_friday_open',       'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Vendredi ouvert'],
            ['key' => 'hours_friday_from',       'value' => '06:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Vendredi ouverture'],
            ['key' => 'hours_friday_to',         'value' => '22:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Vendredi fermeture'],
            ['key' => 'hours_saturday_open',     'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Samedi ouvert'],
            ['key' => 'hours_saturday_from',     'value' => '07:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Samedi ouverture'],
            ['key' => 'hours_saturday_to',       'value' => '21:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Samedi fermeture'],
            ['key' => 'hours_sunday_open',       'value' => '1',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Dimanche ouvert'],
            ['key' => 'hours_sunday_from',       'value' => '08:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Dimanche ouverture'],
            ['key' => 'hours_sunday_to',         'value' => '20:00', 'type' => 'text',    'group' => 'hours', 'label' => 'Dimanche fermeture'],
            ['key' => 'hours_closed_today',      'value' => '0',     'type' => 'boolean', 'group' => 'hours', 'label' => 'Fermé aujourd\'hui'],
            ['key' => 'hours_closed_message',    'value' => 'Le service est actuellement fermé. Nous reprenons bientôt.', 'type' => 'textarea', 'group' => 'hours', 'label' => 'Message fermeture'],

            // ====================================================
            // NOTIFICATIONS
            // ====================================================
            ['key' => 'notif_push_enabled',                     'value' => '1',  'type' => 'boolean',  'group' => 'notifications', 'label' => 'Push FCM activé'],
            ['key' => 'notif_sms_enabled',                      'value' => '1',  'type' => 'boolean',  'group' => 'notifications', 'label' => 'SMS activé'],
            ['key' => 'notif_whatsapp_enabled',                  'value' => '0',  'type' => 'boolean',  'group' => 'notifications', 'label' => 'WhatsApp activé'],
            ['key' => 'notif_max_push_per_day',                  'value' => '20', 'type' => 'number',   'group' => 'notifications', 'label' => 'Push max/jour'],
            ['key' => 'notif_max_sms_per_day',                   'value' => '5',  'type' => 'number',   'group' => 'notifications', 'label' => 'SMS max/jour'],
            ['key' => 'notif_otp_expiry_minutes',                'value' => '10', 'type' => 'number',   'group' => 'notifications', 'label' => 'Expiration OTP (min)'],
            ['key' => 'notif_otp_max_attempts',                  'value' => '3',  'type' => 'number',   'group' => 'notifications', 'label' => 'Tentatives OTP max'],
            ['key' => 'notif_tpl_order_created_client',         'value' => 'Votre commande #{order_number} a été créée. Un coursier va être assigné.',         'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: commande créée (client)'],
            ['key' => 'notif_tpl_courier_assigned_client',      'value' => '{courier_name} a accepté votre commande #{order_number} et est en route.',          'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: coursier assigné (client)'],
            ['key' => 'notif_tpl_order_picked_up_client',       'value' => 'Votre colis (commande #{order_number}) a été récupéré. Livraison en cours.',        'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: colis récupéré (client)'],
            ['key' => 'notif_tpl_order_delivered_client',       'value' => 'Votre commande #{order_number} a été livrée. Merci de noter votre coursier.',       'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: livré (client)'],
            ['key' => 'notif_tpl_order_cancelled_client',       'value' => 'Votre commande #{order_number} a été annulée.',                                     'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: annulée (client)'],
            ['key' => 'notif_tpl_payment_success_client',       'value' => 'Paiement de {amount} FCFA confirmé pour la commande #{order_number}.',              'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: paiement réussi (client)'],
            ['key' => 'notif_tpl_new_order_courier',            'value' => 'Nouvelle commande #{order_number} à {distance}km. Montant: {amount} FCFA.',         'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: nouvelle commande (coursier)'],
            ['key' => 'notif_tpl_order_assigned_courier',       'value' => 'La commande #{order_number} vous a été assignée. Rendez-vous à : {pickup_address}', 'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: assignée (coursier)'],
            ['key' => 'notif_tpl_order_cancelled_courier',      'value' => 'La commande #{order_number} a été annulée.',                                        'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: annulée (coursier)'],
            ['key' => 'notif_tpl_wallet_credit_courier',        'value' => 'Votre compte a été crédité de {amount} FCFA pour la livraison #{order_number}.',    'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: crédit wallet (coursier)'],
            ['key' => 'notif_tpl_withdrawal_processed_courier', 'value' => 'Votre retrait de {amount} FCFA a été envoyé sur votre {method}.',                   'type' => 'textarea', 'group' => 'notifications', 'label' => 'Template: retrait traité (coursier)'],
        ];

        foreach ($settings as $s) {
            SiteSetting::updateOrCreate(
                ['key' => $s['key']],
                ['value' => $s['value'], 'type' => $s['type'], 'group' => $s['group'], 'label' => $s['label']]
            );
        }

        $this->command->info('ConfigurationSettingsSeeder: ' . count($settings) . ' paramètres insérés/mis à jour.');
    }
}
