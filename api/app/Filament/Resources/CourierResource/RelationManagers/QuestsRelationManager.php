<?php

namespace App\Filament\Resources\CourierResource\RelationManagers;

use App\Models\CourierQuestProgress;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestsRelationManager extends RelationManager
{
    protected static string $relationship = 'questProgress';

    protected static ?string $title = 'Quêtes & Progression';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('quest'))
            ->columns([
                Tables\Columns\TextColumn::make('quest.icon')
                    ->label('')
                    ->width(40),
                Tables\Columns\TextColumn::make('quest.title')
                    ->label('Quête')
                    ->description(fn (CourierQuestProgress $record) => $record->quest?->quest_type),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progression')
                    ->getStateUsing(fn (CourierQuestProgress $record) => $record->current_value . ' / ' . ($record->quest?->target_value ?? '?'))
                    ->description(fn (CourierQuestProgress $record) => $record->progressPercent() . '%'),
                Tables\Columns\TextColumn::make('quest.bonus_xof')
                    ->label('Bonus')
                    ->money('XOF'),
                Tables\Columns\IconColumn::make('completed')
                    ->label('Terminé')
                    ->boolean(),
                Tables\Columns\IconColumn::make('reward_claimed')
                    ->label('Réclamé')
                    ->boolean(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complété le')
                    ->dateTime('d/m/Y')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('completed')->label('Terminé'),
            ]);
    }
}
