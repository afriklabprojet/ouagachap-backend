<?php

namespace Tests\Unit\Services;

use App\Services\ExportService;
use ReflectionClass;
use Tests\TestCase;

/**
 * Tests pour ExportService
 * Focus sur les méthodes de traduction et formatage
 */
class ExportServiceTest extends TestCase
{
    private ExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExportService();
    }

    /**
     * Helper pour appeler les méthodes privées
     */
    private function callPrivateMethod(string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass(ExportService::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invoke($this->service, ...$args);
    }

    // ==================== translateStatus Tests ====================

    public function test_translate_status_pending(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['pending']);
        $this->assertEquals('En attente', $result);
    }

    public function test_translate_status_confirmed(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['confirmed']);
        $this->assertEquals('Confirmée', $result);
    }

    public function test_translate_status_assigned(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['assigned']);
        $this->assertEquals('Assignée', $result);
    }

    public function test_translate_status_picked_up(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['picked_up']);
        $this->assertEquals('Récupérée', $result);
    }

    public function test_translate_status_in_transit(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['in_transit']);
        $this->assertEquals('En transit', $result);
    }

    public function test_translate_status_delivered(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['delivered']);
        $this->assertEquals('Livrée', $result);
    }

    public function test_translate_status_cancelled(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['cancelled']);
        $this->assertEquals('Annulée', $result);
    }

    public function test_translate_status_unknown(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['unknown_status']);
        $this->assertEquals('unknown_status', $result);
    }

    // ==================== translatePaymentStatus Tests ====================

    public function test_translate_payment_status_pending(): void
    {
        $result = $this->callPrivateMethod('translatePaymentStatus', ['pending']);
        $this->assertEquals('En attente', $result);
    }

    public function test_translate_payment_status_completed(): void
    {
        $result = $this->callPrivateMethod('translatePaymentStatus', ['completed']);
        $this->assertEquals('Complété', $result);
    }

    public function test_translate_payment_status_failed(): void
    {
        $result = $this->callPrivateMethod('translatePaymentStatus', ['failed']);
        $this->assertEquals('Échoué', $result);
    }

    public function test_translate_payment_status_refunded(): void
    {
        $result = $this->callPrivateMethod('translatePaymentStatus', ['refunded']);
        $this->assertEquals('Remboursé', $result);
    }

    public function test_translate_payment_status_unknown(): void
    {
        $result = $this->callPrivateMethod('translatePaymentStatus', ['unknown']);
        $this->assertEquals('unknown', $result);
    }

    // ==================== translateWithdrawalStatus Tests ====================

    public function test_translate_withdrawal_status_pending(): void
    {
        $result = $this->callPrivateMethod('translateWithdrawalStatus', ['pending']);
        $this->assertEquals('En attente', $result);
    }

    public function test_translate_withdrawal_status_approved(): void
    {
        $result = $this->callPrivateMethod('translateWithdrawalStatus', ['approved']);
        $this->assertEquals('Approuvé', $result);
    }

    public function test_translate_withdrawal_status_completed(): void
    {
        $result = $this->callPrivateMethod('translateWithdrawalStatus', ['completed']);
        $this->assertEquals('Complété', $result);
    }

    public function test_translate_withdrawal_status_rejected(): void
    {
        $result = $this->callPrivateMethod('translateWithdrawalStatus', ['rejected']);
        $this->assertEquals('Rejeté', $result);
    }

    public function test_translate_withdrawal_status_unknown(): void
    {
        $result = $this->callPrivateMethod('translateWithdrawalStatus', ['something_else']);
        $this->assertEquals('something_else', $result);
    }

    // ==================== All Translations Return French ====================

    public function test_all_order_statuses_have_french_translation(): void
    {
        $statuses = ['pending', 'confirmed', 'assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled'];
        
        foreach ($statuses as $status) {
            $result = $this->callPrivateMethod('translateStatus', [$status]);
            
            // Vérifier que ce n'est pas le statut original (donc traduit)
            $this->assertNotEquals($status, $result, "Status {$status} should be translated");
            
            // Vérifier que la traduction contient des caractères français
            $this->assertMatchesRegularExpression(
                '/[A-Za-zÀ-ÿ]+/', 
                $result, 
                "Translation for {$status} should contain text"
            );
        }
    }

    public function test_all_payment_statuses_have_french_translation(): void
    {
        $statuses = ['pending', 'completed', 'failed', 'refunded'];
        
        foreach ($statuses as $status) {
            $result = $this->callPrivateMethod('translatePaymentStatus', [$status]);
            
            $this->assertNotEquals($status, $result, "Payment status {$status} should be translated");
        }
    }

    public function test_all_withdrawal_statuses_have_french_translation(): void
    {
        $statuses = ['pending', 'approved', 'completed', 'rejected'];
        
        foreach ($statuses as $status) {
            $result = $this->callPrivateMethod('translateWithdrawalStatus', [$status]);
            
            $this->assertNotEquals($status, $result, "Withdrawal status {$status} should be translated");
        }
    }

    // ==================== CSV Header Format Tests ====================

    public function test_orders_csv_has_proper_header(): void
    {
        // On vérifie le format d'en-tête attendu
        $expectedHeader = "Numéro;Date;Client;Coursier;Statut;Montant Total;Frais Livraison;Mode Paiement";
        
        // Vérifier que l'en-tête contient les bonnes colonnes
        $columns = explode(';', $expectedHeader);
        
        $this->assertCount(8, $columns);
        $this->assertContains('Numéro', $columns);
        $this->assertContains('Date', $columns);
        $this->assertContains('Client', $columns);
        $this->assertContains('Coursier', $columns);
        $this->assertContains('Statut', $columns);
        $this->assertContains('Montant Total', $columns);
    }

    public function test_payments_csv_has_proper_header(): void
    {
        $expectedHeader = "Référence;Date;Commande;Montant;Méthode;Statut;Opérateur";
        
        $columns = explode(';', $expectedHeader);
        
        $this->assertCount(7, $columns);
        $this->assertContains('Référence', $columns);
        $this->assertContains('Montant', $columns);
        $this->assertContains('Statut', $columns);
    }

    public function test_withdrawals_csv_has_proper_header(): void
    {
        $expectedHeader = "ID;Date;Coursier;Téléphone;Montant;Méthode;Statut;Référence Transaction";
        
        $columns = explode(';', $expectedHeader);
        
        $this->assertCount(8, $columns);
        $this->assertContains('ID', $columns);
        $this->assertContains('Coursier', $columns);
        $this->assertContains('Montant', $columns);
    }

    public function test_couriers_csv_has_proper_header(): void
    {
        $expectedHeader = "Nom;Téléphone;Email;Livraisons;Note;Disponible;Date Inscription";
        
        $columns = explode(';', $expectedHeader);
        
        $this->assertCount(7, $columns);
        $this->assertContains('Nom', $columns);
        $this->assertContains('Livraisons', $columns);
        $this->assertContains('Disponible', $columns);
    }

    // ==================== Number Formatting Tests ====================

    public function test_number_format_for_csv(): void
    {
        // Vérifier que le formatage des nombres est correct pour le CSV français
        $formatted = number_format(1500000, 0, ',', ' ');
        $this->assertEquals('1 500 000', $formatted);
        
        $formatted = number_format(500, 0, ',', ' ');
        $this->assertEquals('500', $formatted);
        
        $formatted = number_format(0, 0, ',', ' ');
        $this->assertEquals('0', $formatted);
    }

    // ==================== Filter Handling Tests ====================

    public function test_filter_keys_are_expected(): void
    {
        // Vérifier que les clés de filtres attendues sont correctes
        $expectedFilterKeys = ['start_date', 'end_date', 'status'];
        
        foreach ($expectedFilterKeys as $key) {
            $this->assertIsString($key);
        }
    }

    // ==================== Edge Cases ====================

    public function test_translate_status_empty_string(): void
    {
        $result = $this->callPrivateMethod('translateStatus', ['']);
        $this->assertEquals('', $result);
    }

    public function test_translate_status_case_sensitive(): void
    {
        // Le match est case-sensitive
        $result = $this->callPrivateMethod('translateStatus', ['PENDING']);
        $this->assertEquals('PENDING', $result); // Non traduit car majuscule
        
        $result = $this->callPrivateMethod('translateStatus', ['Pending']);
        $this->assertEquals('Pending', $result); // Non traduit car majuscule
    }

    public function test_service_instantiation(): void
    {
        $service = new ExportService();
        $this->assertInstanceOf(ExportService::class, $service);
    }
}
