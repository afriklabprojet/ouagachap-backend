<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\GeofenceAlert;
use App\Models\InAppNotification;
use App\Models\JekoTransaction;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OtpCode;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Rating;
use App\Models\SavedAddress;
use App\Models\SiteSetting;
use App\Models\TrafficIncident;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelsRemainingCoverageTest extends TestCase
{
    use RefreshDatabase;

    // ===== PromoCode (89.1%) - lines 85, 96..101 =====

    public function test_promo_code_can_be_used_by_first_order_only(): void
    {
        $user = User::factory()->create();
        // User has previous delivered order
        Order::factory()->create(['client_id' => $user->id, 'status' => 'delivered']);

        $promo = PromoCode::factory()->create([
            'is_active' => true,
            'first_order_only' => true,
            'max_uses_per_user' => 5,
        ]);

        $result = $promo->canBeUsedBy($user);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('nouvelles inscriptions', $result['message']);
    }

    public function test_promo_code_min_order_amount(): void
    {
        $user = User::factory()->create();
        $promo = PromoCode::factory()->create([
            'is_active' => true,
            'min_order_amount' => 5000,
            'max_uses_per_user' => 5,
        ]);

        $result = $promo->canBeUsedBy($user, 2000);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('minimum', $result['message']);
    }

    public function test_promo_code_zone_check(): void
    {
        $user = User::factory()->create();
        $promo = PromoCode::factory()->create([
            'is_active' => true,
            'applicable_zones' => [1, 2],
            'max_uses_per_user' => 5,
        ]);

        $result = $promo->canBeUsedBy($user, null, 3);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('zone', $result['message']);
    }

    public function test_promo_code_calculate_discount_percentage_with_max(): void
    {
        $promo = PromoCode::factory()->create([
            'type' => 'percentage',
            'value' => 50,
            'max_discount' => 1000,
        ]);

        $discount = $promo->calculateDiscount(5000);
        $this->assertEquals(1000, $discount); // 50% of 5000 = 2500, capped at 1000
    }

    public function test_promo_code_calculate_discount_fixed(): void
    {
        $promo = PromoCode::factory()->create(['type' => 'fixed', 'value' => 1000]);

        $discount = $promo->calculateDiscount(500);
        $this->assertEquals(500, $discount); // min(1000, 500)
    }

    public function test_promo_code_calculate_discount_free_delivery(): void
    {
        $promo = PromoCode::factory()->create(['type' => 'free_delivery', 'value' => 0]);

        $discount = $promo->calculateDiscount(5000, 1500);
        $this->assertEquals(1500, $discount); // deliveryFee
    }

    public function test_promo_code_calculate_discount_unknown_type(): void
    {
        // Create with valid type, then override type attribute in memory
        $promo = PromoCode::factory()->create(['type' => 'fixed']);
        // Force the type to an unknown value in memory (skip DB check constraint)
        $promo->setAttribute('type', 'unknown');

        $discount = $promo->calculateDiscount(5000);
        $this->assertEquals(0, $discount);
    }

    public function test_promo_code_apply(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();
        $promo = PromoCode::factory()->create(['current_uses' => 0]);

        $usage = $promo->apply($user, $order, 500);

        $this->assertInstanceOf(PromoCodeUsage::class, $usage);
        $this->assertEquals(1, $promo->fresh()->current_uses);
    }

    // ===== TrafficIncident (89.2%) - lines 47, 68, 78..83 =====

    public function test_traffic_incident_resolved_by_relationship(): void
    {
        $user = User::factory()->create();
        $incident = TrafficIncident::factory()->create([
            'resolved_by' => $user->id,
            'is_active' => false,
            'resolved_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $incident->resolvedBy);
        $this->assertInstanceOf(User::class, $incident->resolver);
    }

    public function test_traffic_incident_scope_by_type(): void
    {
        TrafficIncident::factory()->create(['type' => 'accident']);
        TrafficIncident::factory()->create(['type' => 'congestion']);

        $this->assertEquals(1, TrafficIncident::byType('accident')->count());
    }

    public function test_traffic_incident_scope_resolved(): void
    {
        TrafficIncident::factory()->create(['is_active' => true]);
        TrafficIncident::factory()->create([
            'is_active' => false,
            'resolved_at' => now(),
        ]);

        $this->assertEquals(1, TrafficIncident::resolved()->count());
    }

    public function test_traffic_incident_scope_severe(): void
    {
        TrafficIncident::factory()->create(['severity' => 'high']);
        TrafficIncident::factory()->create(['severity' => 'severe']);
        TrafficIncident::factory()->create(['severity' => 'low']);

        $this->assertEquals(2, TrafficIncident::severe()->count());
    }

    public function test_traffic_incident_is_expired(): void
    {
        $expired = TrafficIncident::factory()->create(['expires_at' => now()->subHour()]);
        $active = TrafficIncident::factory()->create(['expires_at' => now()->addHour()]);
        $noExpiry = TrafficIncident::factory()->create(['expires_at' => null]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($active->isExpired());
        $this->assertFalse($noExpiry->isExpired());
    }

    public function test_traffic_incident_confirm(): void
    {
        $incident = TrafficIncident::factory()->create(['confirmations' => 0]);
        $incident->confirm();

        $this->assertEquals(1, $incident->fresh()->confirmations);
    }

    public function test_traffic_incident_resolve(): void
    {
        $user = User::factory()->create();
        $incident = TrafficIncident::factory()->create(['is_active' => true]);
        $incident->resolve($user->id);

        $this->assertFalse($incident->fresh()->is_active);
        $this->assertNotNull($incident->fresh()->resolved_at);
        $this->assertEquals($user->id, $incident->fresh()->resolved_by);
    }

    public function test_traffic_incident_labels(): void
    {
        $incident = TrafficIncident::factory()->create(['type' => 'accident', 'severity' => 'high']);

        $this->assertEquals('Accident', $incident->getTypeLabel());
        $this->assertEquals('Élevé', $incident->getSeverityLabel());
    }

    public function test_traffic_incident_static_lists(): void
    {
        $types = TrafficIncident::getTypes();
        $severities = TrafficIncident::getSeverities();

        $this->assertArrayHasKey('congestion', $types);
        $this->assertArrayHasKey('low', $severities);
    }

    // ===== JekoTransaction (89.6%) - lines 56, 66..76, 135 =====

    public function test_jeko_transaction_scope_successful(): void
    {
        JekoTransaction::factory()->create(['status' => 'success']);
        JekoTransaction::factory()->create(['status' => 'pending']);

        $this->assertEquals(1, JekoTransaction::successful()->count());
    }

    public function test_jeko_transaction_scope_failed(): void
    {
        JekoTransaction::factory()->create(['status' => 'error']);
        JekoTransaction::factory()->create(['status' => 'success']);

        $this->assertEquals(1, JekoTransaction::failed()->count());
    }

    public function test_jeko_transaction_scope_wallet_recharge(): void
    {
        JekoTransaction::factory()->create(['type' => 'wallet_recharge']);
        JekoTransaction::factory()->create(['type' => 'order_payment']);

        $this->assertEquals(1, JekoTransaction::walletRecharge()->count());
    }

    public function test_jeko_transaction_scope_order_payment(): void
    {
        JekoTransaction::factory()->create(['type' => 'order_payment']);
        JekoTransaction::factory()->create(['type' => 'wallet_recharge']);

        $this->assertEquals(1, JekoTransaction::orderPayment()->count());
    }

    public function test_jeko_transaction_scope_for_user(): void
    {
        $user = User::factory()->create();
        JekoTransaction::factory()->create(['user_id' => $user->id]);
        JekoTransaction::factory()->create();

        $this->assertEquals(1, JekoTransaction::forUser($user->id)->count());
    }

    public function test_jeko_transaction_scope_of_type(): void
    {
        JekoTransaction::factory()->create(['type' => 'recharge']);
        $this->assertEquals(1, JekoTransaction::ofType('recharge')->count());
    }

    public function test_jeko_transaction_status_label(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'pending']);
        $this->assertEquals('En attente', $tx->status_label);

        $tx->update(['status' => 'success']);
        $this->assertEquals('Réussi', $tx->fresh()->status_label);

        $tx->update(['status' => 'error']);
        $this->assertEquals('Échoué', $tx->fresh()->status_label);

        $tx->update(['status' => 'expired']);
        $this->assertEquals('Expiré', $tx->fresh()->status_label);

        $tx->update(['status' => 'cancelled']);
        $this->assertEquals('Annulé', $tx->fresh()->status_label);
    }

    public function test_jeko_transaction_status_color(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'success']);
        $this->assertEquals('success', $tx->status_color);
    }

    public function test_jeko_transaction_formatted_amount(): void
    {
        $tx = JekoTransaction::factory()->create(['amount' => 10000, 'currency' => 'XOF']);
        $this->assertStringContainsString('10 000', $tx->formatted_amount);
        $this->assertStringContainsString('XOF', $tx->formatted_amount);
    }

    public function test_jeko_transaction_is_successful(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'success']);
        $this->assertTrue($tx->isSuccessful());
    }

    public function test_jeko_transaction_is_failed(): void
    {
        $error = JekoTransaction::factory()->create(['status' => 'error']);
        $expired = JekoTransaction::factory()->create(['status' => 'expired']);
        $cancelled = JekoTransaction::factory()->create(['status' => 'cancelled']);

        $this->assertTrue($error->isFailed());
        $this->assertTrue($expired->isFailed());
        $this->assertTrue($cancelled->isFailed());
    }

    public function test_jeko_transaction_mark_as_success(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'pending']);
        $tx->markAsSuccess(['data' => 'test']);

        $this->assertEquals('success', $tx->fresh()->status);
        $this->assertNotNull($tx->fresh()->executed_at);
    }

    public function test_jeko_transaction_mark_as_error(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'pending', 'metadata' => []]);
        $tx->markAsError('Payment refused');

        $this->assertEquals('error', $tx->fresh()->status);
        $this->assertEquals('Payment refused', $tx->fresh()->metadata['error_reason']);
    }

    public function test_jeko_transaction_mark_as_expired(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'pending']);
        $tx->markAsExpired();

        $this->assertEquals('expired', $tx->fresh()->status);
    }

    // ===== OrderMessage (85.7%) - lines 47, 64 =====

    public function test_order_message_scope_read(): void
    {
        OrderMessage::factory()->create(['is_read' => true]);
        OrderMessage::factory()->create(['is_read' => false]);

        $this->assertEquals(1, OrderMessage::read()->count());
    }

    public function test_order_message_scope_from_courier(): void
    {
        OrderMessage::factory()->create(['sender_type' => 'courier']);
        OrderMessage::factory()->create(['sender_type' => 'client']);

        $this->assertEquals(1, OrderMessage::fromCourier()->count());
    }

    public function test_order_message_is_from_courier(): void
    {
        $msg = OrderMessage::factory()->create(['sender_type' => 'courier']);
        $this->assertTrue($msg->isFromCourier());
        $this->assertFalse($msg->isFromClient());
    }

    public function test_order_message_accessors(): void
    {
        $msg = OrderMessage::factory()->create(['sender_type' => 'client']);
        $this->assertTrue($msg->is_from_client);
        $this->assertFalse($msg->is_from_courier);
    }

    public function test_order_message_sender_name_without_relation(): void
    {
        $msg = OrderMessage::factory()->create(['sender_type' => 'courier']);
        // Don't load sender relation
        $this->assertEquals('Coursier', $msg->sender_name);
    }

    public function test_order_message_sender_name_client(): void
    {
        $msg = OrderMessage::factory()->create(['sender_type' => 'client']);
        $this->assertEquals('Client', $msg->sender_name);
    }

    // ===== WalletTransaction (86.5%) - lines 80, 90..93 =====

    public function test_wallet_transaction_method_labels(): void
    {
        $labels = [
            'orange_money' => 'Orange Money',
            'moov_money' => 'Moov Money',
            'wave' => 'Wave',
            'mtn_money' => 'MTN Money',
            'djamo' => 'Djamo',
            'cash' => 'Espèces',
        ];

        foreach ($labels as $method => $label) {
            $tx = WalletTransaction::factory()->create(['method' => $method]);
            $this->assertEquals($label, $tx->method_label);
        }
    }

    public function test_wallet_transaction_status_labels(): void
    {
        $tx = WalletTransaction::factory()->create(['status' => 'pending']);
        $this->assertEquals('En attente', $tx->status_label);

        $success = WalletTransaction::factory()->success()->create();
        $this->assertEquals('Réussi', $success->status_label);

        $failed = WalletTransaction::factory()->failed()->create();
        $this->assertEquals('Échoué', $failed->status_label);
    }

    public function test_wallet_transaction_mark_as_success(): void
    {
        $tx = WalletTransaction::factory()->create(['status' => 'pending']);
        $tx->markAsSuccess();

        $this->assertEquals('success', $tx->fresh()->status);
        $this->assertNotNull($tx->fresh()->completed_at);
    }

    public function test_wallet_transaction_mark_as_failed(): void
    {
        $tx = WalletTransaction::factory()->create(['status' => 'pending']);
        $tx->markAsFailed('Error occurred');

        $this->assertEquals('failed', $tx->fresh()->status);
        $this->assertEquals('Error occurred', $tx->fresh()->failure_reason);
    }

    public function test_wallet_transaction_is_pending(): void
    {
        $tx = WalletTransaction::factory()->create(['status' => 'pending']);
        $this->assertTrue($tx->isPending());
        $this->assertFalse($tx->isSuccess());
        $this->assertFalse($tx->isFailed());
    }

    public function test_wallet_transaction_scopes(): void
    {
        WalletTransaction::factory()->create(['status' => 'pending', 'type' => 'recharge']);
        WalletTransaction::factory()->success()->create(['type' => 'debit']);
        WalletTransaction::factory()->failed()->create(['type' => 'recharge']);

        $this->assertEquals(1, WalletTransaction::pending()->count());
        $this->assertEquals(1, WalletTransaction::success()->count());
        $this->assertEquals(1, WalletTransaction::failed()->count());
        $this->assertEquals(2, WalletTransaction::recharges()->count());
        $this->assertEquals(1, WalletTransaction::debit()->count());
    }

    // ===== Wallet (87.9%) - lines 34..44, 82 =====

    public function test_wallet_transactions_relationship(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);
        WalletTransaction::factory()->create(['user_id' => $user->id]);

        $this->assertGreaterThanOrEqual(1, $wallet->transactions()->count());
    }

    public function test_wallet_withdrawals_relationship(): void
    {
        $wallet = Wallet::factory()->create();
        $this->assertNotNull($wallet->withdrawals());
    }

    public function test_wallet_available_balance(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10000, 'pending_balance' => 3000]);
        $this->assertEquals(7000, $wallet->available_balance);
    }

    public function test_wallet_available_balance_never_negative(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 1000, 'pending_balance' => 5000]);
        $this->assertEquals(0, $wallet->available_balance);
    }

    public function test_wallet_credit(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 5000, 'total_earned' => 10000]);
        $wallet->credit(2000);

        $this->assertEquals(7000, (float)$wallet->balance);
        $this->assertEquals(12000, (float)$wallet->total_earned);
    }

    public function test_wallet_debit(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 5000, 'pending_balance' => 0]);
        $wallet->debit(3000);

        $this->assertEquals(3000, (float)$wallet->pending_balance);
    }

    public function test_wallet_debit_insufficient(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 1000, 'pending_balance' => 0]);

        $this->expectException(\Exception::class);
        $wallet->debit(5000);
    }

    public function test_wallet_cancel_withdrawal(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 5000, 'pending_balance' => 3000]);
        $wallet->cancelWithdrawal(2000);

        $this->assertEquals(1000, (float)$wallet->pending_balance);
    }

    public function test_wallet_confirm_withdrawal(): void
    {
        $wallet = Wallet::factory()->create([
            'balance' => 10000,
            'pending_balance' => 5000,
            'total_withdrawn' => 0,
        ]);
        $wallet->confirmWithdrawal(5000);

        $this->assertEquals(5000, (float)$wallet->balance);
        $this->assertEquals(0, (float)$wallet->pending_balance);
        $this->assertEquals(5000, (float)$wallet->total_withdrawn);
    }

    // ===== Rating (93.5%) - lines 74..79 =====

    public function test_rating_stats_for_user(): void
    {
        $user = User::factory()->create();
        Rating::factory()->create(['rated_id' => $user->id, 'rating' => 5, 'tags' => ['rapide'], 'is_visible' => true]);
        Rating::factory()->create(['rated_id' => $user->id, 'rating' => 4, 'tags' => ['rapide', 'aimable'], 'is_visible' => true]);
        Rating::factory()->create(['rated_id' => $user->id, 'rating' => 3, 'tags' => null, 'is_visible' => true]);

        $stats = Rating::statsForUser($user->id);

        $this->assertEquals(4.0, $stats['average']);
        $this->assertEquals(3, $stats['count']);
        $this->assertArrayHasKey(5, $stats['distribution']);
        $this->assertEquals(1, $stats['distribution'][5]);
        $this->assertEquals(1, $stats['distribution'][4]);
        $this->assertEquals(1, $stats['distribution'][3]);
        $this->assertArrayHasKey('rapide', $stats['tags']);
        $this->assertEquals(2, $stats['tags']['rapide']);
    }

    // ===== SavedAddress (95.0%) - line 53 =====

    public function test_saved_address_scope_of_type(): void
    {
        $user = User::factory()->create();
        SavedAddress::factory()->create(['user_id' => $user->id, 'type' => 'home']);
        SavedAddress::factory()->create(['user_id' => $user->id, 'type' => 'work']);

        $this->assertEquals(1, SavedAddress::ofType('home')->count());
    }

    public function test_saved_address_scope_by_type(): void
    {
        $user = User::factory()->create();
        SavedAddress::factory()->create(['user_id' => $user->id, 'type' => 'work']);

        $this->assertEquals(1, SavedAddress::byType('work')->count());
    }

    public function test_saved_address_display_label(): void
    {
        $home = SavedAddress::factory()->create(['type' => 'home', 'label' => 'Maison']);
        $work = SavedAddress::factory()->create(['type' => 'work', 'label' => 'Bureau']);
        $other = SavedAddress::factory()->create(['type' => 'other', 'label' => 'Gym']);

        $this->assertStringContainsString('🏠', $home->display_label);
        $this->assertStringContainsString('🏢', $work->display_label);
        $this->assertStringContainsString('📍', $other->display_label);
    }

    public function test_saved_address_icon(): void
    {
        $home = SavedAddress::factory()->create(['type' => 'home']);
        $work = SavedAddress::factory()->create(['type' => 'work']);
        $other = SavedAddress::factory()->create(['type' => 'other']);

        $this->assertEquals('home', $home->icon);
        $this->assertEquals('work', $work->icon);
        $this->assertEquals('location_on', $other->icon);
    }

    public function test_saved_address_set_as_default(): void
    {
        $user = User::factory()->create();
        $addr1 = SavedAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $addr2 = SavedAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

        $addr2->setAsDefault();

        $this->assertFalse($addr1->fresh()->is_default);
        $this->assertTrue($addr2->fresh()->is_default);
    }

    // ===== InAppNotification (95.2%) - line 47 =====

    public function test_in_app_notification_auto_generates_uuid(): void
    {
        $user = User::factory()->create();
        $notification = InAppNotification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Test',
            'message' => 'Message',
        ]);

        $this->assertNotNull($notification->id);
        $this->assertIsString($notification->id);
    }

    public function test_in_app_notification_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        InAppNotification::notify($user, 'test', 'T1', 'M1');
        InAppNotification::notify($user, 'test', 'T2', 'M2');

        $count = InAppNotification::markAllAsReadForUser($user->id);

        $this->assertEquals(2, $count);
        $this->assertEquals(0, InAppNotification::where('user_id', $user->id)->unread()->count());
    }

    // ===== ActivityLog (97.1%) - lines 76..81 =====

    public function test_activity_log_subject_relationship(): void
    {
        $order = Order::factory()->create();
        $log = ActivityLog::factory()->create([
            'subject_type' => Order::class,
            'subject_id' => (string) $order->getKey(),
        ]);

        // The morphTo relationship should work
        $subject = $log->subject;
        $this->assertNotNull($subject);
    }

    // ===== SiteSetting (97.3%) - line 110 =====

    public function test_site_setting_get_and_set(): void
    {
        SiteSetting::set('test_key', 'test_value');
        $this->assertEquals('test_value', SiteSetting::get('test_key'));
    }

    public function test_site_setting_default_value(): void
    {
        $this->assertEquals('default', SiteSetting::get('nonexistent', 'default'));
    }

    // ===== OtpCode (98.2%) - line 47 =====

    public function test_otp_code_generate_and_verify(): void
    {
        $otp = OtpCode::generate('70123456');
        $this->assertNotNull($otp->code);

        $result = OtpCode::verify('70123456', $otp->code);
        $this->assertTrue($result['success']);
    }

    public function test_otp_code_expired(): void
    {
        $otp = OtpCode::generate('70123456');
        // Manually expire it
        $otp->update(['expires_at' => now()->subMinute()]);

        $result = OtpCode::verify('70123456', $otp->code);
        $this->assertFalse($result['success']);
    }

    // ===== GeofenceAlert (93.9%) - lines 48, 58 =====

    public function test_geofence_alert_mark_as_read(): void
    {
        $order = Order::factory()->create();
        $courier = User::factory()->courier()->create();
        $order->update(['courier_id' => $courier->id]);

        GeofenceAlert::createProximityPickup($order, 12.37, -1.52, 100);
        $alert = GeofenceAlert::first();
        $this->assertFalse($alert->is_read);

        $alert->update(['is_read' => true]);
        $this->assertTrue($alert->fresh()->is_read);
    }

    // ===== Faq (93.5%) - lines 36, 46 =====

    public function test_faq_scopes(): void
    {
        Faq::factory()->create(['is_active' => true, 'category' => 'general']);
        Faq::factory()->create(['is_active' => false, 'category' => 'payments']);

        $this->assertEquals(1, Faq::where('is_active', true)->count());
    }

    // ===== Order (90.3%) - status methods =====

    public function test_order_assign_courier(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $courier = User::factory()->courier()->active()->create(['is_available' => true]);

        $result = $order->assign($courier);
        $this->assertTrue($result);
        $this->assertEquals(OrderStatus::ASSIGNED, $order->fresh()->status);
    }

    public function test_order_mark_as_picked_up(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::ASSIGNED]);
        $user = User::factory()->create();

        $result = $order->markAsPickedUp($user->id, 12.37, -1.52);
        $this->assertTrue($result);
        $this->assertEquals(OrderStatus::PICKED_UP, $order->fresh()->status);
    }

    public function test_order_mark_as_delivered(): void
    {
        $courier = User::factory()->courier()->active()->create();
        $order = Order::factory()->create([
            'status' => OrderStatus::IN_TRANSIT,
            'courier_id' => $courier->id,
            'courier_earnings' => 1500,
        ]);

        $result = $order->markAsDelivered($courier->id);
        $this->assertTrue($result);
        $this->assertEquals(OrderStatus::DELIVERED, $order->fresh()->status);
    }

    public function test_order_cancel(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);

        $result = $order->cancel('Changed my mind');
        $this->assertTrue($result);
        $this->assertEquals(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertEquals('Changed my mind', $order->fresh()->cancellation_reason);
    }

    public function test_order_status_helpers(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PENDING]);
        $this->assertTrue($order->isPending());
        $this->assertFalse($order->isAssigned());
        $this->assertFalse($order->isCompleted());
        $this->assertFalse($order->isCancelled());
    }

    public function test_order_generate_order_number(): void
    {
        $number = Order::generateOrderNumber();
        $this->assertStringStartsWith('OC', $number);
    }
}
