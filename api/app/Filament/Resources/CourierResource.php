<?php

namespace App\Filament\Resources;

use App\Enums\KycStatus;
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
                $query->whereIn('status', OrderStatus::activeStatuses());
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

                Forms\Components\Section::make('KYC — Vérification d\'identité')
                    ->schema([
                        Forms\Components\Select::make('kyc_status')
                            ->label('Statut KYC')
                            ->options(collect(KycStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('documents_submitted_at')
                            ->label('Documents soumis le')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('documents_verified_at')
                            ->label('Vérifié le')
                            ->disabled(),
                        Forms\Components\Textarea::make('kyc_rejection_reason')
                            ->label('Raison du rejet')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('identity_document_url')
                            ->label('Document d\'identité (URL)')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('selfie_url')
                            ->label('Selfie (URL)')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2)->collapsible(),
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
                Tables\Columns\TextColumn::make('kyc_status')
                    ->label('KYC')
                    ->badge()
                    ->color(fn(KycStatus $state): string => $state->color())
                    ->formatStateUsing(fn(KycStatus $state): string => $state->label()),

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
                Tables\Filters\SelectFilter::make('kyc_status')
                    ->label('Statut KYC')
                    ->options(collect(KycStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => $record->status === UserStatus::PENDING)
                    ->action(fn(User $record) => $record->update(['status' => UserStatus::ACTIVE])),
                Tables\Actions\Action::make('kyc_approve')
                    ->label('Valider KYC')
                    ->icon('heroicon-o-identification')
                    ->color('success')
                    ->visible(fn(User $record) => $record->kyc_status === KycStatus::PENDING)
                    ->requiresConfirmation()
                    ->action(fn(User $record) => $record->update([
                        'kyc_status' => KycStatus::APPROVED,
                        'documents_verified_at' => now(),
                        'kyc_rejection_reason' => null,
                    ])),
                Tables\Actions\Action::make('kyc_reject')
                    ->label('Rejeter KYC')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->kyc_status === KycStatus::PENDING)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Raison du rejet')
                            ->required()
                            ->minLength(10),
                    ])
                    ->action(fn(User $record, array $data) => $record->update([
                        'kyc_status' => KycStatus::REJECTED,
                        'kyc_rejection_reason' => $data['reason'],
                    ])),
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
        return [
            \App\Filament\Resources\CourierResource\RelationManagers\QuestsRelationManager::class,
        ];
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
