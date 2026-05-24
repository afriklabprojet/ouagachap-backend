<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\OrderMessage;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('sender_type')
                    ->label('De')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'client' ? 'primary' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'client' ? 'Client' : 'Coursier'),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Expéditeur')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_read')
                    ->label('Lu')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(false);
    }
}
