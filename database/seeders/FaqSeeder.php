<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            // Général
            [
                'category' => 'general',
                'question' => 'Qu\'est-ce que OUAGA CHAP ?',
                'answer' => 'OUAGA CHAP est un service de livraison rapide à Ouagadougou. Nous connectons les clients avec des coursiers fiables pour livrer vos colis, documents, repas et bien plus encore dans toute la ville.',
                'order' => 1,
            ],
            [
                'category' => 'general',
                'question' => 'Comment fonctionne OUAGA CHAP ?',
                'answer' => "1. Créez votre commande en indiquant l'adresse de collecte et de livraison\n2. Choisissez le type de colis et le mode de paiement\n3. Un coursier proche accepte votre commande\n4. Suivez la livraison en temps réel\n5. Recevez votre colis et notez le coursier",
                'order' => 2,
            ],
            [
                'category' => 'general',
                'question' => 'Dans quelles zones OUAGA CHAP est-il disponible ?',
                'answer' => 'OUAGA CHAP couvre actuellement l\'ensemble de Ouagadougou et ses environs proches. Nous étendons progressivement notre couverture à d\'autres villes du Burkina Faso.',
                'order' => 3,
            ],

            // Commandes
            [
                'category' => 'orders',
                'question' => 'Comment passer une commande ?',
                'answer' => "Pour passer une commande :\n1. Ouvrez l'application et connectez-vous\n2. Appuyez sur \"Nouvelle livraison\"\n3. Entrez l'adresse de collecte\n4. Entrez l'adresse de livraison\n5. Sélectionnez le type de colis\n6. Validez et attendez qu'un coursier accepte",
                'order' => 1,
            ],
            [
                'category' => 'orders',
                'question' => 'Comment suivre ma livraison ?',
                'answer' => 'Vous pouvez suivre votre livraison en temps réel directement dans l\'application. Allez dans "Mes commandes" et sélectionnez votre commande active. Vous verrez la position du coursier sur la carte.',
                'order' => 2,
            ],
            [
                'category' => 'orders',
                'question' => 'Puis-je annuler une commande ?',
                'answer' => "Oui, vous pouvez annuler une commande tant qu'un coursier n'a pas encore récupéré votre colis. Allez dans \"Mes commandes\", sélectionnez la commande et appuyez sur \"Annuler\". Des frais d'annulation peuvent s'appliquer selon les cas.",
                'order' => 3,
            ],
            [
                'category' => 'orders',
                'question' => 'Quels types de colis puis-je envoyer ?',
                'answer' => "Vous pouvez envoyer :\n• Documents et courriers\n• Colis légers (jusqu'à 5kg)\n• Repas et plats préparés\n• Courses et achats\n• Médicaments (avec ordonnance)\n\nLes objets illégaux, dangereux ou périssables non emballés sont interdits.",
                'order' => 4,
            ],

            // Paiement
            [
                'category' => 'payment',
                'question' => 'Quels modes de paiement acceptez-vous ?',
                'answer' => "Nous acceptons :\n• Orange Money\n• Moov Money\n• Portefeuille OUAGA CHAP\n• Paiement à la livraison (cash)\n\nLe paiement par carte bancaire sera bientôt disponible.",
                'order' => 1,
            ],
            [
                'category' => 'payment',
                'question' => 'Comment utiliser mon portefeuille OUAGA CHAP ?',
                'answer' => "1. Allez dans Profil > Portefeuille\n2. Appuyez sur \"Recharger\"\n3. Choisissez Orange Money ou Moov Money\n4. Entrez le montant à recharger\n5. Validez avec votre code mobile money\n\nVotre solde sera crédité instantanément.",
                'order' => 2,
            ],
            [
                'category' => 'payment',
                'question' => 'Comment les prix sont-ils calculés ?',
                'answer' => "Le prix d'une livraison dépend de :\n• La distance entre les points de collecte et de livraison\n• Le type de colis (document, colis, repas...)\n• L'heure de la commande (tarifs majorés aux heures de pointe)\n• Les promotions en cours\n\nVous voyez toujours le prix estimé avant de valider.",
                'order' => 3,
            ],

            // Livraison
            [
                'category' => 'delivery',
                'question' => 'Combien de temps prend une livraison ?',
                'answer' => 'Le temps de livraison moyen est de 30 à 45 minutes selon la distance et le trafic. Vous pouvez voir une estimation du temps de livraison lors de la création de votre commande.',
                'order' => 1,
            ],
            [
                'category' => 'delivery',
                'question' => 'Que faire si le coursier ne trouve pas l\'adresse ?',
                'answer' => 'Le coursier vous appellera directement. Assurez-vous que votre numéro de téléphone est correct. Vous pouvez également ajouter des instructions de livraison détaillées lors de la création de la commande.',
                'order' => 2,
            ],
            [
                'category' => 'delivery',
                'question' => 'Mon colis est endommagé, que faire ?',
                'answer' => "Si votre colis est endommagé :\n1. Prenez des photos du colis et des dommages\n2. Refusez la livraison si les dégâts sont importants\n3. Créez une réclamation dans Aide & Support\n4. Notre équipe traitera votre demande sous 24h",
                'order' => 3,
            ],

            // Compte
            [
                'category' => 'account',
                'question' => 'Comment créer un compte ?',
                'answer' => "1. Téléchargez l'application OUAGA CHAP\n2. Entrez votre numéro de téléphone\n3. Vous recevrez un code OTP par SMS\n4. Entrez le code pour vérifier votre numéro\n5. Complétez votre profil (nom, photo...)\n\nVotre compte est créé !",
                'order' => 1,
            ],
            [
                'category' => 'account',
                'question' => 'Comment modifier mes informations ?',
                'answer' => 'Allez dans Profil > Paramètres du compte. Vous pouvez modifier votre nom, photo de profil, adresses enregistrées et préférences de notification.',
                'order' => 2,
            ],
            [
                'category' => 'account',
                'question' => 'Comment supprimer mon compte ?',
                'answer' => "Pour supprimer votre compte, contactez notre support via l'application ou par email à support@ouagachap.com. Notez que cette action est irréversible et toutes vos données seront supprimées.",
                'order' => 3,
            ],

            // Portefeuille
            [
                'category' => 'wallet',
                'question' => 'Comment recharger mon portefeuille ?',
                'answer' => "1. Allez dans Profil > Portefeuille\n2. Appuyez sur \"Recharger\"\n3. Sélectionnez votre opérateur (Orange Money ou Moov Money)\n4. Entrez le montant souhaité\n5. Confirmez avec votre PIN mobile money\n\nLe crédit est ajouté instantanément.",
                'order' => 1,
            ],
            [
                'category' => 'wallet',
                'question' => 'Y a-t-il des limites de recharge ?',
                'answer' => "Montant minimum : 500 FCFA\nMontant maximum : 100 000 FCFA par transaction\n\nIl n'y a pas de limite sur le solde total de votre portefeuille.",
                'order' => 2,
            ],
            [
                'category' => 'wallet',
                'question' => 'Puis-je retirer l\'argent de mon portefeuille ?',
                'answer' => 'Non, le solde du portefeuille OUAGA CHAP ne peut pas être retiré. Il peut uniquement être utilisé pour payer vos livraisons sur la plateforme.',
                'order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['is_active' => true])
            );
        }

        $this->command->info('✅ ' . count($faqs) . ' FAQs créées avec succès !');
    }
}
