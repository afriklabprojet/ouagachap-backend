<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Commandes';
    protected static ?string $modelLabel = 'Commande';
    protected static ?string $pluralModelLabel = 'Commandes';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Commandes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails commande')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('N° commande')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(collect(OrderStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                            ->required(),
                        Forms\Components\TextInput::make('total_price')
                            ->label('Prix total (FCFA)')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('distance_km')
                            ->label('Distance (km)')
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Enlèvement')
                    ->schema([
                        Forms\Components\TextInput::make('pickup_address')
                            ->label('Adresse enlèvement')
                            ->disabled(),
                        Forms\Components\TextInput::make('pickup_contact_name')
                            ->label('Contact enlèvement')
                            ->disabled(),
                        Forms\Components\TextInput::make('pickup_contact_phone')
                            ->label('Téléphone enlèvement')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Livraison')
                    ->schema([
                        Forms\Components\TextInput::make('dropoff_address')
                            ->label('Adresse livraison')
                            ->disabled(),
                        Forms\Components\TextInput::make('dropoff_contact_name')
                            ->label('Contact livraison')
                            ->disabled(),
                        Forms\Components\TextInput::make('dropoff_contact_phone')
                            ->label('Téléphone livraison')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('N° Commande')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Order $record) => $record->client?->phone)
                    ->default('Inconnu'),
                Tables\Columns\TextColumn::make('courier.name')
                    ->label('Livreur')
                    ->searchable()
                    ->description(fn (Order $record) => $record->courier?->phone)
                    ->default('Non assigné'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('XOF')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('courier_earnings')
                    ->label('Gains coursier')
                    ->money('XOF')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pickup_address')
                    ->label('Enlèvement')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('dropoff_address')
                    ->label('Livraison')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Paiement')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('zone_id')
                    ->label('Zone')
                    ->relationship('zone', 'name'),
                Tables\Filters\Filter::make('no_courier')
                    ->label('Sans coursier')
                    ->query(fn ($query) => $query->whereNull('courier_id')),
                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn ($query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('assign_courier')
                    ->label('Assigner coursier')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PENDING, OrderStatus::ASSIGNED]))
                    ->form([
                        Forms\Components\Select::make('courier_id')
                            ->label('Coursier')
                            ->options(
                                User::where('role', UserRole::COURIER)
                                    ->where('is_online', true)
                                    ->get()
                                    ->mapWithKeys(fn ($u) => [$u->id => ($u->name ?? $u->phone) . ' — ⭐ ' . number_format((float) ($u->average_rating ?? 0), 1)])
                            )
                            ->searchable()
                            ->required()
                            ->helperText('Seuls les coursiers en ligne sont affichés'),
                    ])
                    ->action(function (Order $record, array $data) {
                        $courier = User::findOrFail($data['courier_id']);
                        $success = $record->assign($courier, auth()->id());
                        if ($success) {
                            Notification::make()->title('Coursier assigné avec succès')->success()->send();
                        } else {
                            Notification::make()->title('Impossible d\'assigner ce coursier')->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('change_status')
                    ->label('Changer statut')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Order $record) => !$record->isCancelled() && !$record->isCompleted())
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nouveau statut')
                            ->options(fn (Order $record) => collect(OrderStatus::cases())
                                ->filter(fn ($s) => $record->canTransitionTo($s))
                                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                            )
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->label('Note interne')
                            ->rows(2),
                    ])
                    ->action(function (Order $record, array $data) {
                        $newStatus = OrderStatus::from($data['status']);
                        $success = $record->transitionTo($newStatus, auth()->id(), $data['note'] ?? null);
                        if ($success) {
                            Notification::make()->title('Statut mis à jour : ' . $newStatus->label())->success()->send();
                        } else {
                            Notification::make()->title('Transition de statut non autorisée')->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel_order')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => !$record->isCancelled() && !$record->isCompleted())
                    ->form([
                        Forms\Components\TextInput::make('reason')
                            ->label('Raison d\'annulation')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->cancel($data['reason'], auth()->id());
                        Notification::make()->title('Commande annulée')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_cancel')
                        ->label('Annuler sélectionnées')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('reason')
                                ->label('Raison')
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(fn ($r) => $r->cancel($data['reason'], auth()->id()));
                            Notification::make()->title('Commandes annulées')->warning()->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Commande')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number')->label('N° Commande')->copyable()->fontFamily('mono'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (OrderStatus $state) => $state->color())
                            ->formatStateUsing(fn (OrderStatus $state) => $state->label()),
                        Infolists\Components\TextEntry::make('zone.name')->label('Zone'),
                        Infolists\Components\TextEntry::make('payment_method')->label('Méthode paiement'),
                    ])->columns(4),

                Infolists\Components\Section::make('Parties')
                    ->schema([
                        Infolists\Components\TextEntry::make('client.name')->label('Client')
                            ->description(fn ($record) => $record->client?->phone),
                        Infolists\Components\TextEntry::make('courier.name')->label('Coursier')
                            ->placeholder('Non assigné')
                            ->description(fn ($record) => $record->courier?->phone),
                    ])->columns(2),

                Infolists\Components\Section::make('Enlèvement')
                    ->schema([
                        Infolists\Components\TextEntry::make('pickup_address')->label('Adresse')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('pickup_contact_name')->label('Contact'),
                        Infolists\Components\TextEntry::make('pickup_contact_phone')->label('Téléphone'),
                        Infolists\Components\TextEntry::make('pickup_instructions')->label('Instructions')->placeholder('—'),
                    ])->columns(3),

                Infolists\Components\Section::make('Livraison')
                    ->schema([
                        Infolists\Components\TextEntry::make('dropoff_address')->label('Adresse')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('dropoff_contact_name')->label('Contact'),
                        Infolists\Components\TextEntry::make('dropoff_contact_phone')->label('Téléphone'),
                        Infolists\Components\TextEntry::make('dropoff_instructions')->label('Instructions')->placeholder('—'),
                    ])->columns(3),

                Infolists\Components\Section::make('Finances')
                    ->schema([
                        Infolists\Components\TextEntry::make('base_price')->label('Prix de base')->money('XOF'),
                        Infolists\Components\TextEntry::make('distance_price')->label('Prix distance')->money('XOF'),
                        Infolists\Components\TextEntry::make('subscription_discount')->label('Remise abonnement')->money('XOF')->placeholder('—'),
                        Infolists\Components\TextEntry::make('total_price')->label('Total')->money('XOF')->weight('bold'),
                        Infolists\Components\TextEntry::make('commission_amount')->label('Commission')->money('XOF'),
                        Infolists\Components\TextEntry::make('courier_earnings')->label('Gains coursier')->money('XOF'),
                        Infolists\Components\TextEntry::make('distance_km')->label('Distance')->suffix(' km'),
                    ])->columns(4),

                Infolists\Components\Section::make('Colis')
                    ->schema([
                        Infolists\Components\TextEntry::make('package_description')->label('Description')->placeholder('—'),
                        Infolists\Components\TextEntry::make('package_size')->label('Taille')->placeholder('—'),
                    ])->columns(2),

                Infolists\Components\Section::make('Évaluations')
                    ->schema([
                        Infolists\Components\TextEntry::make('client_rating')->label('Note client')->placeholder('—'),
                        Infolists\Components\TextEntry::make('client_review')->label('Avis client')->placeholder('—'),
                        Infolists\Components\TextEntry::make('courier_rating')->label('Note coursier')->placeholder('—'),
                        Infolists\Components\TextEntry::make('courier_review')->label('Avis coursier')->placeholder('—'),
                    ])->columns(2),

                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('assigned_at')->label('Assignée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('picked_up_at')->label('Récupérée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('delivered_at')->label('Livrée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('cancelled_at')->label('Annulée le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('cancellation_reason')->label('Raison annulation')->placeholder('—'),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StatusHistoriesRelationManager::class,
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view'  => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', OrderStatus::PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
