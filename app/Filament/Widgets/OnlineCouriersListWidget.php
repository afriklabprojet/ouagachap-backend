<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class OnlineCouriersListWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = '📍 Coursiers actuellement en ligne';
    
    // Rafraîchir toutes les 15 secondes
    protected static ?string $pollingInterval = '15s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'coursier')
                    ->where('is_online', true)
                    ->orderBy('last_location_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Véhicule')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'moto' => '🏍️ Moto',
                        'velo' => '🚲 Vélo',
                        'voiture' => '🚗 Voiture',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'moto' => 'primary',
                        'velo' => 'info',
                        'voiture' => 'gray',
                        default => 'secondary',
                    }),
                    
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Disponible')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-truck')
                    ->trueColor('success')
                    ->falseColor('warning'),
                    
                Tables\Columns\TextColumn::make('current_latitude')
                    ->label('Position')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->current_latitude && $record->current_longitude) {
                            return "📍 {$record->current_latitude}, {$record->current_longitude}";
                        }
                        return '📍 Non disponible';
                    }),
                    
                Tables\Columns\TextColumn::make('last_location_at')
                    ->label('Dernière MAJ')
                    ->since()
                    ->sortable()
                    ->description(fn ($record) => $record->last_location_at?->format('H:i:s')),
            ])
            ->actions([
                Tables\Actions\Action::make('call')
                    ->label('Appeler')
                    ->icon('heroicon-m-phone')
                    ->color('success')
                    ->url(fn (User $record): string => "tel:{$record->phone}"),
                    
                Tables\Actions\Action::make('map')
                    ->label('Carte')
                    ->icon('heroicon-m-map')
                    ->color('info')
                    ->url(fn (User $record): string => 
                        $record->current_latitude && $record->current_longitude
                            ? "https://www.google.com/maps?q={$record->current_latitude},{$record->current_longitude}"
                            : '#'
                    )
                    ->openUrlInNewTab()
                    ->visible(fn (User $record): bool => 
                        $record->current_latitude && $record->current_longitude
                    ),
            ])
            ->emptyStateHeading('Aucun coursier en ligne')
            ->emptyStateDescription('Aucun coursier n\'est actuellement disponible.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->defaultSort('last_location_at', 'desc')
            ->striped();
    }
}
