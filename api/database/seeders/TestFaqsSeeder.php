<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class TestFaqsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('❓ Création des FAQs de test...');
        $this->command->newLine();

        $faqs = [
            // Catégorie: Général
            [
                'category' => 'general',
                'question' => 'Qu\'est-ce que OUAGA CHAP ?',
                'answer' => 'OUAGA CHAP est un service de livraison rapide à Ouagadougou qui vous permet d\'envoyer et de recevoir des colis partout dans la ville. Nous connectons les clients avec des coursiers locaux pour des livraisons rapides et sécurisées.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'category' => 'general',
                'question' => 'Dans quelles zones livrez-vous ?',
                'answer' => 'Nous livrons dans tout Ouagadougou et sa périphérie, incluant : Centre-ville, Ouaga 2000, Zone du Bois, Cité An III, Dassasgho, Wemtenga, Zone Industrielle de Kossodo et les zones périurbaines nord et sud.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'category' => 'general',
                'question' => 'Quels sont vos horaires de service ?',
                'answer' => 'OUAGA CHAP est disponible 7j/7 de 6h à 22h. Pour les livraisons en dehors de ces horaires, des frais supplémentaires peuvent s\'appliquer selon la disponibilité des coursiers.',
                'order' => 3,
                'is_active' => true,
            ],

            // Catégorie: Commandes
            [
                'category' => 'commandes',
                'question' => 'Comment passer une commande ?',
                'answer' => '1. Ouvrez l\'application OUAGA CHAP\n2. Entrez l\'adresse de récupération\n3. Entrez l\'adresse de livraison\n4. Décrivez votre colis\n5. Confirmez et payez\n\nUn coursier sera assigné automatiquement et vous pourrez suivre la livraison en temps réel.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'category' => 'commandes',
                'question' => 'Puis-je annuler une commande ?',
                'answer' => 'Oui, vous pouvez annuler une commande avant qu\'un coursier ne soit assigné sans frais. Une fois le coursier assigné, des frais d\'annulation peuvent s\'appliquer selon la distance déjà parcourue.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'category' => 'commandes',
                'question' => 'Combien de temps prend une livraison ?',
                'answer' => 'Le temps de livraison dépend de la distance et du trafic. En général :\n- Moins de 5 km : 15-30 minutes\n- 5-10 km : 30-45 minutes\n- Plus de 10 km : 45-60 minutes\n\nVous pouvez suivre votre livraison en temps réel dans l\'application.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'category' => 'commandes',
                'question' => 'Que puis-je faire livrer ?',
                'answer' => 'Vous pouvez faire livrer :\n✅ Documents et colis\n✅ Nourriture et repas\n✅ Médicaments (avec ordonnance si nécessaire)\n✅ Vêtements et accessoires\n✅ Électronique de petite taille\n\n❌ Nous ne transportons pas : alcool, produits illégaux, animaux vivants, objets de grande taille.',
                'order' => 4,
                'is_active' => true,
            ],

            // Catégorie: Paiement
            [
                'category' => 'paiement',
                'question' => 'Quels modes de paiement acceptez-vous ?',
                'answer' => 'Nous acceptons :\n💵 Espèces (à la livraison)\n📱 Orange Money\n📱 Moov Money\n\nLe paiement mobile est recommandé pour une expérience plus rapide et sécurisée.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'category' => 'paiement',
                'question' => 'Comment utiliser un code promo ?',
                'answer' => 'Lors de la validation de votre commande :\n1. Appuyez sur "Ajouter un code promo"\n2. Entrez votre code\n3. Appuyez sur "Appliquer"\n\nLa réduction sera automatiquement appliquée à votre commande si le code est valide.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'category' => 'paiement',
                'question' => 'Comment sont calculés les tarifs ?',
                'answer' => 'Le tarif comprend :\n- Un prix de base (variable selon la zone)\n- Un prix au kilomètre\n- Des frais supplémentaires éventuels (nuit, colis fragile, etc.)\n\nLe prix total est affiché avant confirmation de la commande, sans frais cachés.',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'category' => 'paiement',
                'question' => 'Puis-je obtenir un remboursement ?',
                'answer' => 'Les remboursements sont possibles dans les cas suivants :\n- Annulation avant assignation du coursier : 100% remboursé\n- Colis endommagé par notre faute : remboursement après enquête\n- Livraison non effectuée : 100% remboursé\n\nContactez notre support avec votre numéro de commande pour toute demande.',
                'order' => 4,
                'is_active' => true,
            ],

            // Catégorie: Compte
            [
                'category' => 'compte',
                'question' => 'Comment créer un compte ?',
                'answer' => '1. Téléchargez l\'application OUAGA CHAP\n2. Entrez votre numéro de téléphone\n3. Recevez et entrez le code OTP\n4. Complétez vos informations (nom, prénom)\n\nC\'est tout ! Pas besoin de mot de passe, votre téléphone est votre identifiant.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'category' => 'compte',
                'question' => 'Comment modifier mes informations ?',
                'answer' => 'Dans l\'application :\n1. Allez dans "Profil"\n2. Appuyez sur "Modifier"\n3. Changez vos informations\n4. Sauvegardez\n\nPour changer de numéro de téléphone, contactez notre support.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'category' => 'compte',
                'question' => 'Comment supprimer mon compte ?',
                'answer' => 'Pour supprimer votre compte :\n1. Contactez notre support via l\'application\n2. Confirmez votre identité\n3. Votre compte sera supprimé sous 48h\n\nAttention : toutes vos données seront définitivement effacées.',
                'order' => 3,
                'is_active' => true,
            ],

            // Catégorie: Coursiers
            [
                'category' => 'coursiers',
                'question' => 'Comment devenir coursier OUAGA CHAP ?',
                'answer' => 'Pour devenir coursier :\n1. Téléchargez l\'app Coursier OUAGA CHAP\n2. Inscrivez-vous avec votre téléphone\n3. Fournissez les documents requis :\n   - Carte d\'identité\n   - Permis de conduire\n   - Carte grise du véhicule\n4. Attendez la validation de votre profil (24-48h)\n\nUne fois validé, vous pouvez commencer à livrer !',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'category' => 'coursiers',
                'question' => 'Quels véhicules sont acceptés ?',
                'answer' => 'Véhicules acceptés :\n🛵 Motos et scooters\n🚲 Vélos (pour courtes distances)\n🚗 Voitures (pour gros colis)\n\nLe véhicule doit être en bon état et avoir les documents à jour.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'category' => 'coursiers',
                'question' => 'Comment sont payés les coursiers ?',
                'answer' => 'Les coursiers reçoivent :\n- 85% du montant de chaque livraison\n- Paiement instantané après livraison (Mobile Money)\n- Bonus pour les meilleures notes et performances\n\nLes gains sont visibles en temps réel dans l\'application coursier.',
                'order' => 3,
                'is_active' => true,
            ],

            // Catégorie: Support
            [
                'category' => 'support',
                'question' => 'Comment contacter le support ?',
                'answer' => 'Plusieurs options :\n📱 Dans l\'app : Menu > Support\n📞 Téléphone : +226 70 00 00 00\n📧 Email : support@ouagachap.bf\n💬 WhatsApp : +226 70 00 00 00\n\nNotre équipe répond généralement sous 1 heure pendant les heures ouvrables.',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'category' => 'support',
                'question' => 'Comment signaler un problème avec une livraison ?',
                'answer' => '1. Ouvrez la commande concernée dans l\'app\n2. Appuyez sur "Signaler un problème"\n3. Sélectionnez le type de problème\n4. Décrivez la situation (photos si nécessaire)\n\nNotre équipe vous contactera rapidement pour résoudre le problème.',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'category' => 'support',
                'question' => 'Mon colis est perdu, que faire ?',
                'answer' => 'En cas de perte de colis :\n1. Vérifiez le statut dans l\'application\n2. Contactez le coursier via l\'app\n3. Si pas de réponse, contactez le support immédiatement\n\nNous prenons en charge les colis perdus et vous proposons une indemnisation après enquête.',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faqData) {
            $faq = Faq::updateOrCreate(
                [
                    'category' => $faqData['category'],
                    'question' => $faqData['question'],
                ],
                $faqData
            );

            $status = $faq->is_active ? '✅' : '❌';
            $this->command->line("  {$status} [{$faq->category}] {$this->truncate($faq->question, 50)}");
        }

        $this->command->newLine();
        $this->displaySummary();
    }

    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - 3) . '...';
    }

    private function displaySummary(): void
    {
        $total = Faq::count();
        $byCategory = Faq::where('is_active', true)
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $this->command->info('❓ Résumé des FAQs:');
        $this->command->table(
            ['Catégorie', 'Nombre'],
            collect($byCategory)->map(fn($count, $cat) => [ucfirst($cat), $count])->toArray()
        );
        $this->command->line("  Total: {$total} FAQs");
    }
}
