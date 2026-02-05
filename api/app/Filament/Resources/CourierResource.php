<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\CourierResource\Pages;
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

class CourierResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Coursiers';
    protected static ?string $modelLabel = 'Coursier';
    protected static ?string $pluralModelLabel = 'Coursiers';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Utilisateurs';

    // ==================== EAGER LOADING (Performance) ====================
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::COURIER)
            ->withCount(['courierOrders as active_orders_count' => function ($query) {
                $query->whereIn('status', [OrderStatus::ASSIGNED->value, OrderStatus::PICKED_UP->value]);
            }]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->required()
                            ->tel()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(collect(UserStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Véhicule')
                    ->schema([
                        Forms\Components\Select::make('vehicle_type')
                            ->label('Type')
                            ->options([
                                'moto' => 'Moto',
                                'velo' => 'Vélo',
                                'voiture' => 'Voiture',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('vehicle_plate')
                            ->label('Plaque')
                            ->required(),
                        Forms\Components\TextInput::make('vehicle_model')
                            ->label('Modèle'),
                    ])->columns(3),

                Forms\Components\Section::make('Disponibilité')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Disponible pour livraisons'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicle_type')
                    ->label('Véhicule')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('En ligne')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(UserStatus $state): string => $state->color())
                    ->formatStateUsing(fn(UserStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Note')
                    ->formatStateUsing(fn($state) => $state ? number_format($state, 1) . '/5' : '-'),
                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label('Solde')
                    ->money('XOF')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Disponibilité'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => $record->status === UserStatus::PENDING)
                    ->action(fn(User $record) => $record->update(['status' => UserStatus::ACTIVE])),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->status === UserStatus::ACTIVE)
                    ->requiresConfirmation()
                    ->action(fn(User $record) => $record->update(['status' => UserStatus::SUSPENDED, 'is_available' => false])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListCouriers::route('/'),
            'create' => Pages\CreateCourier::route('/create'),
            'view' => Pages\ViewCourier::route('/{record}'),
            'edit' => Pages\EditCourier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()->where('status', UserStatus::PENDING)->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
