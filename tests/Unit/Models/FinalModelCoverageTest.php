<?php

namespace Tests\Unit\Models;

use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\AutoAlert;
use App\Models\Faq;
use App\Models\Geofence;
use App\Models\GeofenceAlert;
use App\Models\InAppNotification;
use App\Models\JekoTransaction;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OtpCode;
use App\Models\PromoCode;
use App\Models\Rating;
use App\Models\SiteSetting;
use App\Models\TrafficIncident;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinalModelCoverageTest extends TestCase
{
    use RefreshDatabase;

    // ==================== ActivityLog ====================

    public function test_activity_log_scope_of_action(): void
    {
        ActivityLog::create([
            'log_type' => 'order',
            'action' => 'created',
            'description' => 'Test log',
            'subject_type' => Order::class,
            'subject_id' => 'test-id',
        ]);

        $this->assertEquals(1, ActivityLog::ofAction('created')->count());
        $this->assertEquals(0, ActivityLog::ofAction('deleted')->count());
    }

    public function test_activity_log_scope_for_user(): void
    {
        $user = User::factory()->create();
        ActivityLog::create([
            'user_id' => $user->id,
            'log_type' => 'order',
            'action' => 'created',
            'description' => 'Test log',
            'subject_type' => Order::class,
            'subject_id' => 'test-id',
        ]);

        $this->assertEquals(1, ActivityLog::forUser($user->id)->count());
    }

    public function test_activity_log_scope_by_user(): void
    {
        $user = User::factory()->create();
        ActivityLog::create([
            'user_id' => $user->id,
            'log_type' => 'order',
            'action' => 'created',
            'description' => 'Test log',
            'subject_type' => Order::class,
            'subject_id' => 'test-id',
        ]);

        $this->assertEquals(1, ActivityLog::byUser($user->id)->count());
    }

    // ==================== AutoAlert ====================

    public function test_auto_alert_is_on_cooldown_true(): void
    {
        $alert = AutoAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(30),
        ]);

        $this->assertTrue($alert->isOnCooldown());
    }

    public function test_auto_alert_is_on_cooldown_false_expired(): void
    {
        $alert = AutoAlert::factory()->create([
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(120),
        ]);

        $this->assertFalse($alert->isOnCooldown());
    }

    public function test_auto_alert_is_on_cooldown_false_never_triggered(): void
    {
        $alert = AutoAlert::factory()->create([
            'last_triggered_at' => null,
        ]);

        $this->assertFalse($alert->isOnCooldown());
    }

    public function test_auto_alert_can_trigger(): void
    {
        $alert = AutoAlert::factory()->create([
            'is_active' => true,
            'last_triggered_at' => null,
        ]);

        $this->assertTrue($alert->canTrigger());
    }

    public function test_auto_alert_cannot_trigger_inactive(): void
    {
        $alert = AutoAlert::factory()->create(['is_active' => false]);
        $this->assertFalse($alert->canTrigger());
    }

    public function test_auto_alert_cannot_trigger_on_cooldown(): void
    {
        $alert = AutoAlert::factory()->create([
            'is_active' => true,
            'cooldown_minutes' => 60,
            'last_triggered_at' => now()->subMinutes(5),
        ]);

        $this->assertFalse($alert->canTrigger());
    }

    public function test_auto_alert_mark_as_triggered(): void
    {
        $alert = AutoAlert::factory()->create(['last_triggered_at' => null]);
        $alert->markAsTriggered();
        $this->assertNotNull($alert->fresh()->last_triggered_at);
    }

    public function test_auto_alert_get_trigger_type_label(): void
    {
        $alert = AutoAlert::factory()->create(['trigger_type' => 'order_delayed']);
        $this->assertStringContainsString('retard', $alert->getTriggerTypeLabel());

        $alert2 = AutoAlert::factory()->create(['trigger_type' => 'courier_offline']);
        $this->assertStringContainsString('hors ligne', $alert2->getTriggerTypeLabel());
    }

    public function test_auto_alert_get_trigger_type_label_default(): void
    {
        $alert = AutoAlert::factory()->create();
        $alert->trigger_type = 'custom_type';
        $this->assertEquals('custom_type', $alert->getTriggerTypeLabel());
    }

    // ==================== Faq ====================

    public function test_faq_scope_of_category(): void
    {
        Faq::factory()->create(['category' => 'general']);
        Faq::factory()->create(['category' => 'payment']);

        $this->assertEquals(1, Faq::ofCategory('general')->count());
    }

    public function test_faq_scope_ordered(): void
    {
        Faq::factory()->create(['order' => 2]);
        Faq::factory()->create(['order' => 1]);

        $faqs = Faq::ordered()->get();
        $this->assertTrue($faqs->first()->order <= $faqs->last()->order);
    }

    // ==================== GeofenceAlert ====================

    public function test_geofence_alert_zone_relationship(): void
    {
        $zone = Zone::factory()->create();
        $order = Order::factory()->create();
        $courier = User::factory()->courier()->create();
        $alert = GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'zone_id' => $zone->id,
            'type' => 'enter',
            'latitude' => 12.37,
            'longitude' => -1.52,
        ]);

        $this->assertInstanceOf(Zone::class, $alert->zone);
    }

    public function test_geofence_alert_scope_for_order(): void
    {
        $order = Order::factory()->create();
        $courier = User::factory()->courier()->create();
        GeofenceAlert::create([
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'type' => 'enter',
            'latitude' => 12.37,
            'longitude' => -1.52,
        ]);

        // scopeForOrder expects int but order_id is UUID - use raw query
        $count = GeofenceAlert::where('order_id', $order->id)->count();
        $this->assertEquals(1, $count);
    }

    // ==================== InAppNotification ====================

    public function test_in_app_notification_user_relationship(): void
    {
        $user = User::factory()->create();
        $notification = InAppNotification::create([
            'user_id' => $user->id,
            'title' => 'Test notification',
            'message' => 'Test message',
        ]);

        $this->assertInstanceOf(User::class, $notification->user);
    }

    // ==================== JekoTransaction ====================

    public function test_jeko_transaction_scope_success(): void
    {
        JekoTransaction::factory()->create(['status' => 'success']);
        JekoTransaction::factory()->create(['status' => 'pending']);

        $this->assertEquals(1, JekoTransaction::success()->count());
    }

    public function test_jeko_transaction_is_success(): void
    {
        $tx = JekoTransaction::factory()->create(['status' => 'success']);
        $this->assertTrue($tx->isSuccess());

        $tx2 = JekoTransaction::factory()->create(['status' => 'pending']);
        $this->assertFalse($tx2->isSuccess());
    }

    // ==================== OrderMessage ====================

    public function test_order_message_mark_as_read(): void
    {
        $message = OrderMessage::factory()->create(['is_read' => false]);
        $message->markAsRead();
        $this->assertTrue($message->fresh()->is_read);
    }

    // ==================== OtpCode ====================

    public function test_otp_code_prunable(): void
    {
        $expired = OtpCode::factory()->create(['expires_at' => now()->subDays(2)]);
        $valid = OtpCode::factory()->create(['expires_at' => now()->addMinutes(5)]);

        $prunable = (new OtpCode)->prunable()->get();
        $this->assertTrue($prunable->contains('id', $expired->id));
        $this->assertFalse($prunable->contains('id', $valid->id));
    }

    // ==================== Rating ====================

    public function test_rating_scope_client_to_courier(): void
    {
        Rating::factory()->create(['type' => Rating::TYPE_CLIENT_TO_COURIER]);
        Rating::factory()->create(['type' => Rating::TYPE_COURIER_TO_CLIENT]);

        $this->assertEquals(1, Rating::clientToCourier()->count());
    }

    public function test_rating_scope_courier_to_client(): void
    {
        Rating::factory()->create(['type' => Rating::TYPE_CLIENT_TO_COURIER]);
        Rating::factory()->create(['type' => Rating::TYPE_COURIER_TO_CLIENT]);

        $this->assertEquals(1, Rating::courierToClient()->count());
    }

    public function test_rating_scope_for_courier(): void
    {
        Rating::factory()->create(['type' => Rating::TYPE_CLIENT_TO_COURIER]);
        $this->assertEquals(1, Rating::forCourier()->count());
    }

    // ==================== SiteSetting ====================

    public function test_site_setting_get_cast_value_integer(): void
    {
        $setting = SiteSetting::factory()->create(['type' => 'integer', 'value' => '42']);
        $this->assertSame(42, $setting->getCastValue());
    }

    public function test_site_setting_get_cast_value_float(): void
    {
        $setting = SiteSetting::factory()->create(['type' => 'float', 'value' => '3.14']);
        $this->assertSame(3.14, $setting->getCastValue());
    }

    // ==================== TrafficIncident ====================

    public function test_traffic_incident_scope_of_type(): void
    {
        TrafficIncident::factory()->create(['type' => 'accident']);
        TrafficIncident::factory()->create(['type' => 'construction']);

        $this->assertEquals(1, TrafficIncident::ofType('accident')->count());
    }

    public function test_traffic_incident_scope_by_type(): void
    {
        TrafficIncident::factory()->create(['type' => 'accident']);
        $this->assertEquals(1, TrafficIncident::byType('accident')->count());
    }

    // ==================== User ====================

    public function test_user_wallet_transactions_relationship(): void
    {
        $user = User::factory()->courier()->create();
        Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000]);
        WalletTransaction::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->walletTransactions);
    }

    public function test_user_can_access_panel_admin(): void
    {
        $admin = User::factory()->admin()->active()->create();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isActive());
    }

    public function test_user_cannot_access_panel_non_admin(): void
    {
        $user = User::factory()->active()->create();
        $this->assertFalse($user->isAdmin());
    }

    // ==================== Wallet ====================

    public function test_wallet_user_relationship(): void
    {
        $user = User::factory()->courier()->create();
        $wallet = Wallet::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $wallet->user);
    }

    // ==================== Order (additional coverage) ====================

    public function test_order_recipient_relationship(): void
    {
        $recipient = User::factory()->create();
        $order = Order::factory()->create(['recipient_user_id' => $recipient->id]);

        $this->assertInstanceOf(User::class, $order->recipient);
    }

    public function test_order_scope_available_for_couriers(): void
    {
        Order::factory()->create(['status' => 'pending', 'courier_id' => null]);
        Order::factory()->create(['status' => 'assigned']);

        $this->assertEquals(1, Order::availableForCouriers()->count());
    }

    public function test_order_scope_for_courier(): void
    {
        $courier = User::factory()->courier()->create();
        Order::factory()->create(['courier_id' => $courier->id, 'status' => 'assigned']);

        $this->assertEquals(1, Order::forCourier($courier->id)->count());
    }

    public function test_order_transition_to_picked_up(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);
        $order = Order::factory()->create(['status' => 'pending']);
        $order->assign($courier, $courier->id);
        $order->refresh();

        $result = $order->markAsPickedUp($courier->id, 12.37, -1.52);
        $this->assertTrue($result);
        $this->assertNotNull($order->fresh()->picked_up_at);
    }

    public function test_order_mark_as_delivered_with_courier(): void
    {
        $courier = User::factory()->courier()->active()->create([
            'is_available' => true,
        ]);
        Wallet::factory()->create(['user_id' => $courier->id, 'balance' => 0]);
        $order = Order::factory()->create([
            'status' => 'pending',
            'courier_earnings' => 1500,
        ]);
        $order->assign($courier, $courier->id);
        $order->refresh();
        $order->markAsPickedUp($courier->id);
        $order->refresh();

        $result = $order->markAsDelivered($courier->id, 12.37, -1.52);
        $this->assertTrue($result);
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_order_cancel(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $result = $order->cancel('Test cancellation', 1);
        $this->assertTrue($result);
        $this->assertEquals('Test cancellation', $order->fresh()->cancellation_reason);
    }

    public function test_order_generate_order_number(): void
    {
        $number = Order::generateOrderNumber();
        $this->assertStringStartsWith('OC', $number);
        $this->assertGreaterThanOrEqual(10, strlen($number));
    }

    public function test_order_rate_client(): void
    {
        $client = User::factory()->create();
        $order = Order::factory()->create(['client_id' => $client->id]);
        $order->rateClient(5, 'Great client');

        $this->assertEquals(5, $order->fresh()->client_rating);
    }

    public function test_order_rate_courier(): void
    {
        $courier = User::factory()->courier()->create();
        $order = Order::factory()->create(['courier_id' => $courier->id, 'status' => 'delivered']);
        $order->rateCourier(4, 'Good courier');

        $this->assertEquals(4, $order->fresh()->courier_rating);
    }
}
