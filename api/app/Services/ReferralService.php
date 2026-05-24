<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    /**
     * Montant de récompense de parrainage en XOF.
     */
    public const REFERRAL_REWARD_XOF = 500;

    public function __construct(private readonly WalletService $walletService) {}

    /**
     * Générer un code parrain unique de 8 caractères alphanumériques.
     */
    public function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Appliquer un code parrain lors de l'inscription.
     * Crée le Referral et met à jour referred_by_user_id sur le nouvel utilisateur.
     * Appelé depuis AuthService après création du user.
     *
     * @return bool true si le parrainage a été appliqué, false sinon
     */
    public function applyReferralCode(User $newUser, string $code): bool
    {
        $referrer = User::where('referral_code', $code)->first();

        if ($referrer === null) {
            return false;
        }

        if ($referrer->id === $newUser->id) {
            return false;
        }

        // Vérifier que le nouvel utilisateur n'a pas déjà été parrainé
        if (Referral::where('referred_id', $newUser->id)->exists()) {
            return false;
        }

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
        ]);

        $newUser->update(['referred_by_user_id' => $referrer->id]);

        return true;
    }

    /**
     * Créditer la récompense de parrainage (REFERRAL_REWARD_XOF) lors de la
     * première livraison complétée par l'utilisateur parrainé.
     * Appelé après qu'une commande passe au statut DELIVERED.
     */
    public function rewardReferralOnFirstDelivery(User $user): void
    {
        $referral = Referral::where('referred_id', $user->id)
            ->whereNull('referred_rewarded_at')
            ->first();

        if ($referral === null) {
            return;
        }

        // S'assurer que c'est bien la première commande livrée
        $deliveredOrdersCount = $user->clientOrders()
            ->where('status', 'delivered')
            ->count();

        if ($deliveredOrdersCount !== 1) {
            return;
        }

        $wallet = $this->walletService->getOrCreateWallet($user);
        $wallet->credit(self::REFERRAL_REWARD_XOF);
        $user->syncWalletBalance();

        $referrer = $referral->referrer;
        if ($referrer !== null) {
            $referrerWallet = $this->walletService->getOrCreateWallet($referrer);
            $referrerWallet->credit(self::REFERRAL_REWARD_XOF);
            $referrer->syncWalletBalance();
        }

        $referral->update([
            'referred_rewarded_at' => now(),
            'referrer_rewarded_at' => now(),
        ]);
    }
}
