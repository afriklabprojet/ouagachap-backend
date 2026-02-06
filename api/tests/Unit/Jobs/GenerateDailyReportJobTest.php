<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateDailyReportJob;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tests pour GenerateDailyReportJob
 */
class GenerateDailyReportJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */
    public function job_can_be_instantiated(): void
    {
        $job = new GenerateDailyReportJob();

        $this->assertInstanceOf(GenerateDailyReportJob::class, $job);
    }

    /** @test */
    public function job_uses_yesterday_date_by_default(): void
    {
        $job = new GenerateDailyReportJob();

        $this->assertEquals(now()->subDay()->format('Y-m-d'), $job->date);
    }

    /** @test */
    public function job_accepts_custom_date(): void
    {
        $customDate = '2025-12-25';
        $job = new GenerateDailyReportJob($customDate);

        $this->assertEquals($customDate, $job->date);
    }

    /** @test */
    public function job_implements_should_queue(): void
    {
        $this->assertTrue(
            in_array(
                \Illuminate\Contracts\Queue\ShouldQueue::class,
                class_implements(GenerateDailyReportJob::class)
            )
        );
    }

    /** @test */
    public function job_can_be_dispatched(): void
    {
        Queue::fake();
        
        GenerateDailyReportJob::dispatch();

        Queue::assertPushed(GenerateDailyReportJob::class);
    }

    /** @test */
    public function job_can_be_dispatched_with_date(): void
    {
        Queue::fake();
        
        GenerateDailyReportJob::dispatch('2025-12-01');

        Queue::assertPushed(GenerateDailyReportJob::class, function ($job) {
            return $job->date === '2025-12-01';
        });
    }

    /** @test */
    public function handle_generates_report_file(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'Rapport quotidien'));

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        Storage::assertExists("reports/daily/{$date}.json");
    }

    /** @test */
    public function handle_generates_valid_json(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        Log::shouldReceive('info')->once();

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        $content = Storage::get("reports/daily/{$date}.json");
        $report = json_decode($content, true);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('date', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertArrayHasKey('orders', $report);
        $this->assertArrayHasKey('revenue', $report);
        $this->assertArrayHasKey('users', $report);
    }

    /** @test */
    public function report_contains_order_statistics(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        // Créer des commandes pour hier
        Order::factory()->create([
            'status' => 'delivered',
            'total_price' => 5000,
            'created_at' => now()->subDay(),
        ]);
        Order::factory()->create([
            'status' => 'cancelled',
            'total_price' => 3000,
            'created_at' => now()->subDay(),
        ]);

        Log::shouldReceive('info')->once();

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        $content = Storage::get("reports/daily/{$date}.json");
        $report = json_decode($content, true);

        $this->assertArrayHasKey('total', $report['orders']);
        $this->assertArrayHasKey('delivered', $report['orders']);
        $this->assertArrayHasKey('cancelled', $report['orders']);
        $this->assertArrayHasKey('delivery_rate', $report['orders']);
    }

    /** @test */
    public function report_contains_revenue_data(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        Log::shouldReceive('info')->once();

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        $content = Storage::get("reports/daily/{$date}.json");
        $report = json_decode($content, true);

        $this->assertArrayHasKey('total', $report['revenue']);
        $this->assertArrayHasKey('delivery_fees', $report['revenue']);
    }

    /** @test */
    public function report_contains_user_statistics(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        // Créer des utilisateurs hier
        User::factory()->client()->create(['created_at' => now()->subDay()]);
        User::factory()->courier()->create(['created_at' => now()->subDay()]);

        Log::shouldReceive('info')->once();

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        $content = Storage::get("reports/daily/{$date}.json");
        $report = json_decode($content, true);

        $this->assertArrayHasKey('new_clients', $report['users']);
        $this->assertArrayHasKey('new_couriers', $report['users']);
        $this->assertArrayHasKey('active_couriers', $report['users']);
    }

    /** @test */
    public function report_calculates_delivery_rate(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        // 8 livrées, 2 annulées = 80% delivery rate
        Order::factory()->count(8)->create([
            'status' => 'delivered',
            'created_at' => now()->subDay(),
        ]);
        Order::factory()->count(2)->create([
            'status' => 'cancelled',
            'created_at' => now()->subDay(),
        ]);

        Log::shouldReceive('info')->once();

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        $content = Storage::get("reports/daily/{$date}.json");
        $report = json_decode($content, true);

        $this->assertEquals(80, $report['orders']['delivery_rate']);
    }

    /** @test */
    public function report_handles_zero_orders(): void
    {
        $date = now()->subDay()->format('Y-m-d');
        
        Log::shouldReceive('info')->once();

        $job = new GenerateDailyReportJob($date);
        $job->handle();

        $content = Storage::get("reports/daily/{$date}.json");
        $report = json_decode($content, true);

        $this->assertEquals(0, $report['orders']['total']);
        $this->assertEquals(0, $report['orders']['delivery_rate']);
    }

    /** @test */
    public function job_uses_required_traits(): void
    {
        $traits = class_uses_recursive(GenerateDailyReportJob::class);
        
        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }
}
