<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionPlan;
use App\Filament\Resources\SubscriptionPlanConfigResource\Pages;
use App\Models\SubscriptionPlanConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionPlanConfigResource extends Resource
{
    protected static ?string $model = SubscriptionPlanConfig::class;
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'CHAP Pass – Tarifs';
    protected static ?string $modelLabel      = 'Configuration du plan';
    protected static ?string $pluralModelLabel = 'Configuration des plans';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $navigationGroup = 'Abonnements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identification du plan')
                    ->description('Ces champs identifient le plan — ils ne peuvent pas être modifiés.')
                    ->schema([
                        Forms\Components\TextInput::make('plan')
                            ->label('Plan')
                            ->disabled()
                            ->formatStateUsing(fn (string $state): string => strtoupper($state))
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('label')
                            ->label('Nom affiché')
                            ->required()
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('Tarification')
                    ->description('Modifiez les montants ici. Le changement sera appliqué aux nouveaux abonnements uniquement.')
                    ->schema([
                        Forms\Components\TextInput::make('price_xof')
                            ->label('Prix mensuel (FCFA)')
                            ->required()
                            ->numeric()
                            ->minValue(500)
                            ->maxValue(100000)
                            ->step(100)
                            ->suffix('FCFA')
                            ->helperText('Tarif facturé au client à chaque souscription mensuelle.'),

                        Forms\Components\TextInput::make('discount_xof')
                            ->label('Remise par livraison (FCFA)')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->step(50)
                            ->suffix('FCFA')
                            ->helperText('Montant déduit du prix de chaque livraison pour les abonnés.'),
                    ])->columns(2),

                Forms\Components\Section::make('Options & Statut')
                    ->schema([
                        Forms\Components\Toggle::make('priority_dispatch')
                            ->label('Dispatch prioritaire')
                            ->helperText('Les commandes de cet abonnement sont prioritaires dans la file de dispatch.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Plan actif')
                            ->helperText('Si désactivé, ce plan ne sera plus proposé à la souscription.')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description (app mobile)')
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('Texte affiché sous le plan dans l\'application mobile.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => strtoupper($state instanceof \BackedEnum ? $state->value : (string) $state))
                    ->color(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : (string) $state) {
                        'premium' => 'warning',
                        default   => 'primary',
                    }),

                Tables\Columns\TextColumn::make('label')
                    ->label('Nom')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('price_xof')
                    ->label('Prix mensuel')
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_xof')
                    ->label('Remise/livraison')
                    ->formatStateUsing(fn (int $state): string => number_format($state, 0, ',', ' ') . ' FCFA')
                    ->sortable(),

                Tables\Columns\IconColumn::make('priority_dispatch')
                    ->label('Prioritaire')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('plan')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        Notification::make()
                            ->title('Tarifs mis à jour')
                            ->body('Le cache a été vidé. Les nouvelles souscriptions utiliseront ces montants.')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated(false); // Seulement 2 lignes : pas besoin de pagination
    }

    /**
     * Pas de création ni suppression : les plans sont gérés via migration.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlanConfigs::route('/'),
            'edit'  => Pages\EditSubscriptionPlanConfig::route('/{record}/edit'),
        ];
    }
}
