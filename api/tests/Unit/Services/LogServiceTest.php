<?php

namespace Tests\Unit\Services;

use App\Services\LogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Tests pour LogService
 */
class LogServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    // ==================== userAction Tests ====================

    public function test_user_action_logs_to_daily_channel(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once();
        
        LogService::userAction('User logged in');
    }

    public function test_user_action_logs_info_level(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once();
        
        LogService::userAction('Test action');
    }

    public function test_user_action_includes_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return $message === 'User clicked button' 
                    && isset($context['ip'])
                    && isset($context['user_agent']);
            })
            ->once();
        
        LogService::userAction('User clicked button');
    }

    public function test_user_action_with_custom_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return isset($context['custom_key']) && $context['custom_key'] === 'custom_value';
            })
            ->once();
        
        LogService::userAction('Custom action', ['custom_key' => 'custom_value']);
    }

    // ==================== apiError Tests ====================

    public function test_api_error_logs_to_daily_channel(): void
    {
        $exception = new \Exception('Test error');
        
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('error')
            ->once();
        
        LogService::apiError('API Error', $exception);
    }

    public function test_api_error_includes_exception_details(): void
    {
        $exception = new \Exception('Test error message');
        
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('error')
            ->withArgs(function ($message, $context) {
                return $message === 'API Error'
                    && $context['exception'] === 'Exception'
                    && $context['message'] === 'Test error message'
                    && isset($context['file'])
                    && isset($context['line'])
                    && isset($context['trace']);
            })
            ->once();
        
        LogService::apiError('API Error', $exception);
    }

    public function test_api_error_with_custom_context(): void
    {
        $exception = new \RuntimeException('Runtime error');
        
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('error')
            ->withArgs(function ($message, $context) {
                return isset($context['order_id']) && $context['order_id'] === '12345';
            })
            ->once();
        
        LogService::apiError('Order Error', $exception, ['order_id' => '12345']);
    }

    // ==================== payment Tests ====================

    public function test_payment_logs_with_prefix(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'Payment:')
                    && isset($context['timestamp']);
            })
            ->once();
        
        LogService::payment('initiated');
    }

    public function test_payment_with_amount_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return isset($context['amount']) && $context['amount'] === 5000;
            })
            ->once();
        
        LogService::payment('completed', ['amount' => 5000]);
    }

    // ==================== order Tests ====================

    public function test_order_logs_with_prefix(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'Order:')
                    && $context['order_id'] === 'ORD123';
            })
            ->once();
        
        LogService::order('created', 'ORD123');
    }

    public function test_order_with_status_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return isset($context['status']) && $context['status'] === 'delivered';
            })
            ->once();
        
        LogService::order('status_changed', 'ORD456', ['status' => 'delivered']);
    }

    // ==================== courier Tests ====================

    public function test_courier_logs_with_prefix(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'Courier:')
                    && isset($context['timestamp']);
            })
            ->once();
        
        LogService::courier('went online');
    }

    public function test_courier_with_location_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return isset($context['latitude']) && isset($context['longitude']);
            })
            ->once();
        
        LogService::courier('location_update', [
            'latitude' => 12.3456,
            'longitude' => -1.5234
        ]);
    }

    // ==================== admin Tests ====================

    public function test_admin_logs_with_prefix(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'Admin:')
                    && isset($context['ip'])
                    && isset($context['timestamp']);
            })
            ->once();
        
        LogService::admin('user_banned');
    }

    public function test_admin_with_target_user_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return isset($context['target_user_id']) && $context['target_user_id'] === 999;
            })
            ->once();
        
        LogService::admin('user_suspended', ['target_user_id' => 999]);
    }

    // ==================== security Tests ====================

    public function test_security_logs_warning_level(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'Security:')
                    && isset($context['ip'])
                    && isset($context['user_agent'])
                    && isset($context['url'])
                    && isset($context['method']);
            })
            ->once();
        
        LogService::security('suspicious_login');
    }

    public function test_security_with_attempt_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->withArgs(function ($message, $context) {
                return isset($context['attempts']) && $context['attempts'] === 5;
            })
            ->once();
        
        LogService::security('brute_force_detected', ['attempts' => 5]);
    }

    // ==================== performance Tests ====================

    public function test_performance_logs_when_above_threshold(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->withArgs(function ($message, $context) {
                return str_starts_with($message, 'Performance:')
                    && $context['value_ms'] === 150.5;
            })
            ->once();
        
        LogService::performance('slow_query', 150.5);
    }

    public function test_performance_does_not_log_below_threshold(): void
    {
        Log::shouldNotReceive('channel');
        Log::shouldNotReceive('warning');
        
        LogService::performance('fast_query', 50.0);
    }

    public function test_performance_logs_at_exact_threshold(): void
    {
        Log::shouldNotReceive('channel');
        Log::shouldNotReceive('warning');
        
        // 100ms est le seuil, donc 100 ne doit pas être loggé (> 100)
        LogService::performance('query', 100.0);
    }

    public function test_performance_logs_just_above_threshold(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('warning')
            ->once();
        
        // 100.1ms doit être loggé
        LogService::performance('query', 100.1);
    }

    // ==================== webhook Tests ====================

    public function test_webhook_logs_with_provider_prefix(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Webhook jeko:')
                    && isset($context['ip'])
                    && isset($context['timestamp']);
            })
            ->once();
        
        LogService::webhook('jeko', 'payment_confirmed');
    }

    public function test_webhook_with_payload_context(): void
    {
        Log::shouldReceive('channel')
            ->with('daily')
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'stripe')
                    && isset($context['event_type']);
            })
            ->once();
        
        LogService::webhook('stripe', 'event_received', ['event_type' => 'payment_intent.succeeded']);
    }

    // ==================== Service Class Tests ====================

    public function test_log_service_has_static_methods(): void
    {
        $this->assertTrue(method_exists(LogService::class, 'userAction'));
        $this->assertTrue(method_exists(LogService::class, 'apiError'));
        $this->assertTrue(method_exists(LogService::class, 'payment'));
        $this->assertTrue(method_exists(LogService::class, 'order'));
        $this->assertTrue(method_exists(LogService::class, 'courier'));
        $this->assertTrue(method_exists(LogService::class, 'admin'));
        $this->assertTrue(method_exists(LogService::class, 'security'));
        $this->assertTrue(method_exists(LogService::class, 'performance'));
        $this->assertTrue(method_exists(LogService::class, 'webhook'));
    }
}
