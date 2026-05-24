<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        LegalPage::updateOrCreate(
            ['slug' => LegalPage::SLUG_DELETION],
            [
                'title'            => 'Suppression de vos données',
                'meta_title'       => 'Suppression des données - OUAGA CHAP',
                'meta_description' => 'Guide de suppression de vos données personnelles collectées par OUAGA CHAP.',
                'is_published'     => true,
                'order'            => 10,
                'content'          => <<<'HTML'
<div class="max-w-3xl mx-auto py-10 px-4 text-gray-800">

  <h1 class="text-3xl font-bold mb-2 text-primary-600">Suppression de vos données personnelles</h1>
  <p class="text-sm text-gray-500 mb-8">Dernière mise à jour : mai 2026</p>

  <!-- Introduction -->
  <section class="mb-8">
    <p class="text-base leading-relaxed">
      Conformément au <strong>Règlement Général sur la Protection des Données (RGPD)</strong> et à la
      législation burkinabè sur la protection des données personnelles, vous disposez du droit de demander
      la suppression de l'ensemble des données personnelles que OUAGA CHAP a collectées vous concernant.
    </p>
  </section>

  <!-- Données collectées -->
  <section class="mb-8">
    <h2 class="text-xl font-semibold mb-3">Quelles données collectons-nous ?</h2>
    <ul class="list-disc list-inside space-y-1 text-base">
      <li>Nom, prénom et numéro de téléphone</li>
      <li>Adresses de livraison enregistrées</li>
      <li>Historique des commandes et transactions</li>
      <li>Données de géolocalisation lors des livraisons</li>
      <li>Informations de paiement (non stockées en clair)</li>
      <li>Journaux de connexion et d'utilisation de l'application</li>
    </ul>
  </section>

  <!-- Comment faire la demande -->
  <section class="mb-8">
    <h2 class="text-xl font-semibold mb-3">Comment demander la suppression ?</h2>
    <p class="mb-4">Vous pouvez demander la suppression de vos données de l'une des façons suivantes :</p>

    <div class="space-y-4">

      <!-- Option 1 : depuis l'app -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-800 mb-1">Option 1 — Depuis l'application</h3>
        <ol class="list-decimal list-inside text-sm text-blue-700 space-y-1">
          <li>Ouvrez <strong>OUAGA CHAP</strong></li>
          <li>Accédez à <strong>Profil → Paramètres</strong></li>
          <li>Sélectionnez <strong>« Supprimer mon compte »</strong></li>
          <li>Confirmez la suppression</li>
        </ol>
      </div>

      <!-- Option 2 : par e-mail -->
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h3 class="font-semibold text-green-800 mb-1">Option 2 — Par e-mail</h3>
        <p class="text-sm text-green-700">
          Envoyez un e-mail à
          <a href="mailto:privacy@ouagachap.com" class="underline font-medium">privacy@ouagachap.com</a>
          avec l'objet <strong>« Demande de suppression de données »</strong>
          en précisant le numéro de téléphone associé à votre compte.
        </p>
      </div>

      <!-- Option 3 : WhatsApp -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h3 class="font-semibold text-yellow-800 mb-1">Option 3 — Via WhatsApp</h3>
        <p class="text-sm text-yellow-700">
          Contactez notre support directement sur
          <a href="https://wa.me/22600000000" class="underline font-medium" target="_blank" rel="noopener">
            WhatsApp
          </a>
          en mentionnant votre numéro de téléphone et votre demande de suppression.
        </p>
      </div>

    </div>
  </section>

  <!-- Délais -->
  <section class="mb-8">
    <h2 class="text-xl font-semibold mb-3">Délais de traitement</h2>
    <p class="text-base leading-relaxed">
      Votre demande sera traitée dans un délai de <strong>30 jours</strong> à compter de sa réception.
      Une confirmation vous sera envoyée par SMS ou e-mail une fois la suppression effectuée.
    </p>
  </section>

  <!-- Exceptions légales -->
  <section class="mb-8">
    <h2 class="text-xl font-semibold mb-3">Exceptions légales</h2>
    <p class="text-base leading-relaxed">
      Certaines données peuvent être conservées au-delà de votre demande afin de respecter nos
      obligations légales (comptabilité, obligations fiscales, prévention de la fraude),
      pour une durée maximale de <strong>5 ans</strong> après la clôture de votre compte.
    </p>
  </section>

  <!-- Contact -->
  <section class="bg-gray-50 border border-gray-200 rounded-lg p-6">
    <h2 class="text-xl font-semibold mb-2">Contact — Délégué à la Protection des Données</h2>
    <p class="text-sm text-gray-600">
      OUAGA CHAP SAS<br>
      Ouagadougou, Burkina Faso<br>
      E-mail : <a href="mailto:privacy@ouagachap.com" class="text-primary-600 underline">privacy@ouagachap.com</a><br>
      Téléphone : <a href="tel:+22600000000" class="text-primary-600 underline">+226 00 00 00 00</a>
    </p>
  </section>

</div>
HTML,
            ]
        );

        LegalPage::updateOrCreate(
            ['slug' => LegalPage::SLUG_FAQ],
            [
                'title'            => 'FAQ - Questions fréquentes',
                'meta_title'       => 'FAQ - OUAGA CHAP',
                'meta_description' => 'Réponses aux questions fréquentes sur OUAGA CHAP.',
                'is_published'     => true,
                'order'            => 5,
                'content'          => <<<'HTML'
<div class="max-w-3xl mx-auto py-10 px-4 text-gray-800">
  <h1 class="text-3xl font-bold mb-6 text-primary-600">Questions fréquentes (FAQ)</h1>

  <details class="mb-4 border border-gray-200 rounded-lg p-4">
    <summary class="font-semibold cursor-pointer">Comment commander une livraison ?</summary>
    <p class="mt-2 text-gray-600">Téléchargez l'application OUAGA CHAP, créez un compte et suivez les instructions pour passer votre première commande.</p>
  </details>

  <details class="mb-4 border border-gray-200 rounded-lg p-4">
    <summary class="font-semibold cursor-pointer">Quels sont les délais de livraison ?</summary>
    <p class="mt-2 text-gray-600">Nos coursiers livrent en moins de 30 minutes dans Ouagadougou.</p>
  </details>

  <details class="mb-4 border border-gray-200 rounded-lg p-4">
    <summary class="font-semibold cursor-pointer">Comment contacter le support ?</summary>
    <p class="mt-2 text-gray-600">Contactez-nous via le formulaire de contact ou sur WhatsApp au +226 70 00 00 00.</p>
  </details>
</div>
HTML,
            ]
        );
    }
}
