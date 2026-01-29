<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationLabel = 'Bannières & Annonces';
    
    protected static ?string $modelLabel = 'Bannière';
    
    protected static ?string $pluralModelLabel = 'Bannières';
    
    protected static ?string $navigationGroup = 'Marketing';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la bannière')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                            
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Image')
                            ->image()
                            ->directory('banners')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('675'),
                            
                        Forms\Components\TextInput::make('action_url')
                            ->label('URL d\'action')
                            ->url()
                            ->placeholder('https://...'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'promo' => '🎁 Promotion',
                                'announcement' => '📢 Annonce',
                                'alert' => '⚠️ Alerte',
                                'info' => 'ℹ️ Information',
                            ])
                            ->default('announcement')
                            ->required(),
                            
                        Forms\Components\Select::make('target')
                            ->label('Audience cible')
                            ->options([
                                'all' => '👥 Tous',
                                'clients' => '🛒 Clients uniquement',
                                'couriers' => '🏍️ Coursiers uniquement',
                            ])
                            ->default('all')
                            ->required(),
                            
                        Forms\Components\Select::make('position')
                            ->label('Position')
                            ->options([
                                'home_top' => '🏠 Accueil - Haut',
                                'home_bottom' => '🏠 Accueil - Bas',
                                'orders_list' => '📋 Liste des commandes',
                                'profile' => '👤 Profil',
                            ])
                            ->default('home_top')
                            ->required(),
                            
                        Forms\Components\TextInput::make('priority')
                            ->label('Priorité')
                            ->numeric()
                            ->default(0)
                            ->helperText('Plus élevé = plus visible'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Planification')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                            
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Date de début')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),
                            
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Date de fin')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->after('starts_at'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular(false)
                    ->size(60),
                    
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'promo' => '🎁 Promo',
                        'announcement' => '📢 Annonce',
                        'alert' => '⚠️ Alerte',
                        'info' => 'ℹ️ Info',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'promo' => 'success',
                        'announcement' => 'primary',
                        'alert' => 'danger',
                        'info' => 'info',
                    }),
                    
                Tables\Columns\TextColumn::make('target')
                    ->label('Cible')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'all' => '👥 Tous',
                        'clients' => '🛒 Clients',
                        'couriers' => '🏍️ Coursiers',
                    }),
                    
                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'home_top' => 'Accueil Haut',
                        'home_bottom' => 'Accueil Bas',
                        'orders_list' => 'Commandes',
                        'profile' => 'Profil',
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime('d/m/Y')
                    ->placeholder('Immédiat')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y')
                    ->placeholder('Illimité')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'promo' => 'Promotion',
                        'announcement' => 'Annonce',
                        'alert' => 'Alerte',
                        'info' => 'Information',
                    ]),
                    
                Tables\Filters\SelectFilter::make('target')
                    ->label('Cible')
                    ->options([
                        'all' => 'Tous',
                        'clients' => 'Clients',
                        'couriers' => 'Coursiers',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (Banner $record): string => $record->is_active ? 'Désactiver' : 'Activer')
                    ->icon(fn (Banner $record): string => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (Banner $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(fn (Banner $record) => $record->update(['is_active' => !$record->is_active])),
                    
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
            ])
            ->reorderable('priority');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
