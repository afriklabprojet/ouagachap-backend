<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportChatResource\Pages;
use App\Filament\Resources\SupportChatResource\RelationManagers;
use App\Models\SupportChat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportChatResource extends Resource
{
    protected static ?string $model = SupportChat::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationLabel = 'Chat Support';
    
    protected static ?string $modelLabel = 'Conversation';
    
    protected static ?string $pluralModelLabel = 'Conversations';
    
    protected static ?string $navigationGroup = 'Support Client';
    
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'open')
            ->whereHas('messages', fn ($q) => $q->where('is_admin', false)->where('is_read', false))
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Conversation')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Utilisateur #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\TextInput::make('subject')
                            ->label('Sujet')
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'open' => '🟢 Ouverte',
                                'closed' => '⚫ Fermée',
                            ])
                            ->default('open')
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Téléphone')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('subject')
                    ->label('Sujet')
                    ->limit(30)
                    ->placeholder('Sans sujet'),
                    
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('unread_messages_count')
                    ->label('Non lus')
                    ->counts([
                        'unreadMessages' => fn (Builder $query) => $query->where('is_admin', false)->where('is_read', false),
                    ])
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => '🟢 Ouverte',
                        'closed' => '⚫ Fermée',
                    }),
                    
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Dernier message')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Aucun message'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'open' => 'Ouverte',
                        'closed' => 'Fermée',
                    ]),
                    
                Tables\Filters\Filter::make('unread')
                    ->label('Avec messages non lus')
                    ->query(fn (Builder $query): Builder => $query->whereHas('messages', fn ($q) => $q->where('is_admin', false)->where('is_read', false))),
            ])
            ->actions([
                Tables\Actions\Action::make('chat')
                    ->label('Ouvrir')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('primary')
                    ->url(fn (SupportChat $record): string => static::getUrl('view', ['record' => $record])),
                    
                Tables\Actions\Action::make('close')
                    ->label('Fermer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SupportChat $record): bool => $record->status === 'open')
                    ->requiresConfirmation()
                    ->action(fn (SupportChat $record) => $record->update(['status' => 'closed'])),
                    
                Tables\Actions\Action::make('reopen')
                    ->label('Rouvrir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (SupportChat $record): bool => $record->status === 'closed')
                    ->action(fn (SupportChat $record) => $record->update(['status' => 'open'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('close_all')
                        ->label('Fermer sélectionnées')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'closed'])),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportChats::route('/'),
            'create' => Pages\CreateSupportChat::route('/create'),
            'view' => Pages\ViewSupportChat::route('/{record}'),
            'edit' => Pages\EditSupportChat::route('/{record}/edit'),
        ];
    }
}
