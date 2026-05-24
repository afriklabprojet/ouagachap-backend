<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RevenueByZoneWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected static ?string $heading = 'Revenus par zone — 7 derniers jours';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->select('zone_id')
                    ->selectRaw('COUNT(*) as total_orders')
                    ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as delivered_orders', [OrderStatus::DELIVERED->value])
                    ->selectRaw('SUM(CASE WHEN status = ? THEN commission_amount ELSE 0 END) as total_commission', [OrderStatus::DELIVERED->value])
                    ->selectRaw('SUM(CASE WHEN status = ? THEN total_price ELSE 0 END) as total_revenue', [OrderStatus::DELIVERED->value])
                    ->selectRaw('AVG(CASE WHEN status = ? AND delivered_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, delivered_at) END) as avg_delivery_minutes', [OrderStatus::DELIVERED->value])
                    ->where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('zone_id')
                    ->with('zone:id,name,code')
                    ->groupBy('zone_id')
                    ->orderByDesc('total_revenue')
            )
            ->columns([
                TextColumn::make('zone.name')
                    ->label('Zone')
                    ->badge()
                    ->color('primary')
                    ->description(fn ($record) => $record->zone?->code),

                TextColumn::make('total_orders')
                    ->label('Commandes')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('delivered_orders')
                    ->label('Livrées')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, $record) {
                        $rate = $record->total_orders > 0
                            ? round(($state / $record->total_orders) * 100)
                            : 0;
                        return $state . ' (' . $rate . '%)';
                    }),

                TextColumn::make('total_revenue')
                    ->label('CA brut')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' F')
                    ->sortable(),

                TextColumn::make('total_commission')
                    ->label('Commission plateforme')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 0, ',', ' ') . ' F')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('avg_delivery_minutes')
                    ->label('Délai moyen')
                    ->formatStateUsing(fn ($state) => $state ? (int) round($state) . ' min' : '—')
                    ->alignCenter(),
            ])
            ->paginated(false)
            ->striped();
    }
}
