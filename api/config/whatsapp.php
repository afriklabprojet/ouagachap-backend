<?php

/*
|--------------------------------------------------------------------------
| Configuration WhatsApp Cloud API — Templates de messages
|--------------------------------------------------------------------------
|
| Catalogue des templates Meta approuvés pour OUAGA CHAP.
| Les templates doivent être préalablement créés et approuvés dans le
| Meta Business Manager > WhatsApp Manager > Message Templates.
|
| Chaque template définit :
|   - name       : identifiant dans Meta (snake_case)
|   - lang       : code langue (fr, en_US…)
|   - category   : AUTHENTICATION | UTILITY | MARKETING
|   - components : liste des composants (HEADER, BODY, BUTTON)
|     Chaque paramètre dynamique est indiqué avec sa position {{index}}
|     et son type attendu (text, currency, date_time…)
|
| Usage via WhatsAppService::sendTemplate() :
|   $this->whatsAppService->sendTemplate(
|       $phone,
|       config('whatsapp.templates.otp_code.name'),
|       config('whatsapp.templates.otp_code.lang'),
|       $components
|   );
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Templates de messages
    |--------------------------------------------------------------------------
    */
    'templates' => [

        // ------------------------------------------------------------------
        // AUTHENTIFICATION — Code OTP
        // Catégorie : AUTHENTICATION (bouton "Copier le code" natif Meta)
        // Paramètres : {{1}} = code OTP (6 chiffres)
        // ------------------------------------------------------------------
        'otp_code' => [
            'name'     => env('WHATSAPP_CLOUD_OTP_TEMPLATE_NAME', 'otp_code'),
            'lang'     => env('WHATSAPP_CLOUD_OTP_TEMPLATE_LANG', 'fr'),
            'category' => 'AUTHENTICATION',
            'params'   => [
                // components à passer à sendTemplate()
                // [
                //   'type'       => 'body',
                //   'parameters' => [['type' => 'text', 'text' => $code]],
                // ],
                // [
                //   'type'    => 'button',
                //   'sub_type'=> 'url',
                //   'index'   => '0',
                //   'parameters' => [['type' => 'text', 'text' => $code]],
                // ],
            ],
        ],

        // ------------------------------------------------------------------
        // COMMANDE CONFIRMÉE
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = numéro de commande (ex : #OC-20240115-0042)
        //   {{2}} = montant total (ex : 3 500 FCFA)
        //   {{3}} = adresse de livraison (ex : Secteur 15, Ouagadougou)
        // ------------------------------------------------------------------
        'commande_confirmee' => [
            'name'     => 'commande_confirmee',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [
                // [
                //   'type' => 'body',
                //   'parameters' => [
                //     ['type' => 'text', 'text' => $orderNumber],
                //     ['type' => 'text', 'text' => $amount],
                //     ['type' => 'text', 'text' => $deliveryAddress],
                //   ],
                // ],
            ],
        ],

        // ------------------------------------------------------------------
        // COURSIER ASSIGNÉ
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = prénom du coursier (ex : Moussa)
        //   {{2}} = numéro de commande (ex : #OC-20240115-0042)
        //   {{3}} = temps estimé d'arrivée (ex : 25 minutes)
        // ------------------------------------------------------------------
        'coursier_assigne' => [
            'name'     => 'coursier_assigne',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [
                // [
                //   'type' => 'body',
                //   'parameters' => [
                //     ['type' => 'text', 'text' => $courierName],
                //     ['type' => 'text', 'text' => $orderNumber],
                //     ['type' => 'text', 'text' => $eta],
                //   ],
                // ],
            ],
        ],

        // ------------------------------------------------------------------
        // COMMANDE EN ROUTE
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = numéro de commande (ex : #OC-20240115-0042)
        //   {{2}} = prénom du coursier (ex : Moussa)
        // ------------------------------------------------------------------
        'commande_en_route' => [
            'name'     => 'commande_en_route',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [
                // [
                //   'type' => 'body',
                //   'parameters' => [
                //     ['type' => 'text', 'text' => $orderNumber],
                //     ['type' => 'text', 'text' => $courierName],
                //   ],
                // ],
            ],
        ],

        // ------------------------------------------------------------------
        // COMMANDE LIVRÉE
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = numéro de commande (ex : #OC-20240115-0042)
        //   {{2}} = montant total payé (ex : 3 500 FCFA)
        // ------------------------------------------------------------------
        'commande_livree' => [
            'name'     => 'commande_livree',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [
                // [
                //   'type' => 'body',
                //   'parameters' => [
                //     ['type' => 'text', 'text' => $orderNumber],
                //     ['type' => 'text', 'text' => $amount],
                //   ],
                // ],
            ],
        ],

        // ------------------------------------------------------------------
        // COMMANDE ANNULÉE
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = numéro de commande (ex : #OC-20240115-0042)
        //   {{2}} = raison de l'annulation (ex : aucun coursier disponible)
        // ------------------------------------------------------------------
        'commande_annulee' => [
            'name'     => 'commande_annulee',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [
                // [
                //   'type' => 'body',
                //   'parameters' => [
                //     ['type' => 'text', 'text' => $orderNumber],
                //     ['type' => 'text', 'text' => $reason],
                //   ],
                // ],
            ],
        ],

        // ------------------------------------------------------------------
        // RAPPEL DE PAIEMENT
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = numéro de commande (ex : #OC-20240115-0042)
        //   {{2}} = montant dû (ex : 3 500 FCFA)
        //   {{3}} = méthode de paiement suggérée (ex : Orange Money)
        // ------------------------------------------------------------------
        'rappel_paiement' => [
            'name'     => 'rappel_paiement',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [],
        ],

        // ------------------------------------------------------------------
        // NOTIFICATION GÉNÉRALE (freeform)
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = message libre (ex : "Votre compte a été suspendu.")
        // ------------------------------------------------------------------
        'notification_generale' => [
            'name'     => 'notification_generale',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [],
        ],

        // ------------------------------------------------------------------
        // BIENVENUE COURSIER (onboarding)
        // Catégorie : UTILITY
        // Paramètres :
        //   {{1}} = prénom du coursier (ex : Moussa)
        // ------------------------------------------------------------------
        'bienvenue_coursier' => [
            'name'     => 'bienvenue_coursier',
            'lang'     => 'fr',
            'category' => 'UTILITY',
            'params'   => [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Correspondance statut commande → template
    |--------------------------------------------------------------------------
    | Permet aux services de notification de résoudre automatiquement
    | le bon template en fonction du statut de la commande.
    |
    | Clés = constantes App\Enums\OrderStatus (ou équivalents)
    |
    */
    'order_status_template_map' => [
        'confirmed'  => 'commande_confirmee',
        'assigned'   => 'coursier_assigne',
        'in_transit' => 'commande_en_route',
        'delivered'  => 'commande_livree',
        'cancelled'  => 'commande_annulee',
    ],

];
