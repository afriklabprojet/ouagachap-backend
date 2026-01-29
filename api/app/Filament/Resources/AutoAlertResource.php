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
    
    protected static ?string $navigationLabel = 'Alertes Automatiques';
    
    protected static ?string $modelLabel = 'Alerte Auto';
    
    protected static ?string $pluralModelLabel = 'Alertes Automatiques';
    
    protected static ?string $navigationGroup = 'Système';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuration de l\'alerte')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de l\'alerte')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('trigger_type')
                            ->label('Type de déclencheur')
                            ->options([
                                'order_delayed' => '⏰ Commande en retard',
                                'courier_offline' => '📴 Coursier hors ligne',
                                'low_couriers' => '👥 Peu de coursiers disponibles',
                                'high_pending_orders' => '📦 Beaucoup de commandes en attente',
                                'withdrawal_pending' => '💸 Retraits en attente',
                                'negative_rating' => '⭐ Avis négatif',
                            ])
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\KeyValue::make('conditions')
                            ->label('Conditions de déclenchement')
                            ->keyLabel('Paramètre')
                            ->valueLabel('Valeur')
                            ->addActionLabel('Ajouter une condition')
                            ->helperText(function (Forms\Get $get) {
                                return match ($get('trigger_type')) {
                                    'order_delayed' => 'Ex: delay_minutes = 30 (alerte après 30 min de retard)',
                                    'courier_offline' => 'Ex: offline_minutes = 60 (hors ligne depuis 60 min)',
                                    'low_couriers' => 'Ex: min_available = 3 (alerte si moins de 3 coursiers)',
                                    'high_pending_orders' => 'Ex: max_pending = 10 (alerte si plus de 10 en attente)',
                                    'withdrawal_pending' => 'Ex: pending_hours = 24 (en attente depuis 24h)',
                                    'negative_rating' => 'Ex: max_rating = 2 (alerte si note <= 2)',
                                    default => 'Définissez les conditions de déclenchement',
                                };
                            }),
                    ]),

                Forms\Components\Section::make('Actions')
                    ->schema([
                        Forms\Components\KeyValue::make('actions')
                            ->label('Actions à exécuter')
                            ->keyLabel('Action')
                            ->valueLabel('Valeur')
                            ->addActionLabel('Ajouter une action')
                            ->helperText('Ex: notify_admin = true, send_push = true, send_sms = +22670000000'),
                    ]),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activer l\'alerte')
                            ->default(true),
                            
                        Forms\Components\TextInput::make('cooldown_minutes')
                            ->label('Cooldown (minutes)')
                            ->numeric()
                            ->default(30)
                            ->helperText('Temps minimum entre deux déclenchements'),
                            
                        Forms\Components\DateTimePicker::make('last_triggered_at')
                            ->label('Dernier déclenchement')
                            ->disabled()
                            ->native(false),
                    ])
                    ->columns(3),
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
                    
                Tables\Columns\TextColumn::make('trigger_type')
                    ->label('Déclencheur')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'order_delayed' => '⏰ Commande retard',
                        'courier_offline' => '📴 Coursier offline',
                        'low_couriers' => '👥 Peu coursiers',
                        'high_pending_orders' => '📦 Cmd en attente',
                        'withdrawal_pending' => '💸 Retraits',
                        'negative_rating' => '⭐ Avis négatif',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'order_delayed' => 'warning',
                        'courier_offline' => 'danger',
                        'low_couriers' => 'info',
                        'high_pending_orders' => 'danger',
                        'withdrawal_pending' => 'warning',
                        'negative_rating' => 'danger',
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('cooldown_minutes')
                    ->label('Cooldown')
                    ->suffix(' min')
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('last_triggered_at')
                    ->label('Dernier déclenchement')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Jamais')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')
                    ->label('Type')
                    ->options([
                        'order_delayed' => 'Commande en retard',
                        'courier_offline' => 'Coursier hors ligne',
                        'low_couriers' => 'Peu de coursiers',
                        'high_pending_orders' => 'Commandes en attente',
                        'withdrawal_pending' => 'Retraits en attente',
                        'negative_rating' => 'Avis négatif',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('test')
                    ->label('Tester')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Tester l\'alerte')
                    ->modalDescription('Voulez-vous simuler le déclenchement de cette alerte ?')
                    ->action(function (AutoAlert $record) {
                        $record->update(['last_triggered_at' => now()]);
                        // Ici on pourrait ajouter la logique de test
                    }),
                    
                Tables\Actions\Action::make('toggle')
                    ->label(fn (AutoAlert $record): string => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (AutoAlert $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (AutoAlert $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(fn (AutoAlert $record) => $record->update(['is_active' => !$record->is_active])),
                    
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
            'index' => Pages\ListAutoAlerts::route('/'),
            'create' => Pages\CreateAutoAlert::route('/create'),
            'edit' => Pages\EditAutoAlert::route('/{record}/edit'),
        ];
    }
}
