<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Transactions Wallet';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions Wallet';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Finances';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,phone');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transaction')
                ->schema([
                    Forms\Components\TextInput::make('transaction_id')
                        ->label('ID Transaction')
                        ->disabled(),
                    Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('amount')
                        ->label('Montant (FCFA)')
                        ->numeric()
                        ->required()
                        ->suffix('FCFA'),
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(['recharge' => 'Recharge', 'debit' => 'Débit'])
                        ->required(),
                    Forms\Components\Select::make('method')
                        ->label('Méthode')
                        ->options([
                            'orange_money' => 'Orange Money',
                            'moov_money' => 'Moov Money',
                            'wave' => 'Wave',
                            'mtn_money' => 'MTN Money',
                            'djamo' => 'Djamo',
                            'cash' => 'Espèces',
                        ]),
                    Forms\Components\TextInput::make('phone_number')
                        ->label('Numéro de téléphone'),
                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options(['pending' => 'En attente', 'success' => 'Réussi', 'failed' => 'Échoué'])
                        ->required(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID Transaction')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->description(fn (WalletTransaction $record) => $record->user?->phone),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'recharge' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'recharge' ? 'Recharge' : 'Débit'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'orange_money' => 'Orange Money',
                        'moov_money' => 'Moov Money',
                        'wave' => 'Wave',
                        'mtn_money' => 'MTN Money',
                        'djamo' => 'Djamo',
                        'cash' => 'Espèces',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Téléphone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Réussi',
                        'failed' => 'Échoué',
                        default => 'En attente',
                    }),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complété le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(['recharge' => 'Recharge', 'debit' => 'Débit']),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(['pending' => 'En attente', 'success' => 'Réussi', 'failed' => 'Échoué']),
                Tables\Filters\SelectFilter::make('method')
                    ->label('Méthode')
                    ->options([
                        'orange_money' => 'Orange Money',
                        'moov_money' => 'Moov Money',
                        'wave' => 'Wave',
                        'mtn_money' => 'MTN Money',
                        'djamo' => 'Djamo',
                        'cash' => 'Espèces',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label("Aujourd'hui")
                    ->query(fn (Builder $q) => $q->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Détails')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction_id')->label('ID Transaction')->copyable()->fontFamily('mono'),
                        Infolists\Components\TextEntry::make('user.name')->label('Utilisateur'),
                        Infolists\Components\TextEntry::make('user.phone')->label('Téléphone'),
                        Infolists\Components\TextEntry::make('type')->label('Type')->badge(),
                        Infolists\Components\TextEntry::make('amount')->label('Montant')->money('XOF'),
                        Infolists\Components\TextEntry::make('method')->label('Méthode'),
                        Infolists\Components\TextEntry::make('phone_number')->label('N° Mobile'),
                        Infolists\Components\TextEntry::make('status')->label('Statut')->badge(),
                        Infolists\Components\TextEntry::make('provider_transaction_id')->label('ID Fournisseur')->copyable(),
                        Infolists\Components\TextEntry::make('failure_reason')->label('Raison échec')->placeholder('—'),
                        Infolists\Components\TextEntry::make('completed_at')->label('Complété le')->dateTime('d/m/Y H:i')->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i'),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
