<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Messages de contact';

    protected static ?string $modelLabel = 'Message';

    protected static ?string $pluralModelLabel = 'Messages de contact';

    protected static ?string $navigationGroup = 'Support Client';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_read', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du contact')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Sujet')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->disabled()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Réponse admin')
                    ->schema([
                        Forms\Components\Textarea::make('admin_reply')
                            ->label('Réponse')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Saisir votre réponse ici...'),
                        Forms\Components\Toggle::make('is_read')
                            ->label('Marqué comme lu')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('gray')
                    ->falseColor('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('admin_reply')
                    ->label('Répondu')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->admin_reply)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Statut de lecture')
                    ->placeholder('Tous')
                    ->trueLabel('Lus')
                    ->falseLabel('Non lus'),
                Tables\Filters\Filter::make('unreplied')
                    ->label('Sans réponse')
                    ->query(fn (Builder $query) => $query->whereNull('admin_reply')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Répondre'),
                Tables\Actions\Action::make('markRead')
                    ->label('Marquer lu')
                    ->icon('heroicon-o-check')
                    ->action(fn (ContactMessage $record) => $record->markAsRead())
                    ->visible(fn (ContactMessage $record) => !$record->is_read)
                    ->requiresConfirmation(false),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAllRead')
                        ->label('Marquer comme lus')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->markAsRead())
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}
