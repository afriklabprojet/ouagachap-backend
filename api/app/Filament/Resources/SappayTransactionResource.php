<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SappayTransactionResource\Pages;
use App\Models\SappayTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SappayTransactionResource extends Resource
{
    private const METHOD_LABEL = 'Méthode';

    protected static ?string $model = SappayTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Transactions Sappay';

    protected static ?string $modelLabel = 'Transaction Sappay';

    protected static ?string $pluralModelLabel = 'Transactions Sappay';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user:id,name,phone']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transaction')
                ->schema([
                    Forms\Components\TextInput::make('reference')->disabled(),
                    Forms\Components\TextInput::make('invoice_id')->label('Invoice ID Sappay')->disabled(),
                    Forms\Components\TextInput::make('type')->disabled(),
                    Forms\Components\TextInput::make('payment_method')->label(self::METHOD_LABEL)->disabled(),
                    Forms\Components\TextInput::make('customer_msisdn')->label('MSISDN client')->disabled(),
                    Forms\Components\TextInput::make('amount')->label('Montant')->disabled(),
                    Forms\Components\TextInput::make('status')->disabled(),
                    Forms\Components\Toggle::make('requires_otp')->label('OTP requis')->disabled(),
                    Forms\Components\DateTimePicker::make('executed_at')->label('Exécutée le')->disabled(),
                ])->columns(2),
            Forms\Components\Section::make('Données techniques')
                ->collapsed()
                ->schema([
                    Forms\Components\KeyValue::make('metadata')->disabled(),
                    Forms\Components\Textarea::make('webhook_payload')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                        ->rows(8),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->description(fn (SappayTransaction $r) => $r->user?->phone),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'wallet_recharge',
                        'success' => 'order_payment',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(self::METHOD_LABEL)
                    ->badge()
                    ->formatStateUsing(fn ($state) => config("sappay.payment_methods.{$state}.name", $state)),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'error',
                        'gray' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('customer_msisdn')
                    ->label('MSISDN')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('executed_at')
                    ->label('Exécutée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'En attente',
                        'success' => 'Succès',
                        'error' => 'Erreur',
                        'cancelled' => 'Annulée',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'wallet_recharge' => 'Recharge wallet',
                        'order_payment' => 'Paiement commande',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label(self::METHOD_LABEL)
                    ->options([
                        'orange_money' => 'Orange Money',
                        'telecel_money' => 'Telecel Money',
                        'moov_money' => 'Moov Money',
                        'coris_money' => 'Coris Money',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSappayTransactions::route('/'),
            'view' => Pages\ViewSappayTransaction::route('/{record}'),
        ];
    }
}
