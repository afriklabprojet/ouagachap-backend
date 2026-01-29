<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    
    protected static ?string $navigationGroup = 'Administration';
    
    protected static ?string $navigationLabel = 'Logs d\'activité';
    
    protected static ?string $modelLabel = 'Log d\'activité';
    
    protected static ?string $pluralModelLabel = 'Logs d\'activité';

    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails du log')
                    ->schema([
                        Forms\Components\TextInput::make('log_type')
                            ->label('Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('subject_type')
                            ->label('Type de sujet')
                            ->disabled(),
                        Forms\Components\TextInput::make('subject_id')
                            ->label('ID du sujet')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Utilisateur')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Nom')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('Adresse IP')
                            ->disabled(),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Valeurs')
                    ->schema([
                        Forms\Components\KeyValue::make('old_values')
                            ->label('Anciennes valeurs')
                            ->disabled(),
                        Forms\Components\KeyValue::make('new_values')
                            ->label('Nouvelles valeurs')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->default('Système')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('log_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                        'primary' => fn ($state) => !in_array($state, ['created', 'updated', 'deleted']),
                    ]),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Sujet')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->limit(8),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_type')
                    ->label('Type')
                    ->options([
                        'created' => 'Création',
                        'updated' => 'Mise à jour',
                        'deleted' => 'Suppression',
                        'login' => 'Connexion',
                        'logout' => 'Déconnexion',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Modèle')
                    ->options([
                        'App\\Models\\Order' => 'Commande',
                        'App\\Models\\User' => 'Utilisateur',
                        'App\\Models\\Payment' => 'Paiement',
                        'App\\Models\\Withdrawal' => 'Retrait',
                    ]),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Pas de suppression en masse pour les logs
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
