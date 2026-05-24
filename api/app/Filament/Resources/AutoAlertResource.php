<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoAlertResource\Pages;
use App\Models\AutoAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AutoAlertResource extends Resource
{
    protected static ?string $model = AutoAlert::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Alertes auto';
    protected static ?string $modelLabel = 'Alerte automatique';
    protected static ?string $pluralModelLabel = 'Alertes automatiques';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Opérations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('trigger_type')
                    ->label('Type de déclencheur')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cooldown_minutes')
                    ->label('Délai de refroidissement (min)')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Textarea::make('conditions')
                    ->label('Conditions (JSON)')
                    ->helperText('Format JSON : {"key": "value"}')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('actions')
                    ->label('Actions (JSON)')
                    ->helperText('Format JSON : {"key": "value"}')
                    ->rows(4)
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
                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Déclencheur')
                    ->badge(),
                Tables\Columns\TextColumn::make('cooldown_minutes')
                    ->label('Délai (min)')
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
            'index'  => Pages\ListAutoAlerts::route('/'),
            'create' => Pages\CreateAutoAlert::route('/create'),
            'edit'   => Pages\EditAutoAlert::route('/{record}/edit'),
        ];
    }
}
