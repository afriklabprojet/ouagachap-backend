<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Paiements';
    protected static ?string $modelLabel = 'Paiement';
    protected static ?string $pluralModelLabel = 'Paiements';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Finances';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails paiement')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID Transaction')
                            ->disabled(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Select::make('method')
                            ->label('Méthode')
                            ->options(collect(PaymentMethod::cases())->mapWithKeys(fn($m) => [$m->value => $m->label()]))
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                            ->disabled(),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Téléphone')
                            ->disabled(),
                        Forms\Components\TextInput::make('provider_transaction_id')
                            ->label('ID Fournisseur')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(fn(PaymentMethod $state): string => $state->label()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn(PaymentStatus $state): string => $state->color())
                    ->formatStateUsing(fn(PaymentStatus $state): string => $state->label()),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Téléphone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('method')
                    ->label('Méthode')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn($m) => [$m->value => $m->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view'  => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
