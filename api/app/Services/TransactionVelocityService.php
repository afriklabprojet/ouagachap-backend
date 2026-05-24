<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TransactionVelocityService
{
    public const MAX_RECHARGES_PER_HOUR = 5;

    private const CACHE_TTL_SECONDS = 3600;
    private const CACHE_KEY_PREFIX  = 'recharge_velocity:';

    /**
     * Checks whether the user has exceeded the recharge limit for the current sliding window.
     *
     * Returns an immutable result array — never mutates the $user argument.
     *
     * @return array{blocked: bool, count: int, message: string}
     */
    public function checkRechargeVelocity(User $user): array
    {
        $count = (int) Cache::get($this->cacheKey($user->id), 0);

        if ($count >= self::MAX_RECHARGES_PER_HOUR) {
            Log::warning('TRANSACTION_VELOCITY_EXCEEDED', [
                'user_id'       => $user->id,
                'recharge_count' => $count,
                'limit'         => self::MAX_RECHARGES_PER_HOUR,
            ]);

            return [
                'blocked' => true,
                'count'   => $count,
                'message' => 'Limite de recharges dépassée. Réessayez dans 1 heure.',
            ];
        }

        return [
            'blocked' => false,
            'count'   => $count,
            'message' => '',
        ];
    }

    /**
     * Increments the recharge counter for a 1-hour sliding window.
     * Sets the TTL only on first increment so the window starts at the first recharge.
     */
    public function recordRecharge(User $user): void
    {
        $key     = $this->cacheKey($user->id);
        $current = (int) Cache::get($key, 0);

        if ($current === 0) {
            Cache::put($key, 1, self::CACHE_TTL_SECONDS);
            return;
        }

        Cache::increment($key);
    }

    private function cacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . $userId;
    }
}
