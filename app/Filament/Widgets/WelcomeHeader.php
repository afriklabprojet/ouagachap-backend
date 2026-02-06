<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class WelcomeHeader extends Widget
{
    protected static string $view = 'filament.widgets.welcome-header';

    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    // Pas de polling pour l'en-tête
    protected static ?string $pollingInterval = null;

    public function getViewData(): array
    {
        $user = auth()->user();
        $hour = now()->hour;
        
        $greeting = match(true) {
            $hour < 12 => 'Bonjour',
            $hour < 18 => 'Bon après-midi',
            default => 'Bonsoir',
        };

        // Alertes importantes
        $alerts = $this->getAlerts();

        return [
            'greeting' => $greeting,
            'userName' => $user->name,
            'currentDate' => now()->translatedFormat('l j F Y'),
            'currentTime' => now()->format('H:i'),
            'alerts' => $alerts,
            'alertsCount' => count($alerts),
        ];
    }

    protected function getAlerts(): array
    {
        $alerts = [];

        // Commandes en attente depuis plus de 15 minutes
        $stuckOrders = Order::where('status', OrderStatus::PENDING)
            ->where('created_at', '<', now()->subMinutes(15))
            ->count();
        
        if ($stuckOrders > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-clock',
                'message' => "{$stuckOrders} commande(s) en attente depuis +15 min",
                'action' => route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'pending']),
            ];
        }

        // Retraits en attente
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();
        if ($pendingWithdrawals > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-banknotes',
                'message' => "{$pendingWithdrawals} retrait(s) à traiter",
                'action' => route('filament.admin.resources.withdrawals.index'),
            ];
        }

        // Coursiers hors ligne (si beaucoup)
        $totalCouriers = User::where('role', UserRole::COURIER)->count();
        $offlineCouriers = User::where('role', UserRole::COURIER)->where('is_available', false)->count();
        
        if ($totalCouriers > 0 && ($offlineCouriers / $totalCouriers) > 0.8) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'heroicon-o-user-minus',
                'message' => "Seulement " . ($totalCouriers - $offlineCouriers) . " coursier(s) en ligne",
                'action' => route('filament.admin.resources.couriers.index'),
            ];
        }

        return $alerts;
    }
}
