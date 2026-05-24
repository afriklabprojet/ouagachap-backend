<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrdersByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Répartition des commandes (7 derniers jours)';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'xl'      => 1,
    ];

    protected static ?string $pollingInterval = '60s';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $rows = Order::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $labels = [];
        $values = [];
        $colors = [];

        $palette = [
            'pending' => ['Pending', '#f59e0b'],
            'accepted' => ['Acceptée', '#3b82f6'],
            'picked_up' => ['Récupérée', '#6366f1'],
            'in_progress' => ['En cours', '#8b5cf6'],
            'delivered' => ['Livrée', '#10b981'],
            'cancelled' => ['Annulée', '#ef4444'],
            'refunded' => ['Remboursée', '#6b7280'],
        ];

        foreach ($rows as $status => $total) {
            $key = is_object($status) ? $status->value : (string) $status;
            [$label, $color] = $palette[$key] ?? [ucfirst($key), '#9ca3af'];
            $labels[] = $label;
            $values[] = (int) $total;
            $colors[] = $color;
        }

        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
