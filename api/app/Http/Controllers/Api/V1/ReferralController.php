<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends BaseController
{
    public function __construct(private readonly ReferralService $referralService) {}

    /**
     * Retourner le code parrain du user connecté.
     */
    public function myCode(Request $request): JsonResponse
    {
        $user = $request->user();

        // Générer le code si absent (migration rétroactive)
        if (empty($user->referral_code)) {
            $user->update(['referral_code' => $this->referralService->generateReferralCode()]);
            $user->refresh();
        }

        return $this->success(['referral_code' => $user->referral_code]);
    }

    /**
     * Statistiques de parrainage du user connecté.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalReferrals = Referral::where('referrer_id', $user->id)->count();
        $rewardedReferrals = Referral::where('referrer_id', $user->id)
            ->whereNotNull('referrer_rewarded_at')
            ->count();

        return $this->success([
            'total_referrals'          => $totalReferrals,
            'rewarded_referrals'       => $rewardedReferrals,
            'pending_referrals'        => $totalReferrals - $rewardedReferrals,
            'total_earned_xof'         => $rewardedReferrals * ReferralService::REFERRAL_REWARD_XOF,
            'reward_per_referral_xof'  => ReferralService::REFERRAL_REWARD_XOF,
        ]);
    }

    /**
     * Appliquer un code parrain au user connecté.
     *
     * Body: { "code": "ABC12345" }
     */
    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:8', 'alpha_num'],
        ]);

        $user = $request->user();

        // Empêcher d'appliquer un code si déjà parrainé
        if ($user->referred_by_user_id !== null) {
            return $this->error('Vous avez déjà appliqué un code parrain.', 422);
        }

        $applied = $this->referralService->applyReferralCode($user, strtoupper($validated['code']));

        if (! $applied) {
            return $this->error('Code parrain invalide ou non applicable.', 422);
        }

        return $this->success(null, 'Code parrain appliqué avec succès.');
    }
}
