<?php

namespace App\Filament\Resources\PromoCodeResource\RelationManagers;

use App\Models\PromoCodeUsage;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'Utilisations';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->description(fn (PromoCodeUsage $record) => $record->user?->phone),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Commande')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('discount_applied')
                    ->label('Remise appliquée')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Utilisé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
