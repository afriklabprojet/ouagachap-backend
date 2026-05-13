<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Portefeuilles';

    protected static ?string $modelLabel = 'Portefeuille';

    protected static ?string $pluralModelLabel = 'Portefeuilles';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user:id,name,phone,role']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Portefeuille')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->relationship('user', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => ($record->name ?? $record->phone) . ($record->role ? ' (' . (is_object($record->role) ? $record->role->value : $record->role) . ')' : ''))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabledOn('edit'),
                    Forms\Components\TextInput::make('balance')
                        ->label('Solde (FCFA)')
                        ->numeric()
                        ->required()
                        ->default(0),
                    Forms\Components\TextInput::make('pending_balance')
                        ->label('Solde en attente (FCFA)')
                        ->numeric()
                        ->required()
                        ->default(0),
                    Forms\Components\TextInput::make('total_earned')
                        ->label('Total gagné (FCFA)')
                        ->numeric()
                        ->disabled(),
                    Forms\Components\TextInput::make('total_withdrawn')
                        ->label('Total retiré (FCFA)')
                        ->numeric()
                        ->disabled(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Wallet $record) => $record->user?->phone),
                Tables\Columns\TextColumn::make('user.role')
                    ->label('Rôle')
                    ->badge()
                    ->formatStateUsing(fn ($state) => is_object($state) ? $state->value : (string) $state),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Solde')
                    ->money('XOF')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('En attente')
                    ->money('XOF')
                    ->sortable()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total gagné')
                    ->money('XOF')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_withdrawn')
                    ->label('Total retiré')
                    ->money('XOF')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Maj')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rôle')
                    ->options([
                        'client' => 'Client',
                        'courier' => 'Coursier',
                        'admin' => 'Admin',
                    ])
                    ->query(fn (Builder $query, array $data) => isset($data['value']) && $data['value'] !== ''
                        ? $query->whereHas('user', fn ($q) => $q->where('role', $data['value']))
                        : $query
                    ),
                Tables\Filters\Filter::make('positive_balance')
                    ->label('Solde > 0')
                    ->query(fn (Builder $q) => $q->where('balance', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('balance', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WalletResource\RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'view' => Pages\ViewWallet::route('/{record}'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
