<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class QuickActions extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'pendingWithdrawals' => Withdrawal::where('status', 'pending')->count(),
            'pendingWithdrawalsAmount' => Withdrawal::where('status', 'pending')->sum('amount'),
            'pendingCouriers' => User::where('role', UserRole::COURIER)
                ->where('status', 'pending')
                ->count(),
            'unreadNotifications' => DB::table('notifications')->whereNull('read_at')->count(),
            'todayNewClients' => User::where('role', UserRole::CLIENT)
                ->whereDate('created_at', today())
                ->count(),
        ];
    }
}
