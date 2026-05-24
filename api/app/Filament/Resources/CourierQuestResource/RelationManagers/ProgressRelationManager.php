<?php

namespace App\Filament\Resources\CourierQuestResource\RelationManagers;

use App\Models\CourierQuestProgress;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProgressRelationManager extends RelationManager
{
    protected static string $relationship = 'progress';

    protected static ?string $title = 'Progression des coursiers';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('courier_id')
            ->columns([
                Tables\Columns\TextColumn::make('courier.name')
                    ->label('Coursier')
                    ->searchable()
                    ->description(fn (CourierQuestProgress $record) => $record->courier?->phone),
                Tables\Columns\TextColumn::make('current_value')
                    ->label('Progression')
                    ->formatStateUsing(fn (CourierQuestProgress $record) => $record->current_value . ' / ' . $record->quest->target_value),
                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('% Complété')
                    ->getStateUsing(fn (CourierQuestProgress $record) => $record->progressPercent() . '%'),
                Tables\Columns\IconColumn::make('completed')
                    ->label('Terminé')
                    ->boolean(),
                Tables\Columns\IconColumn::make('reward_claimed')
                    ->label('Récompense réclamée')
                    ->boolean(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complété le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('completed')->label('Terminé'),
                Tables\Filters\TernaryFilter::make('reward_claimed')->label('Récompense réclamée'),
            ])
            ->actions([
                Tables\Actions\Action::make('reset')
                    ->label('Réinitialiser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (CourierQuestProgress $record) => $record->update([
                        'current_value' => 0,
                        'completed' => false,
                        'reward_claimed' => false,
                        'completed_at' => null,
                    ])),
            ]);
    }
}
