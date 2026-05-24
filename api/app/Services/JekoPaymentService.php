<?php

namespace App\Services;

/**
 * Stub class — Jeko payment integration replaced by SappayService.
 * Kept to avoid autoload failures in legacy test files.
 * @deprecated Use SappayService instead.
 */
class JekoPaymentService
{
    public function createPaymentRequest(mixed ...$args): array
    {
        return ['success' => false, 'message' => 'Jeko removed'];
    }

    public function handleWebhook(array $payload): array
    {
        return ['success' => false, 'message' => 'Jeko removed'];
    }

    public function getPaymentStatus(string $jekoId): array
    {
        return ['success' => false, 'message' => 'Jeko removed'];
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        return false;
    }
}
