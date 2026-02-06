<?php

namespace Tests\Unit\Enums;

use App\Enums\UserStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use Tests\TestCase;

/**
 * Tests pour les Enums UserStatus, PaymentStatus, PaymentMethod, UserRole
 */
class StatusEnumsTest extends TestCase
{
    // ==================== UserStatus Tests ====================

    public function test_user_status_pending_value(): void
    {
        $this->assertEquals('pending', UserStatus::PENDING->value);
    }

    public function test_user_status_active_value(): void
    {
        $this->assertEquals('active', UserStatus::ACTIVE->value);
    }

    public function test_user_status_suspended_value(): void
    {
        $this->assertEquals('suspended', UserStatus::SUSPENDED->value);
    }

    public function test_user_status_rejected_value(): void
    {
        $this->assertEquals('rejected', UserStatus::REJECTED->value);
    }

    public function test_user_status_pending_label(): void
    {
        $this->assertEquals('En attente', UserStatus::PENDING->label());
    }

    public function test_user_status_active_label(): void
    {
        $this->assertEquals('Actif', UserStatus::ACTIVE->label());
    }

    public function test_user_status_suspended_label(): void
    {
        $this->assertEquals('Suspendu', UserStatus::SUSPENDED->label());
    }

    public function test_user_status_rejected_label(): void
    {
        $this->assertEquals('Rejeté', UserStatus::REJECTED->label());
    }

    public function test_user_status_colors(): void
    {
        $this->assertEquals('warning', UserStatus::PENDING->color());
        $this->assertEquals('success', UserStatus::ACTIVE->color());
        $this->assertEquals('danger', UserStatus::SUSPENDED->color());
        $this->assertEquals('gray', UserStatus::REJECTED->color());
    }

    public function test_user_status_cases_count(): void
    {
        $this->assertCount(4, UserStatus::cases());
    }

    public function test_user_status_all_have_labels(): void
    {
        foreach (UserStatus::cases() as $status) {
            $this->assertNotEmpty($status->label());
        }
    }

    public function test_user_status_all_have_colors(): void
    {
        foreach (UserStatus::cases() as $status) {
            $this->assertNotEmpty($status->color());
        }
    }

    // ==================== PaymentStatus Tests ====================

    public function test_payment_status_pending_value(): void
    {
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
    }

    public function test_payment_status_success_value(): void
    {
        $this->assertEquals('success', PaymentStatus::SUCCESS->value);
    }

    public function test_payment_status_failed_value(): void
    {
        $this->assertEquals('failed', PaymentStatus::FAILED->value);
    }

    public function test_payment_status_pending_label(): void
    {
        $this->assertEquals('En attente', PaymentStatus::PENDING->label());
    }

    public function test_payment_status_success_label(): void
    {
        $this->assertEquals('Réussi', PaymentStatus::SUCCESS->label());
    }

    public function test_payment_status_failed_label(): void
    {
        $this->assertEquals('Échoué', PaymentStatus::FAILED->label());
    }

    public function test_payment_status_colors(): void
    {
        $this->assertEquals('warning', PaymentStatus::PENDING->color());
        $this->assertEquals('success', PaymentStatus::SUCCESS->color());
        $this->assertEquals('danger', PaymentStatus::FAILED->color());
    }

    public function test_payment_status_cases_count(): void
    {
        $this->assertCount(3, PaymentStatus::cases());
    }

    // ==================== PaymentMethod Tests ====================

    public function test_payment_method_cash_value(): void
    {
        $this->assertEquals('cash', PaymentMethod::CASH->value);
    }

    public function test_payment_method_orange_money_value(): void
    {
        $this->assertEquals('orange_money', PaymentMethod::ORANGE_MONEY->value);
    }

    public function test_payment_method_moov_money_value(): void
    {
        $this->assertEquals('moov_money', PaymentMethod::MOOV_MONEY->value);
    }

    public function test_payment_method_cases_count(): void
    {
        $this->assertCount(3, PaymentMethod::cases());
    }

    public function test_payment_method_from_valid(): void
    {
        $method = PaymentMethod::from('cash');
        $this->assertEquals(PaymentMethod::CASH, $method);
    }

    public function test_payment_method_try_from_invalid(): void
    {
        $method = PaymentMethod::tryFrom('credit_card');
        $this->assertNull($method);
    }

    public function test_payment_method_labels(): void
    {
        $this->assertEquals('Espèces', PaymentMethod::CASH->label());
        $this->assertEquals('Orange Money', PaymentMethod::ORANGE_MONEY->label());
        $this->assertEquals('Moov Money', PaymentMethod::MOOV_MONEY->label());
    }

    // ==================== UserRole Tests ====================

    public function test_user_role_client_value(): void
    {
        $this->assertEquals('client', UserRole::CLIENT->value);
    }

    public function test_user_role_courier_value(): void
    {
        $this->assertEquals('courier', UserRole::COURIER->value);
    }

    public function test_user_role_admin_value(): void
    {
        $this->assertEquals('admin', UserRole::ADMIN->value);
    }

    public function test_user_role_cases_count(): void
    {
        $this->assertCount(3, UserRole::cases());
    }

    public function test_user_role_from_valid(): void
    {
        $role = UserRole::from('admin');
        $this->assertEquals(UserRole::ADMIN, $role);
    }

    public function test_user_role_try_from_invalid(): void
    {
        $role = UserRole::tryFrom('superadmin');
        $this->assertNull($role);
    }

    // ==================== Cross-Enum Tests ====================

    public function test_all_enums_values_are_snake_case(): void
    {
        $allEnums = [
            UserStatus::cases(),
            PaymentStatus::cases(),
            PaymentMethod::cases(),
            UserRole::cases(),
        ];

        foreach ($allEnums as $enumCases) {
            foreach ($enumCases as $case) {
                $this->assertMatchesRegularExpression(
                    '/^[a-z_]+$/',
                    $case->value,
                    "Enum value {$case->value} should be snake_case"
                );
            }
        }
    }

    public function test_user_status_and_payment_status_pending_are_same_value(): void
    {
        // Les deux utilisent 'pending' - vérifier la cohérence
        $this->assertEquals(UserStatus::PENDING->value, PaymentStatus::PENDING->value);
    }
}
