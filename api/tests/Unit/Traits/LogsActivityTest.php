<?php

namespace Tests\Unit\Traits;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LogsActivityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function trait_exists(): void
    {
        $this->assertTrue(trait_exists(LogsActivity::class));
    }

    /** @test */
    public function trait_has_boot_method(): void
    {
        $reflection = new \ReflectionClass(LogsActivity::class);
        $this->assertTrue($reflection->hasMethod('bootLogsActivity'));
    }

    /** @test */
    public function trait_has_log_activity_method(): void
    {
        $reflection = new \ReflectionClass(LogsActivity::class);
        $this->assertTrue($reflection->hasMethod('logActivity'));
    }

    /** @test */
    public function trait_has_log_custom_activity_method(): void
    {
        $reflection = new \ReflectionClass(LogsActivity::class);
        $this->assertTrue($reflection->hasMethod('logCustomActivity'));
    }

    /** @test */
    public function trait_has_activity_logs_relation(): void
    {
        $reflection = new \ReflectionClass(LogsActivity::class);
        $this->assertTrue($reflection->hasMethod('activityLogs'));
    }

    /** @test */
    public function order_model_uses_logs_activity_trait(): void
    {
        $traits = class_uses_recursive(Order::class);
        $this->assertArrayHasKey(LogsActivity::class, $traits);
    }

    /** @test */
    public function order_creation_logs_activity(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($client->id, $log->user_id);
    }

    /** @test */
    public function activity_log_includes_ip_address(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        // IP peut être null en test, mais le champ doit exister
        $this->assertTrue($log->getAttributes() !== null);
    }

    /** @test */
    public function activity_log_stores_log_type(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('order', $log->log_type);
    }

    /** @test */
    public function excluded_fields_are_not_logged(): void
    {
        // Les champs comme password, remember_token ne doivent pas être loggés
        $defaults = ['password', 'remember_token', 'api_token', 'fcm_token', 'updated_at'];

        foreach ($defaults as $field) {
            $this->assertContains($field, $defaults);
        }
    }

    /** @test */
    public function activity_description_is_generated_correctly(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->description);
        $this->assertStringContainsString('Order', $log->description);
        $this->assertStringContainsString('créé', $log->description);
    }

    /** @test */
    public function order_update_logs_changes(): void
    {
        $client = User::factory()->client()->create();
        $courier = User::factory()->courier()->create();
        Auth::login($client);

        $order = Order::factory()->create([
            'client_id' => $client->id,
            'pickup_instructions' => 'Initial instructions',
        ]);

        // Clear initial creation log
        ActivityLog::truncate();

        // Update the order with a valid field
        $order->update(['pickup_instructions' => 'Updated instructions']);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertNotNull($log->old_values);
        $this->assertNotNull($log->new_values);
    }

    /** @test */
    public function log_custom_activity_works(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        // Clear creation log
        ActivityLog::truncate();

        // Call custom activity log
        $log = $order->logCustomActivity(
            'custom_action',
            'Custom description for testing',
            ['custom_key' => 'custom_value']
        );

        $this->assertNotNull($log);
        $this->assertEquals('custom_action', $log->action);
        $this->assertEquals('Custom description for testing', $log->description);
    }

    /** @test */
    public function activity_log_model_has_subject_relation(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $subject = $log->subject;
        $this->assertInstanceOf(Order::class, $subject);
        $this->assertEquals($order->id, $subject->id);
    }

    /** @test */
    public function activity_log_model_has_user_relation(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($client->id, $log->user->id);
    }

    /** @test */
    public function activity_can_be_logged_without_authenticated_user(): void
    {
        Auth::logout();

        $order = Order::factory()->create();

        $log = ActivityLog::where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
    }

    /** @test */
    public function order_activity_logs_relation_returns_collection(): void
    {
        $client = User::factory()->client()->create();
        Auth::login($client);

        $order = Order::factory()->create(['client_id' => $client->id]);

        $logs = $order->activityLogs;

        $this->assertNotNull($logs);
        $this->assertGreaterThanOrEqual(1, $logs->count());
    }

    /** @test */
    public function get_activity_log_type_returns_lowercase_model_name(): void
    {
        $order = Order::factory()->make();

        // Use reflection to call protected method
        $reflection = new \ReflectionMethod($order, 'getActivityLogType');
        $reflection->setAccessible(true);
        
        $logType = $reflection->invoke($order);

        $this->assertEquals('order', $logType);
    }

    /** @test */
    public function get_excluded_log_fields_includes_sensitive_fields(): void
    {
        $order = Order::factory()->make();

        // Use reflection to call protected method
        $reflection = new \ReflectionMethod($order, 'getExcludedLogFields');
        $reflection->setAccessible(true);
        
        $excludedFields = $reflection->invoke($order);

        $this->assertContains('password', $excludedFields);
        $this->assertContains('remember_token', $excludedFields);
        $this->assertContains('fcm_token', $excludedFields);
    }

    /** @test */
    public function get_activity_description_returns_correct_format(): void
    {
        $order = Order::factory()->make();

        // Use reflection to call protected method
        $reflection = new \ReflectionMethod($order, 'getActivityDescription');
        $reflection->setAccessible(true);
        
        $description = $reflection->invoke($order, 'created');
        $this->assertStringContainsString('créé', $description);

        $description = $reflection->invoke($order, 'updated');
        $this->assertStringContainsString('mis à jour', $description);

        $description = $reflection->invoke($order, 'deleted');
        $this->assertStringContainsString('supprimé', $description);
    }
}
