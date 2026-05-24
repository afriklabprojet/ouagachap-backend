<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Retraits';

    protected static ?string $modelLabel = 'Retrait';

    protected static ?string $pluralModelLabel = 'Retraits';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?int $navigationSort = 2;

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
        return parent::getEloquentQuery()
            ->with(['user:id,name,phone']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Coursier')
                            ->relationship('user', 'name', fn (Builder $query) => $query->where('role', 'courier'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Coursier #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (FCFA)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'completed' => 'Complété',
                                'rejected' => 'Rejeté',
                            ])
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Méthode de paiement')
                            ->options([
                                'mobile_money' => 'Mobile Money',
                                'bank_transfer' => 'Virement bancaire',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Détails Mobile Money')
                    ->schema([
                        Forms\Components\TextInput::make('payment_phone')
                            ->label('Numéro de téléphone'),
                        Forms\Components\Select::make('payment_provider')
                            ->label('Opérateur')
                            ->options([
                                'orange_money' => 'Orange Money',
                                'moov_money' => 'Moov Money',
                            ]),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('payment_method') === 'mobile_money'),

                Forms\Components\Section::make('Détails Virement')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Nom de la banque'),
                        Forms\Components\TextInput::make('bank_account')
                            ->label('Numéro de compte'),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('payment_method') === 'bank_transfer'),

                Forms\Components\Section::make('Traitement')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Référence transaction'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Coursier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.phone')
                    ->label('Téléphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'completed',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'completed' => 'Complété',
                        'rejected' => 'Rejeté',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Méthode')
                    ->formatStateUsing(fn ($state) => $state === 'mobile_money' ? 'Mobile Money' : 'Virement'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'completed' => 'Complété',
                        'rejected' => 'Rejeté',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Méthode')
                    ->options([
                        'mobile_money' => 'Mobile Money',
                        'bank_transfer' => 'Virement',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Withdrawal $record) {
                        $record->approve(auth()->id());
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif de rejet')
                            ->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data) {
                        $record->reject($data['reason'], auth()->id());
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Marquer complété')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (Withdrawal $record) => $record->status === 'approved')
                    ->form([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Référence transaction')
                            ->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data) {
                        $record->complete($data['transaction_reference']);
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('✅ Approuver sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approuver les retraits sélectionnés')
                        ->modalDescription('Êtes-vous sûr de vouloir approuver tous les retraits sélectionnés ?')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->approve(auth()->id());
                                    $count++;
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("{$count} retrait(s) approuvé(s)")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('❌ Rejeter sélection')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Rejeter les retraits sélectionnés')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif de rejet (appliqué à tous)')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->reject($data['reason'], auth()->id());
                                    $count++;
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title("{$count} retrait(s) rejeté(s)")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_complete')
                        ->label('🏁 Marquer complétés')
                        ->icon('heroicon-o-flag')
                        ->color('info')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('transaction_prefix')
                                ->label('Préfixe référence (ex: TXN)')
                                ->default('TXN')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'approved') {
                                    $ref = $data['transaction_prefix'] . '-' . now()->format('YmdHis') . '-' . $record->id;
                                    $record->complete($ref);
                                    $count++;
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title("{$count} retrait(s) complété(s)")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('super_admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'view' => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}
