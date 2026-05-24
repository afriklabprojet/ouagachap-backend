<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?string $heading = '🕐 Dernières commandes';

    protected static ?int $sort = 8;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('N° Commande')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->icon('heroicon-m-user')
                    ->iconColor('gray'),
                Tables\Columns\TextColumn::make('pickup_address')
                    ->label('De')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->pickup_address),
                Tables\Columns\TextColumn::make('dropoff_address')
                    ->label('Vers')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->dropoff_address),
                Tables\Columns\TextColumn::make('courier.name')
                    ->label('Coursier')
                    ->icon('heroicon-m-truck')
                    ->iconColor('success')
                    ->placeholder('Non assigné')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn(OrderStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Prix')
                    ->money('XOF')
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m H:i')
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('d/m H:i')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Détails')
                    ->url(fn(Order $record): string => route('filament.admin.resources.orders.view', $record))
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->button()
                    ->size('sm'),
            ])
            ->striped()
            ->paginated(false);
    }
}
