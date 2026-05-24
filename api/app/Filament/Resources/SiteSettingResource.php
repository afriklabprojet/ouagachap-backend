<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Paramètres du site';

    protected static ?string $modelLabel = 'Paramètre';

    protected static ?string $pluralModelLabel = 'Paramètres du site';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Paramètre')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Clé')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->disabledOn('edit')
                            ->helperText('Identifiant unique du paramètre'),
                        Forms\Components\TextInput::make('label')
                            ->label('Libellé')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('group')
                            ->label('Groupe')
                            ->options(SiteSetting::getGroupLabels())
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'text' => 'Texte',
                                'textarea' => 'Texte long',
                                'number' => 'Nombre',
                                'boolean' => 'Oui/Non',
                                'image' => 'Image (URL)',
                                'json' => 'JSON',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Valeur')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label('Valeur')
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['text', 'number', 'image', null]))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('value')
                            ->label('Valeur (texte long)')
                            ->rows(5)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'textarea')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('value')
                            ->label('Valeur (oui/non)')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'boolean')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('value')
                            ->label('Valeur JSON')
                            ->rows(6)
                            ->visible(fn (Forms\Get $get) => $get('type') === 'json')
                            ->helperText('Entrez du JSON valide')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Groupe')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => SiteSetting::getGroupLabels()[$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->sortable()
                    ->description(fn (SiteSetting $record) => $record->key),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'text' => 'Texte',
                        'textarea' => 'Texte long',
                        'number' => 'Nombre',
                        'boolean' => 'Oui/Non',
                        'image' => 'Image',
                        'json' => 'JSON',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->limit(60)
                    ->placeholder('—')
                    ->wrap(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('group')
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Groupe')
                    ->getTitleFromRecordUsing(fn (SiteSetting $record): string => SiteSetting::getGroupLabels()[$record->group] ?? $record->group)
                    ->collapsible(),
            ])
            ->defaultGroup('group')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Groupe')
                    ->options(SiteSetting::getGroupLabels()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'text' => 'Texte',
                        'textarea' => 'Texte long',
                        'number' => 'Nombre',
                        'boolean' => 'Oui/Non',
                        'image' => 'Image',
                        'json' => 'JSON',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}
