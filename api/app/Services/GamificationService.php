<?php

namespace App\Services;

use App\Models\CourierQuest;
use App\Models\CourierQuestProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    /**
     * Appelé après chaque livraison réussie (DELIVERED).
     * Met à jour toutes les quêtes pertinentes du coursier.
     */
    public function onOrderDelivered(User $courier, Order $order): void
    {
        try {
            DB::transaction(function () use ($courier, $order) {
                $activeQuests = CourierQuest::where('is_active', true)->get();

                foreach ($activeQuests as $quest) {
                    $this->processQuestForDelivery($courier, $quest, $order);
                }
            });
        } catch (\Throwable $e) {
            // Gamification non-critique — on log mais on ne bloque pas la livraison
            Log::error('GamificationService::onOrderDelivered error', [
                'courier_id' => $courier->id,
                'order_id'   => $order->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Traite une quête spécifique suite à une livraison.
     */
    private function processQuestForDelivery(User $courier, CourierQuest $quest, Order $order): void
    {
        /** @var CourierQuestProgress $progress */
        $progress = CourierQuestProgress::firstOrCreate(
            ['courier_id' => $courier->id, 'quest_id' => $quest->id],
            ['current_value' => 0, 'completed' => false, 'reward_claimed' => false]
        );

        // Déjà complétée, on ne retraite pas
        if ($progress->completed) {
            return;
        }

        $newValue = match ($quest->quest_type) {
            'delivery_count' => $progress->current_value + 1,
            'revenue_target' => $progress->current_value + (int) ($order->price ?? 0),
            'streak_days'    => $this->computeStreakValue($courier, $progress),
            default          => $progress->current_value,
        };

        $justCompleted = !$progress->completed && $newValue >= $quest->target_value;

        $progress->update([
            'current_value' => $newValue,
            'completed'     => $justCompleted || $progress->completed,
            'completed_at'  => $justCompleted ? now() : $progress->completed_at,
        ]);

        if ($justCompleted) {
            $this->claimReward($courier, $quest, $progress);
        }
    }

    /**
     * Calcule le nombre de jours consécutifs de livraisons.
     * On regarde combien de jours distincts consécutifs (jusqu'à aujourd'hui) ont ≥1 livraison DELIVERED.
     */
    private function computeStreakValue(User $courier, CourierQuestProgress $progress): int
    {
        // On récupère les dates distinctes de livraisons passées (DELIVERED)
        $deliveryDates = DB::table('orders')
            ->where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->orderByDesc(DB::raw('DATE(delivered_at)'))
            ->pluck(DB::raw('DATE(delivered_at) as delivery_date'))
            ->map(fn($d) => \Carbon\Carbon::parse($d)->startOfDay())
            ->unique()
            ->values();

        if ($deliveryDates->isEmpty()) {
            return 0;
        }

        $streak  = 0;
        $current = now()->startOfDay();

        foreach ($deliveryDates as $date) {
            if ($date->equalTo($current)) {
                $streak++;
                $current = $current->subDay();
            } elseif ($date->lessThan($current)) {
                // Gap → streak brisé
                break;
            }
        }

        return $streak;
    }

    /**
     * Attribue le bonus FCFA au wallet du coursier et marque la récompense comme réclamée.
     */
    private function claimReward(User $courier, CourierQuest $quest, CourierQuestProgress $progress): void
    {
        try {
            if ($quest->bonus_xof > 0) {
                // Incrémente le solde wallet — on utilise increment pour l'atomicité
                $courier->increment('wallet_balance', $quest->bonus_xof);

                Log::info('GamificationService: quest bonus credited', [
                    'courier_id' => $courier->id,
                    'quest_key'  => $quest->key,
                    'bonus_xof'  => $quest->bonus_xof,
                ]);
            }

            $progress->update(['reward_claimed' => true]);
        } catch (\Throwable $e) {
            Log::error('GamificationService::claimReward error', [
                'courier_id' => $courier->id,
                'quest_id'   => $quest->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retourne toutes les quêtes actives avec la progression du coursier.
     *
     * @return Collection<int, array{quest: CourierQuest, progress: CourierQuestProgress|null, percent: float}>
     */
    public function getCourierQuestsWithProgress(User $courier): Collection
    {
        $quests    = CourierQuest::where('is_active', true)->orderBy('quest_type')->orderBy('target_value')->get();
        $progressMap = CourierQuestProgress::where('courier_id', $courier->id)
            ->get()
            ->keyBy('quest_id');

        return $quests->map(function (CourierQuest $quest) use ($progressMap) {
            /** @var CourierQuestProgress|null $progress */
            $progress = $progressMap->get($quest->id);

            return [
                'id'            => $quest->id,
                'key'           => $quest->key,
                'title'         => $quest->title,
                'description'   => $quest->description,
                'icon'          => $quest->icon,
                'quest_type'    => $quest->quest_type,
                'target_value'  => $quest->target_value,
                'bonus_xof'     => $quest->bonus_xof,
                'current_value' => $progress?->current_value ?? 0,
                'completed'     => $progress?->completed ?? false,
                'percent'       => $progress
                    ? $progress->progressPercent()
                    : 0.0,
                'completed_at'  => $progress?->completed_at?->toIso8601String(),
            ];
        });
    }
}
