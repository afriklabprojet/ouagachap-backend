<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'Commande';

    protected static ?string $pluralModelLabel = 'Commandes';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Opérations';

    protected static ?string $recordTitleAttribute = 'order_number';

    // ==================== EAGER LOADING (Performance) ====================
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['client:id,name,phone', 'courier:id,name,phone', 'zone:id,name', 'payment:id,order_id,status,amount']);
    }

    // ==================== FORM ====================
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informations générales')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->label('N° Commande')
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1),
                                Forms\Components\Select::make('client_id')
                                    ->label('Client')
                                    ->relationship('client', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Client #{$record->id}")
                                    ->searchable()
                                    ->preload()
                                    ->disabled()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('courier_id')
                                    ->label('Coursier')
                                    ->relationship('courier', 'name', fn (Builder $query) => $query->where('role', 'courier'))
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Coursier #{$record->id}")
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(1),
                                Forms\Components\Select::make('zone_id')
                                    ->label('Zone')
                                    ->relationship('zone', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->columnSpan(2),
                            ])->columns(2),

                        Forms\Components\Section::make('📍 Récupération')
                            ->icon('heroicon-o-arrow-up-tray')
                            ->collapsible()
                            ->schema([
                                Forms\Components\TextInput::make('pickup_address')
                                    ->label('Adresse')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('pickup_contact_name')
                                    ->label('Contact')
                                    ->required(),
                                Forms\Components\TextInput::make('pickup_contact_phone')
                                    ->label('Téléphone')
                                    ->required()
                                    ->tel(),
                                Forms\Components\Textarea::make('pickup_instructions')
                                    ->label('Instructions')
                                    ->rows(2)
                                    ->columnSpan(2),
                            ])->columns(2),

                        Forms\Components\Section::make('📦 Livraison')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->collapsible()
                            ->schema([
                                Forms\Components\TextInput::make('dropoff_address')
                                    ->label('Adresse')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('dropoff_contact_name')
                                    ->label('Contact')
                                    ->required(),
                                Forms\Components\TextInput::make('dropoff_contact_phone')
                                    ->label('Téléphone')
                                    ->required()
                                    ->tel(),
                                Forms\Components\Textarea::make('dropoff_instructions')
                                    ->label('Instructions')
                                    ->rows(2)
                                    ->columnSpan(2),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('📦 Colis')
                            ->schema([
                                Forms\Components\Textarea::make('package_description')
                                    ->label('Description')
                                    ->required()
                                    ->rows(3),
                                Forms\Components\Select::make('package_size')
                                    ->label('Taille')
                                    ->options([
                                        'small' => '📦 Petit (< 5kg)',
                                        'medium' => '📦📦 Moyen (5-15kg)',
                                        'large' => '📦📦📦 Grand (> 15kg)',
                                    ])
                                    ->required()
                                    ->native(false),
                            ]),

                        Forms\Components\Section::make('💰 Tarification')
                            ->schema([
                                Forms\Components\TextInput::make('distance_km')
                                    ->label('Distance')
                                    ->suffix('km')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('base_price')
                                    ->label('Prix de base')
                                    ->suffix('FCFA')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('distance_price')
                                    ->label('Prix distance')
                                    ->suffix('FCFA')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Prix total')
                                    ->suffix('FCFA')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('commission_amount')
                                    ->label('Commission')
                                    ->suffix('FCFA')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('courier_earnings')
                                    ->label('Gain coursier')
                                    ->suffix('FCFA')
                                    ->numeric()
                                    ->disabled(),
                            ]),

                        Forms\Components\Section::make('⭐ Évaluations')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Forms\Components\Placeholder::make('courier_rating_display')
                                    ->label('Note coursier')
                                    ->content(fn (?Order $record): string => $record?->courier_rating ? str_repeat('⭐', $record->courier_rating) : 'Non noté'),
                                Forms\Components\Placeholder::make('client_rating_display')
                                    ->label('Note client')
                                    ->content(fn (?Order $record): string => $record?->client_rating ? str_repeat('⭐', $record->client_rating) : 'Non noté'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    // ==================== INFOLIST (VIEW) ====================
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('order_number')
                                    ->label('N° Commande')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Statut')
                                    ->badge()
                                    ->color(fn (OrderStatus $state): string => $state->color())
                                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
                            ])->columns(2),

                        Infolists\Components\Section::make('📍 Suivi de la commande')
                            ->schema([
                                Infolists\Components\Grid::make(1)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('🔵 Commande créée')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-plus-circle')
                                            ->iconColor('primary'),
                                        Infolists\Components\TextEntry::make('assigned_at')
                                            ->label('🟠 Coursier assigné')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-user-plus')
                                            ->iconColor('warning')
                                            ->placeholder('En attente'),
                                        Infolists\Components\TextEntry::make('picked_up_at')
                                            ->label('🟡 Colis récupéré')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-arrow-up-tray')
                                            ->iconColor('info')
                                            ->placeholder('En attente'),
                                        Infolists\Components\TextEntry::make('delivered_at')
                                            ->label('🟢 Livré')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-check-circle')
                                            ->iconColor('success')
                                            ->placeholder('En attente'),
                                        Infolists\Components\TextEntry::make('cancelled_at')
                                            ->label('🔴 Annulée')
                                            ->dateTime('d/m/Y H:i')
                                            ->icon('heroicon-o-x-circle')
                                            ->iconColor('danger')
                                            ->visible(fn (?Order $record): bool => $record?->cancelled_at !== null),
                                        Infolists\Components\TextEntry::make('cancellation_reason')
                                            ->label('Motif annulation')
                                            ->visible(fn (?Order $record): bool => $record?->cancellation_reason !== null),
                                    ]),
                            ]),

                        Infolists\Components\Section::make('👥 Acteurs')
                            ->schema([
                                Infolists\Components\TextEntry::make('client.name')
                                    ->label('Client')
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('client.phone')
                                    ->label('Tél. Client')
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('courier.name')
                                    ->label('Coursier')
                                    ->icon('heroicon-o-truck')
                                    ->default('Non assigné'),
                                Infolists\Components\TextEntry::make('courier.phone')
                                    ->label('Tél. Coursier')
                                    ->icon('heroicon-o-phone')
                                    ->copyable()
                                    ->default('-'),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make('📍 Récupération')
                            ->schema([
                                Infolists\Components\TextEntry::make('pickup_address')
                                    ->label('Adresse')
                                    ->icon('heroicon-o-map-pin'),
                                Infolists\Components\TextEntry::make('pickup_contact_name')
                                    ->label('Contact'),
                                Infolists\Components\TextEntry::make('pickup_contact_phone')
                                    ->label('Téléphone')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('pickup_instructions')
                                    ->label('Instructions')
                                    ->placeholder('Aucune'),
                            ]),

                        Infolists\Components\Section::make('📦 Livraison')
                            ->schema([
                                Infolists\Components\TextEntry::make('dropoff_address')
                                    ->label('Adresse')
                                    ->icon('heroicon-o-map-pin'),
                                Infolists\Components\TextEntry::make('dropoff_contact_name')
                                    ->label('Contact'),
                                Infolists\Components\TextEntry::make('dropoff_contact_phone')
                                    ->label('Téléphone')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('dropoff_instructions')
                                    ->label('Instructions')
                                    ->placeholder('Aucune'),
                            ]),

                        Infolists\Components\Section::make('💰 Détails')
                            ->schema([
                                Infolists\Components\TextEntry::make('package_description')
                                    ->label('Colis'),
                                Infolists\Components\TextEntry::make('package_size')
                                    ->label('Taille')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'small' => 'Petit',
                                        'medium' => 'Moyen',
                                        'large' => 'Grand',
                                        default => $state,
                                    }),
                                Infolists\Components\TextEntry::make('distance_km')
                                    ->label('Distance')
                                    ->suffix(' km'),
                                Infolists\Components\TextEntry::make('total_price')
                                    ->label('Prix Total')
                                    ->money('XOF')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('courier_earnings')
                                    ->label('Gain Coursier')
                                    ->money('XOF'),
                            ]),

                        Infolists\Components\Section::make('⭐ Évaluations')
                            ->schema([
                                Infolists\Components\TextEntry::make('courier_rating')
                                    ->label('Note du coursier')
                                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('⭐', $state) : 'Non noté'),
                                Infolists\Components\TextEntry::make('courier_review')
                                    ->label('Avis')
                                    ->placeholder('Aucun avis'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    // ==================== TABLE ====================
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('N° Commande')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('courier.name')
                    ->label('Coursier')
                    ->searchable()
                    ->sortable()
                    ->default('⏳ En attente')
                    ->color(fn (?string $state): string => $state === '⏳ En attente' ? 'warning' : 'success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (OrderStatus $state): string => $state->color())
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->sortable(),
                Tables\Columns\TextColumn::make('zone.name')
                    ->label('Zone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('package_size')
                    ->label('Taille')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'small' => '📦 Petit',
                        'medium' => '📦📦 Moyen',
                        'large' => '📦📦📦 Grand',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Prix')
                    ->money('XOF')
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('XOF')
                            ->label('Total'),
                    ]),
                Tables\Columns\TextColumn::make('distance_km')
                    ->label('Distance')
                    ->suffix(' km')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Livrée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('zone')
                    ->label('Zone')
                    ->relationship('zone', 'name')
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('courier')
                    ->label('Coursier')
                    ->relationship('courier', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Coursier #{$record->id}")
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('package_size')
                    ->label('Taille colis')
                    ->options([
                        'small' => 'Petit',
                        'medium' => 'Moyen',
                        'large' => 'Grand',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au')
                            ->native(false),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'À partir du ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Jusqu\'au ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
                Tables\Filters\TernaryFilter::make('has_courier')
                    ->label('Coursier assigné')
                    ->placeholder('Toutes')
                    ->trueLabel('Avec coursier')
                    ->falseLabel('Sans coursier')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('courier_id'),
                        false: fn (Builder $query) => $query->whereNull('courier_id'),
                    ),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    
                    Tables\Actions\Action::make('assign_courier')
                        ->label('Assigner')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->visible(fn (Order $record): bool => $record->status === OrderStatus::PENDING && !$record->courier_id)
                        ->form([
                            Forms\Components\Select::make('courier_id')
                                ->label('Sélectionner un coursier')
                                ->options(fn () => User::where('role', 'courier')
                                    ->where('is_available', true)
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => $user->name ?? $user->phone ?? "Coursier #{$user->id}"
                                    ])
                                )
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Order $record, array $data): void {
                            $record->update([
                                'courier_id' => $data['courier_id'],
                                'status' => OrderStatus::ASSIGNED,
                                'assigned_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Coursier assigné')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('change_status')
                        ->label('Changer statut')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Nouveau statut')
                                ->options(collect(OrderStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                                ->required()
                                ->native(false),
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif (si annulation)')
                                ->visible(fn (Forms\Get $get): bool => $get('status') === 'cancelled'),
                        ])
                        ->action(function (Order $record, array $data): void {
                            $updates = ['status' => $data['status']];
                            
                            if ($data['status'] === 'cancelled') {
                                $updates['cancelled_at'] = now();
                                $updates['cancellation_reason'] = $data['reason'] ?? null;
                            } elseif ($data['status'] === 'picked_up') {
                                $updates['picked_up_at'] = now();
                            } elseif ($data['status'] === 'delivered') {
                                $updates['delivered_at'] = now();
                            }
                            
                            $record->update($updates);
                            Notification::make()
                                ->title('Statut mis à jour')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Order $record): bool => !in_array($record->status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED]))
                        ->requiresConfirmation()
                        ->modalHeading('Annuler la commande')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif d\'annulation')
                                ->required(),
                        ])
                        ->action(function (Order $record, array $data): void {
                            $record->update([
                                'status' => OrderStatus::CANCELLED,
                                'cancelled_at' => now(),
                                'cancellation_reason' => $data['reason'],
                            ]);
                            Notification::make()
                                ->title('Commande annulée')
                                ->warning()
                                ->send();
                        }),
                ])->dropdown(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('Assigner un coursier')
                        ->icon('heroicon-o-user-plus')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('courier_id')
                                ->label('Coursier')
                                ->options(fn () => User::where('role', 'courier')
                                    ->where('is_available', true)
                                    ->get()
                                    ->mapWithKeys(fn ($user) => [
                                        $user->id => $user->name ?? $user->phone ?? "Coursier #{$user->id}"
                                    ])
                                )
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === OrderStatus::PENDING && !$record->courier_id) {
                                    $record->update([
                                        'courier_id' => $data['courier_id'],
                                        'status' => OrderStatus::ASSIGNED,
                                        'assigned_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} commande(s) assignée(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!in_array($record->status, [OrderStatus::DELIVERED, OrderStatus::CANCELLED])) {
                                    $record->update([
                                        'status' => OrderStatus::CANCELLED,
                                        'cancelled_at' => now(),
                                        'cancellation_reason' => $data['reason'],
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} commande(s) annulée(s)")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('export')
                        ->label('Exporter CSV')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (Collection $records) {
                            $csv = "N° Commande;Client;Coursier;Statut;Prix;Date\n";
                            foreach ($records as $order) {
                                $csv .= implode(';', [
                                    $order->order_number,
                                    $order->client->name ?? 'N/A',
                                    $order->courier->name ?? 'Non assigné',
                                    $order->status->label(),
                                    number_format($order->total_price, 0, ',', ' '),
                                    $order->created_at->format('d/m/Y H:i'),
                                ]) . "\n";
                            }
                            
                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, 'commandes_' . now()->format('Y-m-d') . '.csv');
                        }),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->visible(fn (): bool => auth()->user()?->hasRole('super_admin')),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pending = static::getModel()::pending()->count();
        return $pending > 10 ? 'danger' : ($pending > 5 ? 'warning' : 'success');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'client.name', 'client.phone', 'courier.name'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Client' => $record->client?->name ?? 'N/A',
            'Statut' => $record->status->label(),
            'Prix' => number_format($record->total_price, 0, ',', ' ') . ' FCFA',
        ];
    }
}
