<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionPlan;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Abonnements';

    protected static ?string $modelLabel = 'Abonnement';

    protected static ?string $pluralModelLabel = 'Abonnements';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Finances';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,phone,role');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Abonnement')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Client')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn (User $record) => ($record->name ?? $record->phone))
                            ->searchable(['name', 'phone'])
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('plan')
                            ->label('Plan')
                            ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $plan = SubscriptionPlan::from($state);
                                    $set('price_xof', $plan->priceXof());
                                    $set('discount_xof', $plan->discountXof());
                                    $set('priority_dispatch', $plan->hasPriorityDispatch());
                                }
                            }),
                        Forms\Components\TextInput::make('price_xof')
                            ->label('Prix (FCFA)')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA'),
                        Forms\Components\TextInput::make('discount_xof')
                            ->label('Remise par livraison (FCFA)')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA'),
                        Forms\Components\Toggle::make('priority_dispatch')
                            ->label('Dispatch prioritaire')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Dates')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Début')
                            ->required()
                            ->default(now()),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Expiration')
                            ->required()
                            ->default(now()->addMonth()),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Annulé le')
                            ->nullable(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Subscription $record) => $record->user?->phone),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (SubscriptionPlan $state): string => match ($state) {
                        SubscriptionPlan::PREMIUM => 'warning',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (SubscriptionPlan $state): string => $state->label()),
                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_xof')
                    ->label('Remise/livraison')
                    ->money('XOF'),
                Tables\Columns\IconColumn::make('priority_dispatch')
                    ->label('Prioritaire')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Subscription $record) => $record->ends_at->isPast() ? 'danger' : 'success'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->getStateUsing(fn (Subscription $record) => $record->isActive()),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('Annulé le')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->label('Plan')
                    ->options(collect(SubscriptionPlan::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])),
                Tables\Filters\Filter::make('active')
                    ->label('Actifs seulement')
                    ->query(fn (Builder $q) => $q->active()),
                Tables\Filters\Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn (Builder $q) => $q->where('ends_at', '<', now())->whereNull('cancelled_at')),
                Tables\Filters\Filter::make('cancelled')
                    ->label('Annulés')
                    ->query(fn (Builder $q) => $q->whereNotNull('cancelled_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Subscription $record) => $record->isActive())
                    ->action(fn (Subscription $record) => $record->update(['cancelled_at' => now()])),
                Tables\Actions\Action::make('renew')
                    ->label('Renouveler (+30j)')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->update([
                        'ends_at' => ($record->ends_at->isFuture() ? $record->ends_at : now())->addMonth(),
                        'cancelled_at' => null,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Abonnement')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Client'),
                        Infolists\Components\TextEntry::make('user.phone')
                            ->label('Téléphone'),
                        Infolists\Components\TextEntry::make('plan')
                            ->label('Plan')
                            ->badge()
                            ->formatStateUsing(fn (SubscriptionPlan $state) => $state->label()),
                        Infolists\Components\TextEntry::make('price_xof')
                            ->label('Prix')
                            ->money('XOF'),
                        Infolists\Components\TextEntry::make('discount_xof')
                            ->label('Remise/livraison')
                            ->money('XOF'),
                        Infolists\Components\IconEntry::make('priority_dispatch')
                            ->label('Dispatch prioritaire')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('starts_at')
                            ->label('Début')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('ends_at')
                            ->label('Expiration')
                            ->dateTime('d/m/Y H:i'),
                        Infolists\Components\TextEntry::make('cancelled_at')
                            ->label('Annulé le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non annulé'),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
