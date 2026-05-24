<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PhoneVerificationService
{
    public function __construct(
        private readonly SmsService $smsService,
    ) {}

    public function sendOtp(User $user): array
    {
        $result = ['success' => true, 'message' => 'Code envoyé par SMS.'];

        if ($user->phone_verified_at !== null) {
            $result = ['success' => false, 'message' => 'Ce numéro est déjà vérifié.'];
        } else {
            $phone = '+226' . preg_replace('/^(\+226|00226)/', '', preg_replace('/[\s\-]/', '', $user->phone));
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Cache::put("phone_verify:{$user->id}", $code, now()->addMinutes(10));

            try {
                $this->smsService->send($phone, "Votre code de vérification OUAGA CHAP : {$code}");
            } catch (\Throwable $exception) {
                Log::warning('PhoneVerification SMS failed', [
                    'user' => $user->id,
                    'error' => $exception->getMessage(),
                ]);
                $result = [
                    'success' => false,
                    'message' => "Impossible d'envoyer le SMS. Réessayez dans quelques instants.",
                ];
            }
        }

        return $result;
    }

    public function verifyOtp(User $user, string $code): array
    {
        $result = ['success' => true, 'message' => 'Numéro vérifié avec succès.', 'user' => $user];

        if ($user->phone_verified_at !== null) {
            $result = ['success' => false, 'message' => 'Ce numéro est déjà vérifié.'];
        } else {
            $cached = Cache::get("phone_verify:{$user->id}");

            if ($cached === null) {
                $result = ['success' => false, 'message' => 'Code expiré. Demandez un nouveau code.'];
            } elseif ($cached !== $code) {
                $result = ['success' => false, 'message' => 'Code incorrect.'];
            } else {
                Cache::forget("phone_verify:{$user->id}");
                $user->update(['phone_verified_at' => now()]);
                $user->refresh();
                $result['user'] = $user;
            }
        }

        return $result;
    }
}
