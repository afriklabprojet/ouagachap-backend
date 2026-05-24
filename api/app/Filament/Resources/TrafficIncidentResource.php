<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrafficIncidentResource\Pages;
use App\Models\TrafficIncident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrafficIncidentResource extends Resource
{
    protected static ?string $model = TrafficIncident::class;
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Incidents trafic';
    protected static ?string $modelLabel = 'Incident';
    protected static ?string $pluralModelLabel = 'Incidents trafic';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Opérations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('type')
                    ->label('Type')
                    ->disabled(),
                Forms\Components\TextInput::make('severity')
                    ->label('Sévérité')
                    ->disabled(),
                Forms\Components\TextInput::make('address')
                    ->label('Adresse')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('latitude')
                    ->label('Latitude')
                    ->disabled(),
                Forms\Components\TextInput::make('longitude')
                    ->label('Longitude')
                    ->disabled(),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('confirmations')
                    ->label('Confirmations')
                    ->disabled(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expiration')
                    ->disabled(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Sévérité')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high'   => 'danger',
                        'medium' => 'warning',
                        default  => 'gray',
                    }),
                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->limit(40)
                    ->searchable(),
                Tables\Columns\TextColumn::make('confirmations')
                    ->label('Confirmations')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Signalé par')
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrafficIncidents::route('/'),
            'view'  => Pages\ViewTrafficIncident::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
