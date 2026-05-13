<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\SubscriptionPlan;
use App\Models\Subscription;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'Abonnements';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (SubscriptionPlan $state): string => match ($state) {
                        SubscriptionPlan::PREMIUM => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (SubscriptionPlan $state): string => $state->label()),
                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('discount_xof')
                    ->label('Remise/livraison')
                    ->money('XOF'),
                Tables\Columns\IconColumn::make('priority_dispatch')
                    ->label('Prioritaire')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->color(fn (Subscription $record) => $record->ends_at->isPast() ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->getStateUsing(fn (Subscription $record) => $record->isActive()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
