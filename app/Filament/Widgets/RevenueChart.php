<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = '💰 Revenus des 7 derniers jours';

    protected static ?int $sort = 6;

    protected static ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected function getData(): array
    {
        $data = collect(range(6, 0))->map(function ($daysAgo) {
            $date = now()->subDays($daysAgo);
            
            $revenue = Order::whereDate('delivered_at', $date)
                ->where('status', OrderStatus::DELIVERED)
                ->sum('platform_fee');
            
            $courierEarnings = Order::whereDate('delivered_at', $date)
                ->where('status', OrderStatus::DELIVERED)
                ->sum('courier_earnings');
            
            return [
                'date' => $date->translatedFormat('D'),
                'revenue' => $revenue,
                'courier' => $courierEarnings,
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Revenus plateforme',
                    'data' => $data->pluck('revenue')->toArray(),
                    'backgroundColor' => 'rgba(249, 115, 22, 0.2)',
                    'borderColor' => 'rgb(249, 115, 22)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(249, 115, 22)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 8,
                ],
                [
                    'label' => 'Gains coursiers',
                    'data' => $data->pluck('courier')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(16, 185, 129)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 8,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => "function(context) { return context.dataset.label + ': ' + new Intl.NumberFormat('fr-FR').format(context.raw) + ' FCFA'; }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'callback' => "function(value) { return new Intl.NumberFormat('fr-FR').format(value) + ' F'; }",
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
