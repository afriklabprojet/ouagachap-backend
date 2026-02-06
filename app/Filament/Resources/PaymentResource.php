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
use Illuminate\Database\Eloquent\Builder;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Paiements';

    protected static ?string $modelLabel = 'Paiement';

    protected static ?string $pluralModelLabel = 'Paiements';

    protected static ?int $navigationSort = 3;

    // ==================== EAGER LOADING (Performance) ====================
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user:id,name,phone', 'order:id,order_number,total_price']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID Transaction')
                            ->disabled(),
                        Forms\Components\TextInput::make('order.order_number')
                            ->label('N° Commande')
                            ->disabled(),
                        Forms\Components\TextInput::make('user.name')
                            ->label('Client')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Détails du paiement')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('method')
                            ->label('Méthode')
                            ->formatStateUsing(fn($state) => PaymentMethod::tryFrom($state)?->label() ?? $state)
                            ->disabled(),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Téléphone')
                            ->disabled(),
                        Forms\Components\TextInput::make('provider_transaction_id')
                            ->label('ID Fournisseur')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Créé le')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payé le')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('failed_at')
                            ->label('Échoué le')
                            ->disabled(),
                        Forms\Components\TextInput::make('failure_reason')
                            ->label('Raison d\'échec')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Réponse fournisseur')
                    ->schema([
                        Forms\Components\Textarea::make('provider_response')
                            ->label('Réponse JSON')
                            ->disabled()
                            ->rows(5),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('ID Transaction')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Commande')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Méthode')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof PaymentMethod ? $state->label() : (PaymentMethod::tryFrom($state)?->label() ?? $state)),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn($state): string => $state instanceof PaymentStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn($state): string => $state instanceof PaymentStatus ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Téléphone')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
                Tables\Filters\SelectFilter::make('method')
                    ->label('Méthode')
                    ->options(collect(PaymentMethod::cases())->mapWithKeys(fn($m) => [$m->value => $m->label()])),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Du'),
                        Forms\Components\DatePicker::make('until')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
