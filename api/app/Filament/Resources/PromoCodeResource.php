<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Codes Promo';
    protected static ?string $modelLabel = 'Code promo';
    protected static ?string $pluralModelLabel = 'Codes promo';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Finances';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'percentage'     => 'Pourcentage (%)',
                                'fixed'          => 'Montant fixe (FCFA)',
                                'free_delivery'  => 'Livraison gratuite',
                            ]),
                        Forms\Components\TextInput::make('value')
                            ->label('Valeur')
                            ->numeric()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\TextInput::make('min_order_amount')
                            ->label('Commande minimum (FCFA)')
                            ->numeric()
                            ->nullable(),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Remise maximum (FCFA)')
                            ->numeric()
                            ->nullable(),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Utilisations max')
                            ->numeric()
                            ->nullable(),
                        Forms\Components\TextInput::make('current_uses')
                            ->label('Utilisations actuelles')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Validité')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Début'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiration'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'percentage'    => '% Pourcentage',
                        'fixed'         => 'Montant fixe',
                        'free_delivery' => 'Livraison gratuite',
                        default         => $state,
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_uses')
                    ->label('Utilisations')
                    ->suffix(fn($record) => $record->max_uses ? '/' . $record->max_uses : '')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\PromoCodeResource\RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit'   => Pages\EditPromoCode::route('/{record}/edit'),
            'view'   => Pages\ViewPromoCode::route('/{record}'),
        ];
    }
}
