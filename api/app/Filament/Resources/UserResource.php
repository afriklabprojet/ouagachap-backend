<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clients';
    protected static ?string $modelLabel = 'Client';
    protected static ?string $pluralModelLabel = 'Clients';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Utilisateurs';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::CLIENT);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->maxLength(255),
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

                Forms\Components\Section::make('Solde & Stats')
                    ->schema([
                        Forms\Components\TextInput::make('wallet_balance')
                            ->label('Solde portefeuille (FCFA)')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('total_orders')
                            ->label('Total commandes')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('average_rating')
                            ->label('Note moyenne')
                            ->numeric()
                            ->disabled(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sans nom'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(UserStatus $state): string => $state->color())
                    ->formatStateUsing(fn(UserStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Commandes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet_balance')
                    ->label('Solde')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Tables\Actions\Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->status === UserStatus::ACTIVE)
                    ->requiresConfirmation()
                    ->action(fn(User $record) => $record->update(['status' => UserStatus::SUSPENDED])),
                Tables\Actions\Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => $record->status !== UserStatus::ACTIVE)
                    ->action(fn(User $record) => $record->update(['status' => UserStatus::ACTIVE])),
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
            \App\Filament\Resources\UserResource\RelationManagers\SubscriptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }
}
