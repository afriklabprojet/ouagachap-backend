<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit breaker générique basé sur Redis/Cache.
 *
 * États :
 *   CLOSED   → appels normaux (état par défaut)
 *   OPEN     → circuit ouvert, appels bloqués → fallback immédiat
 *   HALF_OPEN → une tentative de sonde autorisée pour tester la reprise
 *
 * Usage :
 *   $cb = new CircuitBreaker('sappay', threshold: 5, cooldown: 60);
 *   if ($cb->isOpen()) { return $fallback; }
 *   try {
 *       $result = $externalCall();
 *       $cb->recordSuccess();
 *       return $result;
 *   } catch (\Exception $e) {
 *       $cb->recordFailure();
 *       return $fallback;
 *   }
 */
class CircuitBreaker
{
    private const STATE_CLOSED    = 'closed';
    private const STATE_OPEN      = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private string $keyFailures;
    private string $keyState;
    private string $keyLastAttempt;

    public function __construct(
        private readonly string $service,
        private readonly int $threshold = 5,
        private readonly int $cooldown  = 60,
    ) {
        $this->keyFailures    = "cb:{$service}:failures";
        $this->keyState       = "cb:{$service}:state";
        $this->keyLastAttempt = "cb:{$service}:last_attempt";
    }

    public function isOpen(): bool
    {
        $state = $this->state();

        if ($state === self::STATE_OPEN) {
            // Vérifier si le cooldown est passé → passer en HALF_OPEN
            $lastAttempt = Cache::get($this->keyLastAttempt, 0);
            if (time() - $lastAttempt >= $this->cooldown) {
                Cache::put($this->keyState, self::STATE_HALF_OPEN, $this->cooldown * 2);
                Log::warning("CircuitBreaker [{$this->service}]: HALF_OPEN — sonde autorisée");
                return false;
            }

            return true;
        }

        return false;
    }

    public function recordSuccess(): void
    {
        Cache::forget($this->keyFailures);
        Cache::forget($this->keyLastAttempt);
        $prev = $this->state();
        Cache::put($this->keyState, self::STATE_CLOSED, $this->cooldown * 10);

        if ($prev !== self::STATE_CLOSED) {
            Log::info("CircuitBreaker [{$this->service}]: CLOSED — service rétabli");
        }
    }

    public function recordFailure(): void
    {
        $failures = Cache::increment($this->keyFailures);
        Cache::put($this->keyLastAttempt, time(), $this->cooldown * 2);

        if (! Cache::has($this->keyFailures)) {
            Cache::put($this->keyFailures, 1, $this->cooldown * 2);
            $failures = 1;
        }

        if ($failures >= $this->threshold) {
            Cache::put($this->keyState, self::STATE_OPEN, $this->cooldown * 2);
            Log::error("CircuitBreaker [{$this->service}]: OPEN après {$failures} échecs — cooldown {$this->cooldown}s");
        } else {
            Log::warning("CircuitBreaker [{$this->service}]: {$failures}/{$this->threshold} échecs");
        }
    }

    public function state(): string
    {
        return Cache::get($this->keyState, self::STATE_CLOSED);
    }

    public function failures(): int
    {
        return (int) Cache::get($this->keyFailures, 0);
    }

    /**
     * Force l'ouverture manuelle du circuit (pour tests ou maintenance).
     */
    public function forceOpen(): void
    {
        Cache::put($this->keyState, self::STATE_OPEN, $this->cooldown * 2);
        Cache::put($this->keyLastAttempt, time(), $this->cooldown * 2);
    }

    /**
     * Réinitialise complètement le circuit (utile après un déploiement ou fix).
     */
    public function reset(): void
    {
        Cache::forget($this->keyFailures);
        Cache::forget($this->keyState);
        Cache::forget($this->keyLastAttempt);
    }
}
