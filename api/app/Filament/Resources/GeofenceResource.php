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
    
    protected static ?string $navigationLabel = 'Zones Geofencing';
    
    protected static ?string $modelLabel = 'Zone';
    
    protected static ?string $pluralModelLabel = 'Zones Geofencing';
    
    protected static ?string $navigationGroup = 'Géographie';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la zone')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la zone')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Centre-ville Ouagadougou'),
                            
                        Forms\Components\Select::make('type')
                            ->label('Type de zone')
                            ->options([
                                'allowed' => '✅ Zone autorisée',
                                'restricted' => '🚫 Zone restreinte',
                                'surge' => '📈 Zone surge (prix majoré)',
                            ])
                            ->default('allowed')
                            ->required()
                            ->live(),
                            
                        Forms\Components\TextInput::make('surge_multiplier')
                            ->label('Multiplicateur de prix')
                            ->numeric()
                            ->minValue(1.00)
                            ->maxValue(5.00)
                            ->step(0.1)
                            ->default(1.00)
                            ->suffix('x')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'surge')
                            ->helperText('Ex: 1.5 = prix majoré de 50%'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Zone active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Coordonnées du polygone')
                    ->schema([
                        Forms\Components\Repeater::make('coordinates')
                            ->label('Points du polygone')
                            ->schema([
                                Forms\Components\TextInput::make('lat')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->required()
                                    ->step(0.000001)
                                    ->placeholder('12.3456'),
                                    
                                Forms\Components\TextInput::make('lng')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->required()
                                    ->step(0.000001)
                                    ->placeholder('-1.5234'),
                            ])
                            ->columns(2)
                            ->minItems(3)
                            ->defaultItems(4)
                            ->addActionLabel('Ajouter un point')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->helperText('Minimum 3 points pour former un polygone. Les coordonnées sont dans l\'ordre : Latitude, Longitude'),
                    ]),

                Forms\Components\Section::make('Coordonnées prédéfinies - Ouagadougou')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->content('🗺️ Coordonnées de référence pour Ouagadougou :
                            
• Centre-ville : 12.3714, -1.5197
• Aéroport : 12.3532, -1.5124
• Université : 12.3857, -1.4994
• Stade du 4 Août : 12.3656, -1.5389'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'allowed' => '✅ Autorisée',
                        'restricted' => '🚫 Restreinte',
                        'surge' => '📈 Surge',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'allowed' => 'success',
                        'restricted' => 'danger',
                        'surge' => 'warning',
                    }),
                    
                Tables\Columns\TextColumn::make('surge_multiplier')
                    ->label('Multiplicateur')
                    ->formatStateUsing(fn ($state) => $state . 'x')
                    ->visible(fn () => Geofence::where('type', 'surge')->exists())
                    ->badge()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Points')
                    ->formatStateUsing(fn ($state) => count($state ?? []) . ' points')
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('logs_count')
                    ->label('Événements')
                    ->counts('logs')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'allowed' => 'Zone autorisée',
                        'restricted' => 'Zone restreinte',
                        'surge' => 'Zone surge',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_map')
                    ->label('Voir sur carte')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn (Geofence $record): string => route('filament.admin.pages.couriers-tracking')),
                    
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Geofence $record): string => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (Geofence $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (Geofence $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(fn (Geofence $record) => $record->update(['is_active' => !$record->is_active])),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeofences::route('/'),
            'create' => Pages\CreateGeofence::route('/create'),
            'edit' => Pages\EditGeofence::route('/{record}/edit'),
        ];
    }
}
