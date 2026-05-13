<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use App\Models\WalletTransaction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transactions';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('transaction_id')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('created_at', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID')
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'recharge' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'recharge' ? 'Recharge' : 'Débit'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'orange_money' => 'Orange Money',
                        'moov_money' => 'Moov Money',
                        'wave' => 'Wave',
                        'mtn_money' => 'MTN Money',
                        'djamo' => 'Djamo',
                        'cash' => 'Espèces',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Réussi',
                        'failed' => 'Échoué',
                        default => 'En attente',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['recharge' => 'Recharge', 'debit' => 'Débit']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'En attente', 'success' => 'Réussi', 'failed' => 'Échoué']),
            ]);
    }
}
