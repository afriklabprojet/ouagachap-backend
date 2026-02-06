<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => LegalPage::SLUG_TERMS,
                'title' => 'Conditions d\'utilisation',
                'order' => 1,
                'content' => $this->getTermsContent(),
            ],
            [
                'slug' => LegalPage::SLUG_PRIVACY,
                'title' => 'Politique de confidentialité',
                'order' => 2,
                'content' => $this->getPrivacyContent(),
            ],
            [
                'slug' => LegalPage::SLUG_LEGAL,
                'title' => 'Mentions légales',
                'order' => 3,
                'content' => $this->getLegalContent(),
            ],
            [
                'slug' => LegalPage::SLUG_FAQ,
                'title' => 'Questions fréquentes (FAQ)',
                'order' => 4,
                'content' => $this->getFaqContent(),
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }

    private function getTermsContent(): string
    {
        return <<<HTML
<h2>1. Objet des Conditions Générales</h2>
<p>Les présentes Conditions Générales d'Utilisation (CGU) régissent l'utilisation de l'application mobile OUAGA CHAP et des services de livraison proposés à Ouagadougou, Burkina Faso.</p>
<p>En utilisant nos services, vous acceptez sans réserve les présentes conditions.</p>

<h2>2. Description des Services</h2>
<p>OUAGA CHAP est une plateforme de mise en relation entre :</p>
<ul>
<li><strong>Les Clients</strong> : personnes souhaitant faire livrer des colis, documents ou achats</li>
<li><strong>Les Coursiers</strong> : livreurs indépendants effectuant les livraisons</li>
</ul>
<p>Nous proposons un service de livraison rapide et sécurisé dans la ville de Ouagadougou et ses environs.</p>

<h2>3. Inscription et Compte Utilisateur</h2>
<p>Pour utiliser nos services, vous devez :</p>
<ul>
<li>Avoir au moins 18 ans</li>
<li>Disposer d'un numéro de téléphone valide au Burkina Faso</li>
<li>Fournir des informations exactes lors de l'inscription</li>
</ul>
<p>La vérification de votre compte se fait par code OTP envoyé par SMS.</p>

<h2>4. Tarification</h2>
<p>Les tarifs de livraison sont calculés en fonction de :</p>
<ul>
<li>La distance entre le point de collecte et le point de livraison</li>
<li>Le type de colis (standard, fragile, volumineux)</li>
<li>L'urgence de la livraison</li>
</ul>
<p>Le prix final est communiqué avant confirmation de la commande.</p>

<h2>5. Paiement</h2>
<p>Les modes de paiement acceptés sont :</p>
<ul>
<li>Orange Money</li>
<li>Moov Money</li>
<li>Paiement en espèces à la livraison</li>
</ul>

<h2>6. Responsabilités</h2>
<h3>Responsabilité de OUAGA CHAP</h3>
<p>OUAGA CHAP s'engage à :</p>
<ul>
<li>Mettre en relation les clients avec des coursiers vérifiés</li>
<li>Assurer le bon fonctionnement de la plateforme</li>
<li>Traiter les réclamations dans un délai de 48h</li>
</ul>

<h3>Responsabilité du Client</h3>
<p>Le client s'engage à :</p>
<ul>
<li>Fournir des informations exactes sur le colis et les adresses</li>
<li>Ne pas expédier de marchandises illégales ou dangereuses</li>
<li>Être disponible pour la remise et la réception du colis</li>
</ul>

<h2>7. Annulation</h2>
<p>Une commande peut être annulée gratuitement :</p>
<ul>
<li>Tant qu'aucun coursier n'a accepté la course</li>
<li>Dans les 5 minutes suivant l'acceptation par un coursier</li>
</ul>
<p>Au-delà, des frais d'annulation peuvent s'appliquer.</p>

<h2>8. Réclamations et Litiges</h2>
<p>Pour toute réclamation :</p>
<ul>
<li>Contactez notre support via l'application</li>
<li>Envoyez un email à support@ouagachap.com</li>
<li>Appelez le +226 70 00 00 00</li>
</ul>
<p>Les litiges seront réglés selon la législation burkinabè.</p>

<h2>9. Modification des CGU</h2>
<p>OUAGA CHAP se réserve le droit de modifier ces conditions à tout moment. Les utilisateurs seront informés par notification dans l'application.</p>

<h2>10. Contact</h2>
<p>Pour toute question relative aux présentes CGU :</p>
<p><strong>OUAGA CHAP</strong><br>
Ouagadougou, Burkina Faso<br>
Email : contact@ouagachap.com<br>
Tél : +226 70 00 00 00</p>
HTML;
    }

    private function getPrivacyContent(): string
    {
        return <<<HTML
<h2>1. Introduction</h2>
<p>OUAGA CHAP s'engage à protéger la vie privée de ses utilisateurs. Cette politique de confidentialité explique comment nous collectons, utilisons et protégeons vos données personnelles.</p>

<h2>2. Données Collectées</h2>
<p>Nous collectons les données suivantes :</p>

<h3>Données d'identification</h3>
<ul>
<li>Nom et prénom</li>
<li>Numéro de téléphone</li>
<li>Photo de profil (optionnel)</li>
</ul>

<h3>Données de localisation</h3>
<ul>
<li>Position GPS lors de l'utilisation de l'application</li>
<li>Adresses de collecte et de livraison</li>
</ul>

<h3>Données de transaction</h3>
<ul>
<li>Historique des commandes</li>
<li>Informations de paiement (via Mobile Money)</li>
<li>Notes et avis</li>
</ul>

<h2>3. Utilisation des Données</h2>
<p>Vos données sont utilisées pour :</p>
<ul>
<li>Fournir et améliorer nos services de livraison</li>
<li>Vous mettre en relation avec les coursiers</li>
<li>Traiter les paiements</li>
<li>Vous envoyer des notifications sur vos commandes</li>
<li>Assurer la sécurité de la plateforme</li>
<li>Répondre à vos demandes de support</li>
</ul>

<h2>4. Partage des Données</h2>
<p>Nous partageons vos données avec :</p>
<ul>
<li><strong>Les coursiers</strong> : nom, adresses et numéro de téléphone pour effectuer la livraison</li>
<li><strong>Les prestataires de paiement</strong> : Orange Money, Moov Money pour traiter les transactions</li>
<li><strong>Les autorités</strong> : uniquement si requis par la loi</li>
</ul>
<p>Nous ne vendons jamais vos données à des tiers.</p>

<h2>5. Sécurité des Données</h2>
<p>Nous protégeons vos données par :</p>
<ul>
<li>Chiffrement SSL des communications</li>
<li>Stockage sécurisé sur des serveurs protégés</li>
<li>Accès restreint aux employés autorisés</li>
<li>Authentification par OTP</li>
</ul>

<h2>6. Conservation des Données</h2>
<p>Vos données sont conservées :</p>
<ul>
<li>Données de compte : jusqu'à suppression de votre compte + 1 an</li>
<li>Historique des commandes : 3 ans (obligations légales)</li>
<li>Données de paiement : selon les exigences légales</li>
</ul>

<h2>7. Vos Droits</h2>
<p>Vous disposez des droits suivants :</p>
<ul>
<li><strong>Accès</strong> : consulter vos données personnelles</li>
<li><strong>Rectification</strong> : corriger des informations inexactes</li>
<li><strong>Suppression</strong> : demander la suppression de votre compte</li>
<li><strong>Opposition</strong> : refuser certains traitements (marketing)</li>
</ul>
<p>Pour exercer ces droits, contactez : privacy@ouagachap.com</p>

<h2>8. Cookies et Technologies</h2>
<p>Notre application peut utiliser :</p>
<ul>
<li>Des identifiants de session pour maintenir votre connexion</li>
<li>Des tokens Firebase pour les notifications push</li>
<li>Des données de localisation pour le suivi en temps réel</li>
</ul>

<h2>9. Mineurs</h2>
<p>Nos services s'adressent aux personnes de 18 ans et plus. Nous ne collectons pas sciemment de données de mineurs.</p>

<h2>10. Modifications</h2>
<p>Cette politique peut être mise à jour. La date de dernière modification est indiquée en haut de page. En cas de changement majeur, nous vous informerons par notification.</p>

<h2>11. Contact</h2>
<p>Pour toute question sur vos données personnelles :</p>
<p><strong>Responsable Protection des Données</strong><br>
OUAGA CHAP<br>
Email : privacy@ouagachap.com<br>
Tél : +226 70 00 00 00</p>
HTML;
    }

    private function getLegalContent(): string
    {
        return <<<HTML
<h2>Éditeur de l'Application</h2>
<p>L'application OUAGA CHAP est éditée par :</p>
<p><strong>OUAGA CHAP SARL</strong><br>
Société à Responsabilité Limitée<br>
Capital social : 1 000 000 FCFA<br>
RCCM : BF-OUA-01-2024-B-12345<br>
IFU : 00012345X</p>

<p><strong>Siège social :</strong><br>
Avenue Kwame Nkrumah<br>
Secteur 4, Ouagadougou<br>
Burkina Faso</p>

<p><strong>Contact :</strong><br>
Téléphone : +226 70 00 00 00<br>
Email : contact@ouagachap.com</p>

<h2>Directeur de la Publication</h2>
<p>M. / Mme [Nom du Directeur]<br>
Gérant de OUAGA CHAP SARL</p>

<h2>Hébergement</h2>
<p>L'application et les données sont hébergées par :</p>
<p><strong>[Nom de l'hébergeur]</strong><br>
[Adresse]<br>
[Pays]</p>

<h2>Propriété Intellectuelle</h2>
<p>L'ensemble des éléments de l'application OUAGA CHAP (logo, nom, design, textes, images, fonctionnalités) sont la propriété exclusive de OUAGA CHAP SARL.</p>
<p>Toute reproduction, représentation, modification ou exploitation non autorisée est interdite et constitue une contrefaçon sanctionnée par le Code de la Propriété Intellectuelle.</p>

<h2>Marques</h2>
<p><strong>OUAGA CHAP</strong> est une marque déposée de OUAGA CHAP SARL.</p>
<p>Les marques Orange Money et Moov Money sont la propriété de leurs détenteurs respectifs.</p>

<h2>Crédits</h2>
<p><strong>Conception et développement :</strong><br>
OUAGA CHAP Tech Team</p>

<p><strong>Icônes :</strong><br>
Heroicons (MIT License)</p>

<h2>Droit Applicable</h2>
<p>Les présentes mentions légales sont soumises au droit burkinabè. En cas de litige, les tribunaux de Ouagadougou seront seuls compétents.</p>

<h2>Médiation</h2>
<p>En cas de litige, vous pouvez recourir à une procédure de médiation ou tout autre mode alternatif de règlement des différends.</p>
<p>Contact médiation : mediation@ouagachap.com</p>
HTML;
    }

    private function getFaqContent(): string
    {
        return <<<HTML
<h3>Comment créer un compte OUAGA CHAP ?</h3>
<p>Téléchargez l'application OUAGA CHAP depuis notre site. Entrez votre numéro de téléphone et validez-le avec le code OTP reçu par SMS. Complétez votre profil avec votre nom et vous êtes prêt à commander !</p>

<h3>Quels sont les tarifs de livraison ?</h3>
<p>Nos tarifs dépendent de la distance et du type de colis :</p>
<ul>
<li><strong>Petit colis (0-5 km)</strong> : à partir de 500 FCFA</li>
<li><strong>Colis standard (5-10 km)</strong> : à partir de 1 000 FCFA</li>
<li><strong>Longue distance (10+ km)</strong> : tarif calculé selon la distance</li>
</ul>
<p>Le prix exact est affiché avant confirmation de votre commande.</p>

<h3>Comment suivre ma livraison en temps réel ?</h3>
<p>Une fois votre commande acceptée par un coursier, vous pouvez suivre sa position en temps réel sur la carte dans l'application. Vous recevrez également des notifications à chaque étape : collecte du colis, en route, livraison effectuée.</p>

<h3>Quels modes de paiement acceptez-vous ?</h3>
<p>Nous acceptons :</p>
<ul>
<li><strong>Orange Money</strong> : paiement sécurisé via votre compte Orange</li>
<li><strong>Moov Money</strong> : paiement via votre compte Moov</li>
<li><strong>Espèces</strong> : paiement au coursier à la livraison</li>
</ul>

<h3>Puis-je annuler ma commande ?</h3>
<p>Oui, vous pouvez annuler gratuitement votre commande :</p>
<ul>
<li>Avant qu'un coursier n'accepte la course</li>
<li>Dans les 5 minutes suivant l'acceptation</li>
</ul>
<p>Après ce délai, des frais d'annulation de 300 FCFA peuvent s'appliquer pour compenser le déplacement du coursier.</p>

<h3>Que faire si mon colis est endommagé ?</h3>
<p>En cas de problème avec votre colis :</p>
<ul>
<li>Prenez des photos du colis endommagé</li>
<li>Contactez immédiatement notre support via l'application</li>
<li>Nous traiterons votre réclamation sous 48h</li>
</ul>
<p>Une indemnisation peut être proposée selon les circonstances.</p>

<h3>Quels types de colis puis-je envoyer ?</h3>
<p>Vous pouvez envoyer :</p>
<ul>
<li>Documents et courriers</li>
<li>Petits colis et paquets</li>
<li>Achats et commandes</li>
<li>Nourriture et repas</li>
</ul>
<p><strong>Interdits :</strong> produits illégaux, matières dangereuses, animaux vivants, objets de grande valeur non déclarés.</p>

<h3>Les coursiers sont-ils vérifiés ?</h3>
<p>Oui, tous nos coursiers passent par un processus de vérification :</p>
<ul>
<li>Vérification d'identité (CNIB)</li>
<li>Vérification du permis de conduire</li>
<li>Vérification du véhicule</li>
<li>Formation aux bonnes pratiques</li>
</ul>
<p>Vous pouvez consulter la note et les avis des coursiers dans l'application.</p>

<h3>Comment devenir coursier OUAGA CHAP ?</h3>
<p>Pour rejoindre notre équipe de coursiers :</p>
<ul>
<li>Téléchargez l'application Coursier OUAGA CHAP</li>
<li>Créez votre compte avec vos informations</li>
<li>Soumettez vos documents (CNIB, permis, carte grise)</li>
<li>Attendez la validation (24-48h)</li>
<li>Commencez à effectuer des livraisons !</li>
</ul>

<h3>Comment contacter le support ?</h3>
<p>Notre équipe support est disponible :</p>
<ul>
<li><strong>Dans l'application</strong> : section Aide & Support</li>
<li><strong>Par téléphone</strong> : +226 70 00 00 00 (8h-20h)</li>
<li><strong>Par email</strong> : support@ouagachap.com</li>
<li><strong>WhatsApp</strong> : +226 70 00 00 00</li>
</ul>
HTML;
    }
}
