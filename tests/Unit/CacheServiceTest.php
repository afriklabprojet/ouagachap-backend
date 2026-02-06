<?php

namespace Tests\Unit;

use App\Services\CacheService;
use App\Models\Zone;
use App\Models\Faq;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CacheService();
        Cache::flush();
    }

    // ==========================================
    // Tests pour les Zones
    // ==========================================

    public function test_get_active_zones_returns_only_active_zones(): void
    {
        Zone::factory()->create(['is_active' => true, 'name' => 'Zone Active']);
        Zone::factory()->create(['is_active' => false, 'name' => 'Zone Inactive']);

        $zones = $this->service->getActiveZones();

        $this->assertCount(1, $zones);
        $this->assertEquals('Zone Active', $zones->first()->name);
    }

    public function test_get_active_zones_caches_result(): void
    {
        Zone::factory()->create(['is_active' => true, 'name' => 'Zone A']);

        // Premier appel - met en cache
        $zones1 = $this->service->getActiveZones();

        // Ajouter une nouvelle zone (ne sera pas visible car en cache)
        Zone::factory()->create(['is_active' => true, 'name' => 'Zone B']);

        // Deuxième appel - depuis le cache
        $zones2 = $this->service->getActiveZones();

        $this->assertCount(1, $zones2);
        $this->assertTrue(Cache::has('zones:active'));
    }

    public function test_get_zone_by_id(): void
    {
        $zone = Zone::factory()->create(['name' => 'Zone Test']);

        $cachedZone = $this->service->getZone($zone->id);

        $this->assertEquals('Zone Test', $cachedZone->name);
        $this->assertTrue(Cache::has("zones:{$zone->id}"));
    }

    public function test_get_zone_returns_null_for_nonexistent_id(): void
    {
        $result = $this->service->getZone(99999);

        $this->assertNull($result);
    }

    public function test_clear_zones_cache(): void
    {
        Zone::factory()->create(['is_active' => true]);
        
        // Remplir le cache
        $this->service->getActiveZones();
        $this->assertTrue(Cache::has('zones:active'));

        // Vider le cache des zones
        $this->service->clearZonesCache();

        $this->assertFalse(Cache::has('zones:active'));
    }

    // ==========================================
    // Tests pour les FAQs
    // ==========================================

    public function test_get_active_faqs_returns_only_active_and_ordered(): void
    {
        Faq::factory()->create(['is_active' => true, 'order' => 2, 'question' => 'Q2']);
        Faq::factory()->create(['is_active' => true, 'order' => 1, 'question' => 'Q1']);
        Faq::factory()->create(['is_active' => false, 'order' => 0, 'question' => 'Q Inactive']);

        $faqs = $this->service->getActiveFaqs();

        $this->assertCount(2, $faqs);
        $this->assertEquals('Q1', $faqs->first()->question);
    }

    public function test_get_active_faqs_caches_result(): void
    {
        Faq::factory()->create(['is_active' => true]);

        $this->service->getActiveFaqs();

        $this->assertTrue(Cache::has('faqs:active'));
    }

    public function test_get_faqs_by_category(): void
    {
        Faq::factory()->create(['is_active' => true, 'category' => 'paiement', 'question' => 'Q Paiement']);
        Faq::factory()->create(['is_active' => true, 'category' => 'livraison', 'question' => 'Q Livraison']);

        $paymentFaqs = $this->service->getFaqsByCategory('paiement');

        $this->assertCount(1, $paymentFaqs);
        $this->assertEquals('Q Paiement', $paymentFaqs->first()->question);
    }

    public function test_get_faqs_by_category_caches_result(): void
    {
        Faq::factory()->create(['is_active' => true, 'category' => 'test']);

        $this->service->getFaqsByCategory('test');

        $this->assertTrue(Cache::has('faqs:category:test'));
    }

    public function test_clear_faqs_cache(): void
    {
        Faq::factory()->create(['is_active' => true, 'category' => 'test']);

        // Remplir le cache
        $this->service->getActiveFaqs();
        $this->service->getFaqsByCategory('test');

        $this->assertTrue(Cache::has('faqs:active'));

        // Vider le cache des FAQs
        $this->service->clearFaqsCache();

        $this->assertFalse(Cache::has('faqs:active'));
        $this->assertFalse(Cache::has('faqs:category:test'));
    }

    // ==========================================
    // Tests pour la Configuration (utilise config(), pas de model)
    // ==========================================

    public function test_get_general_config_returns_expected_keys(): void
    {
        $config = $this->service->getGeneralConfig();

        $this->assertArrayHasKey('app_name', $config);
        $this->assertArrayHasKey('currency', $config);
        $this->assertArrayHasKey('support_phone', $config);
        $this->assertArrayHasKey('support_email', $config);
        $this->assertArrayHasKey('commission_rate', $config);
        $this->assertArrayHasKey('min_order_amount', $config);
    }

    public function test_get_general_config_caches_result(): void
    {
        $this->service->getGeneralConfig();

        $this->assertTrue(Cache::has('config:general'));
    }

    public function test_get_contact_info_returns_expected_keys(): void
    {
        $contact = $this->service->getContactInfo();

        $this->assertArrayHasKey('phone', $contact);
        $this->assertArrayHasKey('email', $contact);
        $this->assertArrayHasKey('address', $contact);
        $this->assertArrayHasKey('social', $contact);
        $this->assertIsArray($contact['social']);
    }

    public function test_get_contact_info_caches_result(): void
    {
        $this->service->getContactInfo();

        $this->assertTrue(Cache::has('config:contact'));
    }

    public function test_clear_config_cache(): void
    {
        // Remplir le cache
        $this->service->getGeneralConfig();
        $this->service->getContactInfo();

        $this->assertTrue(Cache::has('config:general'));
        $this->assertTrue(Cache::has('config:contact'));

        // Vider le cache
        $this->service->clearConfigCache();

        $this->assertFalse(Cache::has('config:general'));
        $this->assertFalse(Cache::has('config:contact'));
    }

    // ==========================================
    // Tests pour les SiteSettings
    // ==========================================

    public function test_get_site_settings_returns_key_value_array(): void
    {
        SiteSetting::factory()->create(['key' => 'site_title', 'value' => 'OuagaChap']);
        SiteSetting::factory()->create(['key' => 'site_description', 'value' => 'Livraison rapide']);

        $settings = $this->service->getSiteSettings();

        $this->assertIsArray($settings);
        $this->assertArrayHasKey('site_title', $settings);
        $this->assertEquals('OuagaChap', $settings['site_title']);
    }

    public function test_get_site_settings_caches_result(): void
    {
        SiteSetting::factory()->create(['key' => 'test_key', 'value' => 'value']);

        $this->service->getSiteSettings();

        $this->assertTrue(Cache::has('site_settings:all'));
    }

    public function test_get_landing_settings_returns_key_value_array(): void
    {
        SiteSetting::factory()->create(['key' => 'hero_title', 'value' => 'Bienvenue']);
        SiteSetting::factory()->create(['key' => 'cta_text', 'value' => 'Commander']);

        $landing = $this->service->getLandingSettings();

        $this->assertIsArray($landing);
        $this->assertArrayHasKey('hero_title', $landing);
        $this->assertEquals('Bienvenue', $landing['hero_title']);
    }

    public function test_get_landing_settings_decodes_json_values(): void
    {
        SiteSetting::factory()->create([
            'key' => 'features',
            'value' => json_encode([
                ['title' => 'Rapide', 'description' => 'Livraison en 30min'],
                ['title' => 'Sécurisé', 'description' => 'Paiement sécurisé'],
            ]),
        ]);

        $landing = $this->service->getLandingSettings();

        $this->assertIsArray($landing['features']);
        $this->assertCount(2, $landing['features']);
        $this->assertEquals('Rapide', $landing['features'][0]['title']);
    }

    public function test_get_landing_settings_caches_result(): void
    {
        SiteSetting::factory()->create(['key' => 'test_landing', 'value' => 'value']);

        $this->service->getLandingSettings();

        $this->assertTrue(Cache::has('site_settings:landing'));
    }

    public function test_clear_site_settings_via_config_cache(): void
    {
        SiteSetting::factory()->create(['key' => 'test', 'value' => 'value']);

        // Remplir le cache
        $this->service->getSiteSettings();
        $this->service->getLandingSettings();

        $this->assertTrue(Cache::has('site_settings:all'));
        $this->assertTrue(Cache::has('site_settings:landing'));

        // Vider le cache - utilise clearConfigCache car clearSiteSettingsCache n'existe pas
        $this->service->clearConfigCache();

        $this->assertFalse(Cache::has('site_settings:all'));
        $this->assertFalse(Cache::has('site_settings:landing'));
    }

    // ==========================================
    // Tests pour clearAll et warmUp
    // ==========================================

    public function test_clear_all_caches(): void
    {
        // Créer des données
        Zone::factory()->create(['is_active' => true]);
        Faq::factory()->create(['is_active' => true]);

        // Remplir les caches
        $this->service->getActiveZones();
        $this->service->getActiveFaqs();
        $this->service->getGeneralConfig();

        $this->assertTrue(Cache::has('zones:active'));
        $this->assertTrue(Cache::has('faqs:active'));
        $this->assertTrue(Cache::has('config:general'));

        // Vider tous les caches
        $this->service->clearAll();

        $this->assertFalse(Cache::has('zones:active'));
        $this->assertFalse(Cache::has('faqs:active'));
        $this->assertFalse(Cache::has('config:general'));
    }

    public function test_warm_up_populates_core_caches(): void
    {
        // Créer des données
        Zone::factory()->create(['is_active' => true]);
        Faq::factory()->create(['is_active' => true]);

        // Vérifier que les caches sont vides
        $this->assertFalse(Cache::has('zones:active'));
        $this->assertFalse(Cache::has('faqs:active'));

        // Préchauffer les caches
        $this->service->warmUp();

        // Vérifier que les caches principaux sont remplis
        $this->assertTrue(Cache::has('zones:active'));
        $this->assertTrue(Cache::has('faqs:active'));
        $this->assertTrue(Cache::has('config:general'));
        $this->assertTrue(Cache::has('config:contact'));
    }

    // ==========================================
    // Tests de performance et edge cases
    // ==========================================

    public function test_empty_zones_returns_empty_collection(): void
    {
        $zones = $this->service->getActiveZones();

        $this->assertCount(0, $zones);
    }

    public function test_empty_faqs_returns_empty_collection(): void
    {
        $faqs = $this->service->getActiveFaqs();

        $this->assertCount(0, $faqs);
    }

    public function test_empty_site_settings_returns_empty_array(): void
    {
        $settings = $this->service->getSiteSettings();

        $this->assertIsArray($settings);
        $this->assertEmpty($settings);
    }

    public function test_general_config_always_has_defaults(): void
    {
        // Même sans aucune donnée, getGeneralConfig retourne des valeurs par défaut
        $config = $this->service->getGeneralConfig();

        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
        $this->assertEquals('XOF', $config['currency']);
    }

    public function test_multiple_cache_reads_return_same_data(): void
    {
        Zone::factory()->create(['is_active' => true, 'name' => 'Test Zone']);

        $zones1 = $this->service->getActiveZones();
        $zones2 = $this->service->getActiveZones();
        $zones3 = $this->service->getActiveZones();

        $this->assertEquals($zones1->first()->name, $zones2->first()->name);
        $this->assertEquals($zones2->first()->name, $zones3->first()->name);
    }

    public function test_zones_ordered_by_name(): void
    {
        Zone::factory()->create(['is_active' => true, 'name' => 'Zone C']);
        Zone::factory()->create(['is_active' => true, 'name' => 'Zone A']);
        Zone::factory()->create(['is_active' => true, 'name' => 'Zone B']);

        $zones = $this->service->getActiveZones();

        $this->assertEquals('Zone A', $zones->first()->name);
        $this->assertEquals('Zone C', $zones->last()->name);
    }
}
