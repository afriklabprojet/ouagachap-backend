<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RatingResource\Pages;
use App\Models\Rating;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationGroup = 'Opérations';
    
    protected static ?string $navigationLabel = 'Évaluations';
    
    protected static ?string $modelLabel = 'Évaluation';
    
    protected static ?string $pluralModelLabel = 'Évaluations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\TextInput::make('score')
                            ->label('Note')
                            ->disabled(),
                        Forms\Components\Textarea::make('comment')
                            ->label('Commentaire')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_visible')
                            ->label('Visible'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.tracking_number')
                    ->label('Commande')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rater.name')
                    ->label('Évaluateur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rated.name')
                    ->label('Évalué')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Note')
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => "⭐ {$state}/5"),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Commentaire')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->comment),
                Tables\Columns\TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(','),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('score')
                    ->label('Note')
                    ->options([
                        1 => '⭐ 1 étoile',
                        2 => '⭐ 2 étoiles',
                        3 => '⭐ 3 étoiles',
                        4 => '⭐ 4 étoiles',
                        5 => '⭐ 5 étoiles',
                    ]),
                Tables\Filters\Filter::make('low_rating')
                    ->label('Notes basses (< 3)')
                    ->query(fn (Builder $query) => $query->where('score', '<', 3)),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visible'),
                Tables\Filters\Filter::make('with_comment')
                    ->label('Avec commentaire')
                    ->query(fn (Builder $query) => $query->whereNotNull('comment')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('toggle_visibility')
                    ->label(fn ($record) => $record->is_visible ? 'Masquer' : 'Afficher')
                    ->icon(fn ($record) => $record->is_visible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->action(fn ($record) => $record->update(['is_visible' => !$record->is_visible])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('make_visible')
                    ->label('Rendre visible')
                    ->action(fn ($records) => $records->each->update(['is_visible' => true])),
                Tables\Actions\BulkAction::make('make_hidden')
                    ->label('Masquer')
                    ->action(fn ($records) => $records->each->update(['is_visible' => false])),
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
            'index' => Pages\ListRatings::route('/'),
            'view' => Pages\ViewRating::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
