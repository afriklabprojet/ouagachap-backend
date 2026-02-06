<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrafficIncidentResource\Pages;
use App\Models\TrafficIncident;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TrafficIncidentResource extends Resource
{
    protected static ?string $model = TrafficIncident::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Incidents Trafic';

    protected static ?string $modelLabel = 'Incident de trafic';

    protected static ?string $pluralModelLabel = 'Incidents de trafic';

    protected static ?string $navigationGroup = 'Trafic & Zones';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::active()->count();
        
        if ($count >= 10) return 'danger';
        if ($count >= 5) return 'warning';
        
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'incident')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type d\'incident')
                            ->options(TrafficIncident::getTypes())
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('severity')
                            ->label('Sévérité')
                            ->options(TrafficIncident::getSeverities())
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->required()
                            ->numeric()
                            ->step(0.00000001),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->required()
                            ->numeric()
                            ->step(0.00000001),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresse')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statut')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        Forms\Components\TextInput::make('confirmations')
                            ->label('Confirmations')
                            ->numeric()
                            ->default(1)
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expire le')
                            ->native(false),

                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Résolu le')
                            ->native(false)
                            ->disabled(),

                        Forms\Components\Select::make('reporter_id')
                            ->label('Signalé par')
                            ->relationship('reporter', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Utilisateur #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TrafficIncident::getTypes()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'congestion' => 'warning',
                        'accident' => 'danger',
                        'road_work' => 'info',
                        'road_closed' => 'danger',
                        'police' => 'primary',
                        'hazard' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('severity')
                    ->label('Sévérité')
                    ->badge()
                    ->formatStateUsing(fn ($state) => TrafficIncident::getSeverities()[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'low' => 'success',
                        'moderate' => 'warning',
                        'high' => 'danger',
                        'severe' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('confirmations')
                    ->label('Confirmations')
                    ->badge()
                    ->color(fn ($state) => $state >= 5 ? 'success' : ($state >= 3 ? 'warning' : 'gray')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Signalé par')
                    ->searchable()
                    ->placeholder('Anonyme'),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expire')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(TrafficIncident::getTypes()),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Sévérité')
                    ->options(TrafficIncident::getSeverities()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs uniquement')
                    ->falseLabel('Résolus uniquement'),

                Tables\Filters\Filter::make('expired')
                    ->label('Expirés')
                    ->query(fn (Builder $query) => $query->where('expires_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->is_active)
                    ->action(function ($record) {
                        $record->resolve(auth()->id());
                    }),

                Tables\Actions\Action::make('viewOnMap')
                    ->label('Voir sur carte')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn ($record) => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resolveSelected')
                        ->label('Résoudre la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->resolve(auth()->id());
                            }
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTrafficIncidents::route('/'),
            'create' => Pages\CreateTrafficIncident::route('/create'),
            'edit' => Pages\EditTrafficIncident::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes();
    }
}
