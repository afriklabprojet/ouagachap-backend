<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    
    protected static ?string $navigationGroup = 'Marketing';
    
    protected static ?string $navigationLabel = 'Codes Promo';
    
    protected static ?string $modelLabel = 'Code Promo';
    
    protected static ?string $pluralModelLabel = 'Codes Promo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('PROMO2024')
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Réduction')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de réduction')
                            ->options([
                                'percentage' => 'Pourcentage (%)',
                                'fixed' => 'Montant fixe (FCFA)',
                                'free_delivery' => 'Livraison gratuite',
                            ])
                            ->required()
                            ->default('percentage'),
                        Forms\Components\TextInput::make('value')
                            ->label('Valeur')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('10'),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Réduction max (FCFA)')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Pour les pourcentages uniquement'),
                        Forms\Components\TextInput::make('min_order_amount')
                            ->label('Montant minimum de commande')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('FCFA'),
                    ])->columns(2),

                Forms\Components\Section::make('Validité')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Valide à partir de'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Valide jusqu\'au'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Nombre max d\'utilisations')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Laisser vide pour illimité'),
                        Forms\Components\TextInput::make('max_uses_per_user')
                            ->label('Max par utilisateur')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])->columns(2),

                Forms\Components\Section::make('Restrictions')
                    ->schema([
                        Forms\Components\Toggle::make('first_order_only')
                            ->label('Première commande uniquement')
                            ->default(false),
                    ])->columns(2),
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
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30),
                Tables\Columns\TextColumn::make('discount_display')
                    ->label('Réduction')
                    ->getStateUsing(fn ($record) => match($record->type) {
                        'percentage' => "{$record->value}%",
                        'fixed' => number_format($record->value) . ' FCFA',
                        'free_delivery' => 'Livraison gratuite',
                        default => $record->value,
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('current_uses')
                    ->label('Utilisations')
                    ->suffix(fn ($record) => $record->max_uses ? "/{$record->max_uses}" : ''),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expire le')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\Filter::make('valid')
                    ->label('Valides uniquement')
                    ->query(fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ),
                Tables\Filters\Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn (Builder $query) => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label(fn ($record) => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
