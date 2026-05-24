<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Paramètres généraux
        SiteSetting::set('site_name', 'OUAGA CHAP', 'text', 'general', 'Nom du site');
        SiteSetting::set('site_tagline', 'Livraison rapide à Ouagadougou', 'text', 'general', 'Slogan');

        // SEO
        SiteSetting::set('seo_title', 'OUAGA CHAP - Service de livraison rapide à Ouagadougou', 'text', 'seo', 'Titre SEO');
        SiteSetting::set('seo_description', 'Service de livraison express à Ouagadougou, Burkina Faso. Livraison de colis, courses et repas en moins de 30 minutes.', 'textarea', 'seo', 'Description SEO');
        SiteSetting::set('seo_keywords', 'livraison, Ouagadougou, Burkina Faso, coursier, colis, rapide, OUAGA CHAP', 'text', 'seo', 'Mots-clés SEO');

        // Section Hero
        SiteSetting::set('hero_badge', '🚀 #1 à Ouagadougou', 'text', 'hero', 'Badge Hero');
        SiteSetting::set('hero_title', 'Livraison express à Ouagadougou', 'text', 'hero', 'Titre Hero');
        SiteSetting::set('hero_highlight', 'express', 'text', 'hero', 'Mot mis en évidence');
        SiteSetting::set('hero_description', 'Vos colis livrés en moins de 30 minutes. Courses, documents, repas... Nous livrons tout ce dont vous avez besoin, partout dans la ville.', 'textarea', 'hero', 'Description Hero');

        // Statistiques
        SiteSetting::set('stat_deliveries', '10K+', 'text', 'hero', 'Nombre de livraisons');
        SiteSetting::set('stat_couriers', '500+', 'text', 'hero', 'Nombre de coursiers');
        SiteSetting::set('stat_rating', '4.8★', 'text', 'hero', 'Note moyenne');

        // Section Fonctionnalités
        SiteSetting::set('features_title', 'Pourquoi choisir OUAGA CHAP?', 'text', 'features', 'Titre section fonctionnalités');
        SiteSetting::set('features_description', 'Une application conçue pour faciliter votre quotidien avec des fonctionnalités pensées pour vous.', 'textarea', 'features', 'Description section fonctionnalités');

        // Fonctionnalités (JSON)
        $features = [
            ['icon' => '⚡', 'title' => 'Livraison Ultra-Rapide', 'description' => 'Vos colis livrés en moins de 30 minutes partout à Ouagadougou. Notre réseau de coursiers est prêt 24h/24.', 'color' => 'primary'],
            ['icon' => '📍', 'title' => 'Suivi en Temps Réel', 'description' => 'Suivez votre coursier sur la carte en temps réel. Vous savez exactement où se trouve votre colis.', 'color' => 'green'],
            ['icon' => '💳', 'title' => 'Paiement Sécurisé', 'description' => 'Payez par Mobile Money (Orange Money, Moov Money) ou en espèces à la livraison. Simple et sécurisé.', 'color' => 'blue'],
            ['icon' => '💬', 'title' => 'Support 24/7', 'description' => 'Notre équipe est disponible 24h/24 pour répondre à vos questions et résoudre vos problèmes.', 'color' => 'purple'],
            ['icon' => '⭐', 'title' => 'Coursiers Vérifiés', 'description' => 'Tous nos coursiers sont vérifiés et notés. Consultez les avis avant de commander.', 'color' => 'yellow'],
            ['icon' => '🛡️', 'title' => 'Assurance Colis', 'description' => 'Vos colis sont assurés contre la perte et les dommages. Livraison garantie ou remboursement.', 'color' => 'red'],
        ];
        SiteSetting::set('features', json_encode($features), 'json', 'features', 'Liste des fonctionnalités');

        // Section Tarifs
        SiteSetting::set('pricing_title', 'Des prix transparents', 'text', 'pricing', 'Titre section tarifs');
        SiteSetting::set('pricing_description', 'Pas de frais cachés. Le prix affiché est le prix payé.', 'textarea', 'pricing', 'Description section tarifs');

        // Tarifs (JSON)
        $pricing = [
            [
                'emoji' => '🛵',
                'name' => 'Moto',
                'subtitle' => 'Petits colis',
                'base_price' => 500,
                'price_per_km' => 100,
                'features' => "Jusqu'à 10 kg\nLivraison en 30 min\nSuivi en temps réel",
                'is_popular' => false,
            ],
            [
                'emoji' => '🚗',
                'name' => 'Voiture',
                'subtitle' => 'Colis moyens',
                'base_price' => 1500,
                'price_per_km' => 150,
                'features' => "Jusqu'à 50 kg\nLivraison en 45 min\nClimatisé\nFragile accepté",
                'is_popular' => true,
            ],
            [
                'emoji' => '🚚',
                'name' => 'Camionnette',
                'subtitle' => 'Gros colis',
                'base_price' => 5000,
                'price_per_km' => 200,
                'features' => "Jusqu'à 500 kg\nLivraison en 1h\nAide au chargement",
                'is_popular' => false,
            ],
        ];
        SiteSetting::set('pricing', json_encode($pricing), 'json', 'pricing', 'Plans tarifaires');

        // Section Témoignages
        SiteSetting::set('testimonials_title', 'Ce que disent nos utilisateurs', 'text', 'testimonials', 'Titre section témoignages');

        // Témoignages (JSON)
        $testimonials = [
            [
                'content' => 'Service excellent! J\'ai fait livrer des documents urgents en 20 minutes. Le coursier était très professionnel. Je recommande!',
                'author' => 'Aminata Konaté',
                'role' => 'Entrepreneuse',
                'initials' => 'AK',
                'rating' => 5,
            ],
            [
                'content' => 'En tant que coursier, je gagne bien ma vie avec OUAGA CHAP. Les paiements sont rapides et l\'application est facile à utiliser.',
                'author' => 'Oumar Sanou',
                'role' => 'Coursier',
                'initials' => 'OS',
                'rating' => 5,
            ],
            [
                'content' => 'J\'utilise OUAGA CHAP pour mon restaurant. Mes clients reçoivent leurs commandes encore chaudes. C\'est génial!',
                'author' => 'Fatou Diallo',
                'role' => 'Restauratrice',
                'initials' => 'FD',
                'rating' => 5,
            ],
        ];
        SiteSetting::set('testimonials', json_encode($testimonials), 'json', 'testimonials', 'Liste des témoignages');

        // Section Coursier
        SiteSetting::set('courier_title', 'Devenez coursier et gagnez de l\'argent', 'text', 'courier', 'Titre section coursier');
        SiteSetting::set('courier_description', 'Rejoignez notre équipe de coursiers et travaillez à votre rythme. Gagnez jusqu\'à 150,000 FCFA par mois en effectuant des livraisons.', 'textarea', 'courier', 'Description section coursier');
        SiteSetting::set('courier_commission', '85', 'number', 'courier', 'Commission coursier (%)');
        SiteSetting::set('courier_benefits', "Horaires flexibles - Travaillez quand vous voulez\nPaiements quotidiens - Retirez vos gains chaque jour\nBonus et primes - Gagnez plus avec les défis", 'textarea', 'courier', 'Avantages coursier');

        // Section Comment ça marche
        SiteSetting::set('how_it_works_title', 'Comment ça marche?', 'text', 'how_it_works', 'Titre section Comment ça marche');
        SiteSetting::set('how_it_works_description', 'En 3 étapes simples, faites livrer vos colis partout à Ouagadougou.', 'textarea', 'how_it_works', 'Description section Comment ça marche');

        $howItWorksSteps = [
            ['num' => '1', 'title' => 'Commandez', 'description' => 'Ouvrez l\'app, entrez les adresses de récupération et de livraison.'],
            ['num' => '2', 'title' => 'Suivez', 'description' => 'Un coursier accepte votre commande. Suivez-le en temps réel.'],
            ['num' => '3', 'title' => 'Recevez', 'description' => 'Votre colis est livré! Payez et notez votre coursier.'],
        ];
        SiteSetting::set('how_it_works_steps', json_encode($howItWorksSteps), 'json', 'how_it_works', 'Étapes Comment ça marche');

        // Section Téléchargement
        SiteSetting::set('download_title', 'Téléchargez l\'application maintenant', 'text', 'download', 'Titre section téléchargement');
        SiteSetting::set('download_description', 'Disponible sur Android. Téléchargez l\'APK directement et commencez à utiliser OUAGA CHAP.', 'textarea', 'download', 'Description section téléchargement');
        SiteSetting::set('apk_install_step_1', 'Téléchargez le fichier APK sur votre téléphone Android', 'text', 'download', 'Instruction installation étape 1');
        SiteSetting::set('apk_install_step_2', 'Allez dans Paramètres → Sécurité → Autoriser les sources inconnues', 'text', 'download', 'Instruction installation étape 2');
        SiteSetting::set('apk_install_step_3', 'Ouvrez le fichier APK téléchargé et cliquez sur "Installer"', 'text', 'download', 'Instruction installation étape 3');
        SiteSetting::set('apk_install_step_4', 'Une fois installé, ouvrez l\'application et créez votre compte', 'text', 'download', 'Instruction installation étape 4');

        // Contact
        SiteSetting::set('contact_title', 'Besoin d\'aide?', 'text', 'contact', 'Titre section contact');
        SiteSetting::set('contact_description', 'Notre équipe est disponible 24h/24 pour répondre à vos questions.', 'textarea', 'contact', 'Description section contact');
        SiteSetting::set('contact_phone', '+226 70 00 00 00', 'text', 'contact', 'Téléphone');
        SiteSetting::set('contact_whatsapp', '+226 70 00 00 00', 'text', 'contact', 'WhatsApp');
        SiteSetting::set('contact_email', 'contact@ouagachap.com', 'text', 'contact', 'Email');
        SiteSetting::set('contact_address', 'Ouagadougou, Burkina Faso', 'text', 'contact', 'Adresse');

        // Réseaux sociaux
        SiteSetting::set('social_facebook', 'https://facebook.com/ouagachap', 'text', 'social', 'Facebook URL');
        SiteSetting::set('social_twitter', 'https://twitter.com/ouagachap', 'text', 'social', 'Twitter URL');
        SiteSetting::set('social_instagram', 'https://instagram.com/ouagachap', 'text', 'social', 'Instagram URL');

        // Footer
        SiteSetting::set('footer_description', 'Le service de livraison #1 à Ouagadougou. Rapide, fiable et abordable.', 'textarea', 'general', 'Description footer');

        // Libellés statistiques
        SiteSetting::set('stat_deliveries_label', 'Livraisons', 'text', 'hero', 'Libellé stat livraisons');
        SiteSetting::set('stat_couriers_label', 'Coursiers actifs', 'text', 'hero', 'Libellé stat coursiers');
        SiteSetting::set('stat_rating_label', 'Note moyenne', 'text', 'hero', 'Libellé stat note');

        // Mockup téléphone
        SiteSetting::set('phone_greeting', 'Bonjour! 👋', 'text', 'hero', 'Accueil mockup téléphone');
        SiteSetting::set('phone_subtitle', 'Où livrons-nous aujourd\'hui?', 'text', 'hero', 'Sous-titre mockup téléphone');
        SiteSetting::set('phone_input_placeholder', 'Entrez l\'adresse de livraison...', 'text', 'hero', 'Placeholder recherche mockup');

        // Téléchargements
        SiteSetting::set('apk_client_version', '1.0.0', 'text', 'general', 'Version APK Client');
        SiteSetting::set('apk_client_size', '25 MB', 'text', 'general', 'Taille APK Client');
        SiteSetting::set('apk_courier_version', '1.0.0', 'text', 'general', 'Version APK Coursier');
        SiteSetting::set('apk_courier_size', '28 MB', 'text', 'general', 'Taille APK Coursier');
    }
}
