<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeofenceResource\Pages;
use App\Models\Geofence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GeofenceResource extends Resource
{
    protected static ?string $model = Geofence::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Géofences';
    protected static ?string $modelLabel = 'Zone géofence';
    protected static ?string $pluralModelLabel = 'Géofences';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Opérations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'allowed'    => 'Autorisée',
                        'restricted' => 'Restreinte',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('surge_multiplier')
                    ->label('Multiplicateur de surcharge')
                    ->numeric()
                    ->step(0.1)
                    ->default(1.0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Textarea::make('coordinates')
                    ->label('Coordonnées (JSON)')
                    ->helperText('Format : [[lat, lng], [lat, lng], ...]')
                    ->rows(6)
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'restricted' => 'danger',
                        default      => 'success',
                    }),
                Tables\Columns\TextColumn::make('surge_multiplier')
                    ->label('Multiplicateur')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'allowed'    => 'Autorisée',
                        'restricted' => 'Restreinte',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGeofences::route('/'),
            'create' => Pages\CreateGeofence::route('/create'),
            'edit'   => Pages\EditGeofence::route('/{record}/edit'),
        ];
    }
}
