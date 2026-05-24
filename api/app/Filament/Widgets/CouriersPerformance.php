<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CouriersPerformance extends BaseWidget
{
    protected static ?string $heading = '🏆 Top Coursiers du mois';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', UserRole::COURIER)
                    ->whereHas('courierOrders', function (Builder $query) {
                        $query->where('status', OrderStatus::DELIVERED)
                            ->whereMonth('delivered_at', now()->month)
                            ->whereYear('delivered_at', now()->year);
                    })
                    ->withCount(['courierOrders as deliveries_count' => function (Builder $query) {
                        $query->where('status', OrderStatus::DELIVERED)
                            ->whereMonth('delivered_at', now()->month)
                            ->whereYear('delivered_at', now()->year);
                    }])
                    ->withSum(['courierOrders as earnings' => function (Builder $query) {
                        $query->where('status', OrderStatus::DELIVERED)
                            ->whereMonth('delivered_at', now()->month)
                            ->whereYear('delivered_at', now()->year);
                    }], 'courier_earnings')
                    ->orderByDesc('deliveries_count')
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->badge()
                    ->color(fn ($rowLoop) => match($rowLoop->index) {
                        0 => 'warning', // Gold
                        1 => 'gray', // Silver  
                        2 => 'danger', // Bronze
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Coursier')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-user')
                    ->iconColor('primary'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->iconColor('gray')
                    ->copyable(),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Véhicule')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'moto' => '🏍️ Moto',
                        'velo' => '🚲 Vélo',
                        'voiture' => '🚗 Voiture',
                        default => $state,
                    })
                    ->color('info'),
                Tables\Columns\TextColumn::make('deliveries_count')
                    ->label('Livraisons')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle'),
                Tables\Columns\TextColumn::make('earnings')
                    ->label('Gains')
                    ->money('XOF')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Note')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'N/A')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 4.0 => 'info',
                        $state >= 3.0 => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->striped();
    }
}
