<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\InAppNotification;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotificationResource extends Resource
{
    protected static ?string $model = InAppNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $modelLabel = 'Notification';

    protected static ?string $pluralModelLabel = 'Notifications';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_read', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Destinataire')
                    ->description('Sélectionnez le destinataire de la notification')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Utilisateur #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Contenu de la notification')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'order_status' => '📦 Commande',
                                'payment' => '💳 Paiement',
                                'promo' => '🎁 Promotion',
                                'system' => '🔔 Système',
                                'wallet' => '💰 Portefeuille',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $icons = [
                                    'order_status' => 'package',
                                    'payment' => 'credit-card',
                                    'promo' => 'gift',
                                    'system' => 'bell',
                                    'wallet' => 'wallet',
                                ];
                                $colors = [
                                    'order_status' => 'blue',
                                    'payment' => 'green',
                                    'promo' => 'purple',
                                    'system' => 'gray',
                                    'wallet' => 'orange',
                                ];
                                $set('icon', $icons[$state] ?? 'bell');
                                $set('color', $colors[$state] ?? 'gray');
                            }),

                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Titre de la notification'),

                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contenu du message'),

                        Forms\Components\TextInput::make('action_url')
                            ->label('URL d\'action')
                            ->url()
                            ->placeholder('https://...'),

                        Forms\Components\Hidden::make('icon')->default('bell'),
                        Forms\Components\Hidden::make('color')->default('gray'),
                    ])->columns(1),
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

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'order_status' => '📦 Commande',
                        'payment' => '💳 Paiement',
                        'promo' => '🎁 Promotion',
                        'system' => '🔔 Système',
                        'wallet' => '💰 Portefeuille',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'order_status' => 'info',
                        'payment' => 'success',
                        'promo' => 'purple',
                        'system' => 'gray',
                        'wallet' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->message),

                Tables\Columns\IconColumn::make('is_read')
                    ->label('Lu')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-envelope')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('read_at')
                    ->label('Lu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'order_status' => 'Commande',
                        'payment' => 'Paiement',
                        'promo' => 'Promotion',
                        'system' => 'Système',
                        'wallet' => 'Portefeuille',
                    ]),

                Tables\Filters\TernaryFilter::make('is_read')
                    ->label('Statut lecture')
                    ->placeholder('Tous')
                    ->trueLabel('Lues')
                    ->falseLabel('Non lues'),

                Tables\Filters\Filter::make('today')
                    ->label('Aujourd\'hui')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_read')
                    ->label('Marquer lu')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_read)
                    ->action(function ($record) {
                        $record->update([
                            'is_read' => true,
                            'read_at' => now(),
                        ]);
                        Notification::make()
                            ->success()
                            ->title('Notification marquée comme lue')
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_all_read')
                        ->label('Marquer comme lues')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'is_read' => true,
                                    'read_at' => now(),
                                ]);
                            });
                            Notification::make()
                                ->success()
                                ->title($records->count() . ' notifications marquées comme lues')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'view' => Pages\ViewNotification::route('/{record}'),
        ];
    }
}
