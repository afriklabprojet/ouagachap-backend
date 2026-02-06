<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    
    protected static ?string $navigationLabel = 'FAQs';
    
    protected static ?string $modelLabel = 'FAQ';
    
    protected static ?string $pluralModelLabel = 'FAQs';
    
    protected static ?string $navigationGroup = 'Support Client';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question & Réponse')
                    ->description('Contenu de la FAQ')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                'general' => '📋 Général',
                                'orders' => '📦 Commandes',
                                'payment' => '💰 Paiement',
                                'delivery' => '🚚 Livraison',
                                'account' => '👤 Compte',
                                'wallet' => '💳 Portefeuille',
                            ])
                            ->default('general')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('question')
                            ->label('Question')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Comment puis-je suivre ma livraison ?'),

                        Forms\Components\Textarea::make('answer')
                            ->label('Réponse')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull()
                            ->placeholder('Entrez la réponse détaillée à cette question...'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Paramètres')
                    ->schema([
                        Forms\Components\TextInput::make('order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->helperText('Les FAQs avec un ordre plus petit apparaissent en premier'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Seules les FAQs actives sont visibles dans l\'app'),

                        Forms\Components\Placeholder::make('views')
                            ->label('Nombre de vues')
                            ->content(fn (?Faq $record): string => $record?->views ?? '0')
                            ->visibleOn('edit'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'general' => '📋 Général',
                        'orders' => '📦 Commandes',
                        'payment' => '💰 Paiement',
                        'delivery' => '🚚 Livraison',
                        'account' => '👤 Compte',
                        'wallet' => '💳 Portefeuille',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'orders' => 'info',
                        'payment' => 'success',
                        'delivery' => 'warning',
                        'account' => 'primary',
                        'wallet' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn (Faq $record): string => $record->question),

                Tables\Columns\TextColumn::make('answer')
                    ->label('Réponse')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('order')
                    ->label('Ordre')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('views')
                    ->label('Vues')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modifiée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options([
                        'general' => 'Général',
                        'orders' => 'Commandes',
                        'payment' => 'Paiement',
                        'delivery' => 'Livraison',
                        'account' => 'Compte',
                        'wallet' => 'Portefeuille',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->trueLabel('Actives uniquement')
                    ->falseLabel('Inactives uniquement')
                    ->placeholder('Toutes'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('toggleActive')
                        ->label(fn (Faq $record): string => $record->is_active ? 'Désactiver' : 'Activer')
                        ->icon(fn (Faq $record): string => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn (Faq $record): string => $record->is_active ? 'warning' : 'success')
                        ->action(fn (Faq $record) => $record->update(['is_active' => !$record->is_active])),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activer')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Désactiver')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                ]),
            ]);
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
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
