<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\AutoAlert;
use App\Models\Order;
use App\Models\OrderLocationHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Detects couriers who have not moved for ≥ STATIC_THRESHOLD_MINUTES during an active delivery.
 * Writes an alert record and logs a warning. Does NOT reassign.
 * Scheduled every 5 minutes via console.php.
 */
class DetectStaticCourierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    private const STATIC_THRESHOLD_MINUTES = 10;
    private const ALERT_TRIGGER_TYPE = 'static_courier';
    private const ALERT_COOLDOWN_MINUTES = 15;

    public function handle(): void
    {
        $cutoff = now()->subMinutes(self::STATIC_THRESHOLD_MINUTES);

        $activeOrders = Order::whereIn('status', OrderStatus::activeStatuses())
            ->whereNotNull('courier_id')
            ->with('courier')
            ->get();

        if ($activeOrders->isEmpty()) {
            return;
        }

        foreach ($activeOrders as $order) {
            try {
                $this->checkOrderCourier($order, $cutoff);
            } catch (\Throwable $e) {
                Log::error('DetectStaticCourierJob: error processing order', [
                    'order_id' => $order->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }

    private function checkOrderCourier(Order $order, \Carbon\Carbon $cutoff): void
    {
        $courier = $order->courier;

        if ($courier === null) {
            return;
        }

        $lastLocationEntry = OrderLocationHistory::where('order_id', $order->id)
            ->where('courier_id', $courier->id)
            ->orderByDesc('recorded_at')
            ->first();

        $lastLocationAt = $lastLocationEntry?->recorded_at ?? $courier->location_updated_at;

        if ($lastLocationAt === null || $lastLocationAt->greaterThan($cutoff)) {
            return;
        }

        $minutesStatic = (int) now()->diffInMinutes($lastLocationAt);

        Log::warning('STATIC_COURIER_DETECTED', [
            'order_id'         => $order->id,
            'order_number'     => $order->order_number,
            'order_status'     => $order->status->value,
            'courier_id'       => $courier->id,
            'minutes_static'   => $minutesStatic,
            'last_location_at' => $lastLocationAt->toIso8601String(),
        ]);

        $this->writeAlert($order, $courier->id, $minutesStatic);
    }

    private function writeAlert(Order $order, int $courierId, int $minutesStatic): void
    {
        $recentAlert = AutoAlert::where('trigger_type', self::ALERT_TRIGGER_TYPE)
            ->whereJsonContains('conditions->order_id', $order->id)
            ->where('last_triggered_at', '>=', now()->subMinutes(self::ALERT_COOLDOWN_MINUTES))
            ->first();

        if ($recentAlert !== null) {
            return;
        }

        AutoAlert::create([
            'name'             => 'Coursier statique — commande #' . $order->order_number,
            'trigger_type'     => self::ALERT_TRIGGER_TYPE,
            'conditions'       => [
                'order_id'       => $order->id,
                'order_number'   => $order->order_number,
                'courier_id'     => $courierId,
                'minutes_static' => $minutesStatic,
                'order_status'   => $order->status->value,
            ],
            'actions'          => ['notify_admin'],
            'is_active'        => true,
            'cooldown_minutes' => self::ALERT_COOLDOWN_MINUTES,
            'last_triggered_at' => now(),
        ]);
    }
}
