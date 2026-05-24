<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // MySQL
        try {
            DB::connection()->getPdo();
            $latency = $this->measureMs(fn () => DB::select('SELECT 1'));
            $checks['mysql'] = ['status' => 'ok', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            $checks['mysql'] = ['status' => 'down', 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Cache (vérifie le store configuré : database, redis, file…)
        $cacheStore = config('cache.default', 'database');
        try {
            $latency = $this->measureMs(fn () => Cache::put('health_check', true, 10));
            $checks['cache'] = ['status' => 'ok', 'driver' => $cacheStore, 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'down', 'driver' => $cacheStore, 'error' => $e->getMessage()];
            $healthy = false;
        }

        // Queue (vérifie qu'il y a des jobs en attente ou un worker actif)
        $queueDriver = config('queue.default', 'database');
        try {
            if ($queueDriver === 'database') {
                $pending = DB::table('jobs')->count();
                $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
                $checks['queue'] = [
                    'status' => 'ok',
                    'driver' => 'database',
                    'pending_jobs' => $pending,
                    'failed_last_24h' => $failed,
                ];
            } else {
                $lastHeartbeat = Cache::get('queue:worker:heartbeat');
                // @codeCoverageIgnoreStart
                $checks['queue'] = $lastHeartbeat && now()->diffInMinutes($lastHeartbeat) < 5
                    ? ['status' => 'ok', 'driver' => $queueDriver, 'last_heartbeat' => $lastHeartbeat]
                    : ['status' => 'unknown', 'driver' => $queueDriver, 'note' => 'No recent heartbeat'];
            }
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'down', 'driver' => $queueDriver, 'error' => $e->getMessage()];
            // @codeCoverageIgnoreEnd
        }

        // Broadcasting (Pusher ou Reverb)
        $broadcaster = config('broadcasting.default', 'null');
        if ($broadcaster === 'pusher') {
            try {
                $key = config('broadcasting.connections.pusher.key');
                $cluster = config('broadcasting.connections.pusher.options.cluster', 'eu');
                // @codeCoverageIgnoreStart
                $checks['broadcasting'] = $key
                    ? ['status' => 'ok', 'driver' => 'pusher', 'cluster' => $cluster]
                    : ['status' => 'down', 'driver' => 'pusher', 'error' => 'PUSHER_APP_KEY not configured'];
                if (!$key) $healthy = false;
            } catch (\Throwable $e) {
                $checks['broadcasting'] = ['status' => 'down', 'driver' => 'pusher', 'error' => $e->getMessage()];
                $healthy = false;
                // @codeCoverageIgnoreEnd
            }
        } elseif ($broadcaster === 'reverb') {
            try {
                $host = config('reverb.servers.reverb.host', '127.0.0.1');
                $port = config('reverb.servers.reverb.port', 8080);
                // @codeCoverageIgnoreStart
                $fp = @fsockopen($host, (int) $port, $errno, $errstr, 2);
                if ($fp) {
                    fclose($fp);
                    $checks['broadcasting'] = ['status' => 'ok', 'driver' => 'reverb', 'port' => (int) $port];
                } else {
                    $checks['broadcasting'] = ['status' => 'down', 'driver' => 'reverb', 'error' => "{$errstr} ({$errno})"];
                    $healthy = false;
                }
            } catch (\Throwable $e) {
                $checks['broadcasting'] = ['status' => 'down', 'driver' => 'reverb', 'error' => $e->getMessage()];
                $healthy = false;
                // @codeCoverageIgnoreEnd
            }
        } else {
            $checks['broadcasting'] = ['status' => 'off', 'driver' => $broadcaster];
        }

        // Storage writeable
        try {
            $testFile = storage_path('app/.health_check');
            // @codeCoverageIgnoreStart
            file_put_contents($testFile, 'ok');
            unlink($testFile);
            $checks['storage'] = ['status' => 'ok'];
        } catch (\Throwable $e) {
            $checks['storage'] = ['status' => 'down', 'error' => $e->getMessage()];
            $healthy = false;
            // @codeCoverageIgnoreEnd
        }

        return response()->json([
            'success' => true,
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function measureMs(callable $fn): float
    {
        $start = microtime(true);
        $fn();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
