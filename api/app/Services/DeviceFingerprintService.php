<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class DeviceFingerprintService
{
    /**
     * Builds a SHA-256 fingerprint from normalized device attributes.
     * Inputs are lowercased and trimmed before hashing to ensure stability.
     */
    public function generate(string $deviceId, string $platform, string $deviceType): string
    {
        $normalized = implode('|', [
            strtolower(trim($deviceId)),
            strtolower(trim($platform)),
            strtolower(trim($deviceType)),
        ]);

        return hash('sha256', $normalized);
    }

    /**
     * Returns a different user that already holds the same fingerprint,
     * or null when no duplicate exists.
     */
    public function checkDuplicate(string $fingerprint, ?int $excludeUserId = null): ?User
    {
        $query = User::where('device_fingerprint', $fingerprint);

        if ($excludeUserId !== null) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->first();
    }

    /**
     * Persists the fingerprint on the user record.
     * Returns a new state — does not mutate the passed $user object in-place
     * beyond what Eloquent save() does on the same instance.
     */
    public function store(User $user, string $fingerprint): void
    {
        $user->update(['device_fingerprint' => $fingerprint]);
    }

    /**
     * Runs the full check-and-store flow.
     * Returns ['duplicate_account_warning' => bool, 'duplicate_user_id' => int|null].
     */
    public function processForUser(
        User $user,
        string $deviceId,
        string $platform,
        string $deviceType
    ): array {
        $fingerprint = $this->generate($deviceId, $platform, $deviceType);
        $duplicate   = $this->checkDuplicate($fingerprint, $user->id);

        if ($duplicate !== null) {
            Log::warning('DUPLICATE_DEVICE_FINGERPRINT', [
                'fingerprint'        => $fingerprint,
                'current_user_id'    => $user->id,
                'duplicate_user_id'  => $duplicate->id,
                'platform'           => $platform,
                'device_type'        => $deviceType,
            ]);

            return [
                'duplicate_account_warning' => true,
                'duplicate_user_id'         => $duplicate->id,
            ];
        }

        $this->store($user, $fingerprint);

        return [
            'duplicate_account_warning' => false,
            'duplicate_user_id'         => null,
        ];
    }
}
