<?php

namespace Tests\Unit;

use App\Services\WalletService;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService();
    }

    // ==========================================
    // Tests pour getOrCreateWallet
    // ==========================================

    public function test_get_or_create_wallet_creates_new_wallet(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);

        $wallet = $this->service->getOrCreateWallet($courier);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals($courier->id, $wallet->user_id);
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals(0, $wallet->pending_balance);
        $this->assertEquals(0, $wallet->total_earned);
        $this->assertEquals(0, $wallet->total_withdrawn);
    }

    public function test_get_or_create_wallet_returns_existing_wallet(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $existingWallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 0,
            'total_earned' => 5000,
            'total_withdrawn' => 0,
        ]);

        $wallet = $this->service->getOrCreateWallet($courier);

        $this->assertEquals($existingWallet->id, $wallet->id);
        $this->assertEquals(5000, $wallet->balance);
    }

    // ==========================================
    // Tests pour creditCourierForDelivery
    // ==========================================

    public function test_credit_courier_for_delivery(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 0,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);

        $wallet = $this->service->creditCourierForDelivery($courier, 1500);

        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(1500, $wallet->balance);
        $this->assertEquals(1500, $wallet->total_earned);
    }

    public function test_credit_courier_creates_wallet_if_not_exists(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);

        $wallet = $this->service->creditCourierForDelivery($courier, 2000);

        $this->assertNotNull($wallet);
        $this->assertEquals(2000, $wallet->balance);
    }

    public function test_multiple_credits_accumulate(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 1000,
            'pending_balance' => 0,
            'total_earned' => 1000,
            'total_withdrawn' => 0,
        ]);

        $this->service->creditCourierForDelivery($courier, 500);
        $this->service->creditCourierForDelivery($courier, 700);

        $wallet = Wallet::where('user_id', $courier->id)->first();
        $this->assertEquals(2200, $wallet->balance);
        $this->assertEquals(2200, $wallet->total_earned);
    }

    // ==========================================
    // Tests pour requestWithdrawal
    // ==========================================

    public function test_request_withdrawal_success(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = $this->service->requestWithdrawal($courier, 5000, 'mobile_money', [
            'phone' => '70000000',
            'provider' => 'orange_money',
        ]);

        $this->assertInstanceOf(Withdrawal::class, $withdrawal);
        $this->assertEquals(5000, $withdrawal->amount);
        $this->assertEquals('pending', $withdrawal->status);
        $this->assertEquals('mobile_money', $withdrawal->payment_method);
        $this->assertEquals('70000000', $withdrawal->payment_phone);
        $this->assertEquals('orange_money', $withdrawal->payment_provider);
    }

    public function test_request_withdrawal_fails_with_insufficient_balance(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 1000,
            'pending_balance' => 0,
            'total_earned' => 1000,
            'total_withdrawn' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solde insuffisant');

        $this->service->requestWithdrawal($courier, 5000, 'mobile_money', ['phone' => '70000000']);
    }

    public function test_request_withdrawal_fails_below_minimum_amount(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('minimum');

        $this->service->requestWithdrawal($courier, 200, 'mobile_money', ['phone' => '70000000']);
    }

    public function test_request_withdrawal_debits_wallet_to_pending(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $this->service->requestWithdrawal($courier, 5000, 'mobile_money', ['phone' => '70000000']);

        $wallet = Wallet::where('user_id', $courier->id)->first();
        $this->assertEquals(5000, $wallet->balance); // 10000 - 5000
        $this->assertEquals(5000, $wallet->pending_balance);
    }

    // ==========================================
    // Tests pour approveWithdrawal
    // ==========================================

    public function test_approve_withdrawal(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $admin = User::factory()->create(['role' => 'admin']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 5000,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $this->service->approveWithdrawal($withdrawal, $admin);

        $withdrawal->refresh();
        $this->assertEquals('approved', $withdrawal->status);
        $this->assertEquals($admin->id, $withdrawal->approved_by);
        $this->assertNotNull($withdrawal->approved_at);
    }

    public function test_approve_withdrawal_fails_if_not_pending(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $admin = User::factory()->create(['role' => 'admin']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 5000,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('approuvé');

        $this->service->approveWithdrawal($withdrawal, $admin);
    }

    // ==========================================
    // Tests pour rejectWithdrawal
    // ==========================================

    public function test_reject_withdrawal(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $admin = User::factory()->create(['role' => 'admin']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 5000,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $this->service->rejectWithdrawal($withdrawal, 'Documents invalides', $admin);

        $withdrawal->refresh();
        $this->assertEquals('rejected', $withdrawal->status);
        $this->assertEquals('Documents invalides', $withdrawal->rejection_reason);
        
        // Vérifier que le montant est retourné au solde disponible
        $wallet->refresh();
        $this->assertEquals(10000, $wallet->balance);
        $this->assertEquals(0, $wallet->pending_balance);
    }

    public function test_reject_withdrawal_fails_if_not_pending(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $admin = User::factory()->create(['role' => 'admin']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'approved',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('rejeté');

        $this->service->rejectWithdrawal($withdrawal, 'Test', $admin);
    }

    // ==========================================
    // Tests pour completeWithdrawal
    // ==========================================

    public function test_complete_withdrawal(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 0,
            'pending_balance' => 5000,
            'total_earned' => 5000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'approved',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $this->service->completeWithdrawal($withdrawal, 'TX123456');

        $withdrawal->refresh();
        $this->assertEquals('completed', $withdrawal->status);
        $this->assertEquals('TX123456', $withdrawal->transaction_reference);
        $this->assertNotNull($withdrawal->completed_at);

        // Vérifier que le pending_balance est vidé et total_withdrawn augmenté
        $wallet->refresh();
        $this->assertEquals(0, $wallet->pending_balance);
        $this->assertEquals(5000, $wallet->total_withdrawn);
    }

    public function test_complete_withdrawal_fails_if_not_approved(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 5000,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 5000,
            'status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('approuvé');

        $this->service->completeWithdrawal($withdrawal, 'TX123456');
    }

    // ==========================================
    // Tests pour getWithdrawalHistory
    // ==========================================

    public function test_get_withdrawal_history(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 1000,
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);
        Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 2000,
            'status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_phone' => '71000000',
        ]);

        $history = $this->service->getWithdrawalHistory($courier);

        $this->assertCount(2, $history);
    }

    public function test_get_withdrawal_history_by_status(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 1000,
            'status' => 'completed',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);
        Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 2000,
            'status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_phone' => '71000000',
        ]);

        $pendingHistory = $this->service->getWithdrawalHistory($courier, 'pending');

        $this->assertCount(1, $pendingHistory);
        $this->assertEquals('pending', $pendingHistory->first()->status);
    }

    // ==========================================
    // Tests pour getWalletStats
    // ==========================================

    public function test_get_wallet_stats(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        $wallet = Wallet::create([
            'user_id' => $courier->id,
            'balance' => 15000,
            'pending_balance' => 2000,
            'total_earned' => 20000,
            'total_withdrawn' => 3000,
        ]);

        // Créer un retrait en attente
        Withdrawal::create([
            'user_id' => $courier->id,
            'wallet_id' => $wallet->id,
            'amount' => 2000,
            'status' => 'pending',
            'payment_method' => 'mobile_money',
            'payment_phone' => '70000000',
        ]);

        $stats = $this->service->getWalletStats($courier);

        $this->assertArrayHasKey('balance', $stats);
        $this->assertArrayHasKey('pending_balance', $stats);
        $this->assertArrayHasKey('total_earned', $stats);
        $this->assertArrayHasKey('total_withdrawn', $stats);
        $this->assertArrayHasKey('available_for_withdrawal', $stats);
        $this->assertArrayHasKey('pending_withdrawals_count', $stats);
        $this->assertArrayHasKey('pending_withdrawals_amount', $stats);
        
        $this->assertEquals(15000, $stats['balance']);
        $this->assertEquals(20000, $stats['total_earned']);
        $this->assertEquals(3000, $stats['total_withdrawn']);
        $this->assertEquals(1, $stats['pending_withdrawals_count']);
        $this->assertEquals(2000, $stats['pending_withdrawals_amount']);
    }

    public function test_get_wallet_stats_empty_wallet(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);

        $stats = $this->service->getWalletStats($courier);

        $this->assertEquals(0, $stats['balance']);
        $this->assertEquals(0, $stats['total_earned']);
        $this->assertEquals(0, $stats['total_withdrawn']);
    }

    // ==========================================
    // Tests edge cases
    // ==========================================

    public function test_withdrawal_exactly_at_minimum(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = $this->service->requestWithdrawal($courier, 500, 'mobile_money', ['phone' => '70000000']);

        $this->assertEquals(500, $withdrawal->amount);
    }

    public function test_withdrawal_exactly_at_balance(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 5000,
            'pending_balance' => 0,
            'total_earned' => 5000,
            'total_withdrawn' => 0,
        ]);

        $withdrawal = $this->service->requestWithdrawal($courier, 5000, 'mobile_money', ['phone' => '70000000']);

        $this->assertEquals(5000, $withdrawal->amount);
        
        $wallet = Wallet::where('user_id', $courier->id)->first();
        $this->assertEquals(0, $wallet->balance);
        $this->assertEquals(5000, $wallet->pending_balance);
    }

    public function test_multiple_pending_withdrawals_accumulate(): void
    {
        $courier = User::factory()->create(['role' => 'courier']);
        Wallet::create([
            'user_id' => $courier->id,
            'balance' => 10000,
            'pending_balance' => 0,
            'total_earned' => 10000,
            'total_withdrawn' => 0,
        ]);

        $this->service->requestWithdrawal($courier, 2000, 'mobile_money', ['phone' => '70000000']);
        $this->service->requestWithdrawal($courier, 3000, 'mobile_money', ['phone' => '70000000']);

        $stats = $this->service->getWalletStats($courier);

        $this->assertEquals(2, $stats['pending_withdrawals_count']);
        $this->assertEquals(5000, $stats['pending_withdrawals_amount']);
        $this->assertEquals(5000, $stats['pending_balance']);
    }
}
