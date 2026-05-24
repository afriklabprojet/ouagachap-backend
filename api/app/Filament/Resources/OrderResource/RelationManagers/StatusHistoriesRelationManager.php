<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderStatusHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = 'Historique des statuts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('previous_status')
                    ->label('Ancien statut')
                    ->badge()
                    ->color(fn ($state) => $state instanceof \App\Enums\OrderStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\OrderStatus ? $state->label() : '—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Nouveau statut')
                    ->badge()
                    ->color(fn ($state) => $state instanceof \App\Enums\OrderStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\OrderStatus ? $state->label() : '—'),
                Tables\Columns\TextColumn::make('changedBy.name')
                    ->label('Changé par')
                    ->placeholder('Système'),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->limit(50)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Latitude')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('longitude')
                    ->label('Longitude')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }
}
