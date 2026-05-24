<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourierQuestResource\Pages;
use App\Filament\Resources\CourierQuestResource\RelationManagers;
use App\Models\CourierQuest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourierQuestResource extends Resource
{
    protected static ?string $model = CourierQuest::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationLabel = 'Quêtes';

    protected static ?string $modelLabel = 'Quête';

    protected static ?string $pluralModelLabel = 'Quêtes coursiers';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clé unique')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText('Ex: daily_5_deliveries, weekly_top_rated'),
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->required(),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icône (emoji ou nom)')
                            ->maxLength(50)
                            ->placeholder('🏆 ou trophy'),
                    ])->columns(2),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\Select::make('quest_type')
                            ->label('Type de quête')
                            ->options([
                                'daily' => 'Quotidienne',
                                'weekly' => 'Hebdomadaire',
                                'monthly' => 'Mensuelle',
                                'milestone' => 'Jalon (permanent)',
                                'special' => 'Spéciale',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('target_value')
                            ->label('Objectif')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText('Nombre de livraisons / km / etc. à atteindre'),
                        Forms\Components\TextInput::make('bonus_xof')
                            ->label('Bonus (FCFA)')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA')
                            ->minValue(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon')
                    ->label('')
                    ->width(40),
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->description(fn (CourierQuest $record) => $record->description),
                Tables\Columns\TextColumn::make('key')
                    ->label('Clé')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quest_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'daily' => 'info',
                        'weekly' => 'primary',
                        'monthly' => 'warning',
                        'milestone' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'daily' => 'Quotidienne',
                        'weekly' => 'Hebdomadaire',
                        'monthly' => 'Mensuelle',
                        'milestone' => 'Jalon',
                        'special' => 'Spéciale',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('target_value')
                    ->label('Objectif')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_xof')
                    ->label('Bonus')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_count')
                    ->label('Coursiers engagés')
                    ->counts('progress')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('is_active', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('quest_type')
                    ->label('Type')
                    ->options([
                        'daily' => 'Quotidienne',
                        'weekly' => 'Hebdomadaire',
                        'monthly' => 'Mensuelle',
                        'milestone' => 'Jalon',
                        'special' => 'Spéciale',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (CourierQuest $record) => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (CourierQuest $record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (CourierQuest $record) => $record->is_active ? 'warning' : 'success')
                    ->action(fn (CourierQuest $record) => $record->update(['is_active' => !$record->is_active])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Détails de la quête')
                    ->schema([
                        Infolists\Components\TextEntry::make('icon')->label('Icône'),
                        Infolists\Components\TextEntry::make('title')->label('Titre'),
                        Infolists\Components\TextEntry::make('key')->label('Clé')->badge()->color('gray'),
                        Infolists\Components\TextEntry::make('description')->label('Description')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('quest_type')->label('Type'),
                        Infolists\Components\TextEntry::make('target_value')->label('Objectif'),
                        Infolists\Components\TextEntry::make('bonus_xof')->label('Bonus')->money('XOF'),
                        Infolists\Components\IconEntry::make('is_active')->label('Active')->boolean(),
                    ])->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProgressRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourierQuests::route('/'),
            'create' => Pages\CreateCourierQuest::route('/create'),
            'view' => Pages\ViewCourierQuest::route('/{record}'),
            'edit' => Pages\EditCourierQuest::route('/{record}/edit'),
        ];
    }
}
