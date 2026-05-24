<?php

namespace App\Filament\Widgets;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Complaint;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingActionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    private const CARD_CLASS = 'cursor-pointer hover:shadow-lg transition-shadow';

    protected function getStats(): array
    {
        $kycPending = User::where('role', UserRole::COURIER)
            ->where('kyc_status', KycStatus::PENDING)
            ->count();

        $withdrawalsPending = Withdrawal::where('status', 'pending')->count();
        $withdrawalsAmount = (float) Withdrawal::where('status', 'pending')->sum('amount');

        $openComplaints = Complaint::whereIn('status', ['open', 'in_progress'])->count();
        $urgentComplaints = Complaint::whereIn('status', ['open', 'in_progress'])
            ->where('priority', 'urgent')
            ->count();

        $withdrawalColor = $this->severityColor($withdrawalsPending, 5, 0);
        $complaintsColor = $this->severityColor($openComplaints, 0, 0, $urgentComplaints > 0);

        return [
            Stat::make('KYC à valider', $kycPending)
                ->description($kycPending > 0 ? 'Coursiers en attente' : 'Aucun en attente')
                ->descriptionIcon('heroicon-m-identification')
                ->color($kycPending > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.couriers.index', ['tableFilters[status][value]' => 'pending']))
                ->extraAttributes(['class' => self::CARD_CLASS]),

            Stat::make('Retraits à traiter', $withdrawalsPending)
                ->description(number_format($withdrawalsAmount, 0, ',', ' ') . ' FCFA')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($withdrawalColor)
                ->url(route('filament.admin.resources.withdrawals.index'))
                ->extraAttributes(['class' => self::CARD_CLASS]),

            Stat::make('Plaintes ouvertes', $openComplaints)
                ->description($urgentComplaints > 0 ? $urgentComplaints . ' urgente(s)' : 'Aucune urgente')
                ->descriptionIcon($urgentComplaints > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-chat-bubble-left-right')
                ->color($complaintsColor)
                ->url(route('filament.admin.resources.complaints.index'))
                ->extraAttributes(['class' => self::CARD_CLASS]),
        ];
    }

    private function severityColor(int $value, int $dangerThreshold, int $warningThreshold, bool $forceDanger = false): string
    {
        if ($forceDanger || $value > $dangerThreshold) {
            return 'danger';
        }
        if ($value > $warningThreshold) {
            return 'warning';
        }
        return 'success';
    }
}
