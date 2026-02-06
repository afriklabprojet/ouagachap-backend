<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintResource\Pages;
use App\Filament\Resources\ComplaintResource\RelationManagers;
use App\Models\Complaint;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ComplaintResource extends Resource
{
    protected static ?string $model = Complaint::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $navigationLabel = 'Réclamations';
    
    protected static ?string $modelLabel = 'Réclamation';
    
    protected static ?string $pluralModelLabel = 'Réclamations';
    
    protected static ?string $navigationGroup = 'Support Client';
    
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['open', 'in_progress'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('status', 'open')->count();
        return $count > 0 ? 'danger' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('ticket_number')
                            ->label('N° Ticket')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                            
                        Forms\Components\Select::make('user_id')
                            ->label('Client')
                            ->relationship('user', 'name', fn (Builder $query) => $query->whereIn('role', ['client', 'courier']))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Utilisateur #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required(),
                            
                        Forms\Components\Select::make('order_id')
                            ->label('Commande associée')
                            ->relationship('order', 'tracking_code')
                            ->searchable()
                            ->preload(),
                            
                        Forms\Components\Select::make('courier_id')
                            ->label('Coursier concerné')
                            ->relationship('courier', 'name', fn (Builder $query) => $query->where('role', 'courier'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? $record->phone ?? "Coursier #{$record->id}")
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Détails de la réclamation')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'delivery_issue' => '🚚 Problème de livraison',
                                'payment_issue' => '💰 Problème de paiement',
                                'courier_behavior' => '👤 Comportement coursier',
                                'app_bug' => '🐛 Bug application',
                                'other' => '📋 Autre',
                            ])
                            ->required(),
                            
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options([
                                'low' => '🟢 Basse',
                                'medium' => '🟡 Moyenne',
                                'high' => '🟠 Haute',
                                'urgent' => '🔴 Urgente',
                            ])
                            ->default('medium')
                            ->required(),
                            
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'open' => '🔵 Ouvert',
                                'in_progress' => '🟡 En cours',
                                'resolved' => '🟢 Résolu',
                                'closed' => '⚫ Fermé',
                            ])
                            ->default('open')
                            ->required(),
                            
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->relationship('assignedAdmin', 'name', fn (Builder $query) => $query->where('role', 'admin'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? "Admin #{$record->id}")
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Objet')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4),
                            
                        Forms\Components\Textarea::make('resolution')
                            ->label('Résolution')
                            ->rows(3)
                            ->helperText('Décrivez la résolution du problème'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('Ticket')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('subject')
                    ->label('Objet')
                    ->limit(30)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'delivery_issue' => '🚚 Livraison',
                        'payment_issue' => '💰 Paiement',
                        'courier_behavior' => '👤 Coursier',
                        'app_bug' => '🐛 Bug',
                        'other' => '📋 Autre',
                    }),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    }),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => '🔵 Ouvert',
                        'in_progress' => '🟡 En cours',
                        'resolved' => '🟢 Résolu',
                        'closed' => '⚫ Fermé',
                    }),
                    
                Tables\Columns\TextColumn::make('assignedAdmin.name')
                    ->label('Assigné à')
                    ->placeholder('Non assigné')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'open' => 'Ouvert',
                        'in_progress' => 'En cours',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                    ]),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options([
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ]),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'delivery_issue' => 'Problème de livraison',
                        'payment_issue' => 'Problème de paiement',
                        'courier_behavior' => 'Comportement coursier',
                        'app_bug' => 'Bug application',
                        'other' => 'Autre',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_me')
                    ->label('M\'assigner')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Complaint $record): bool => $record->assigned_to === null)
                    ->action(fn (Complaint $record) => $record->update(['assigned_to' => auth()->id()])),
                    
                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Complaint $record): bool => $record->status !== 'resolved' && $record->status !== 'closed')
                    ->form([
                        Forms\Components\Textarea::make('resolution')
                            ->label('Résolution')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Complaint $record, array $data) {
                        $record->update([
                            'status' => 'resolved',
                            'resolution' => $data['resolution'],
                            'resolved_at' => now(),
                        ]);
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_in_progress')
                        ->label('Marquer en cours')
                        ->icon('heroicon-o-clock')
                        ->action(fn ($records) => $records->each->update(['status' => 'in_progress'])),
                        
                    Tables\Actions\BulkAction::make('close')
                        ->label('Fermer')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'closed'])),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informations')
                    ->schema([
                        TextEntry::make('ticket_number')
                            ->label('N° Ticket')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('user.name')
                            ->label('Client'),
                        TextEntry::make('order.tracking_code')
                            ->label('Commande')
                            ->placeholder('N/A'),
                        TextEntry::make('courier.name')
                            ->label('Coursier')
                            ->placeholder('N/A'),
                    ])
                    ->columns(4),

                Section::make('Détails')
                    ->schema([
                        TextEntry::make('subject')
                            ->label('Objet'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('resolution')
                            ->label('Résolution')
                            ->placeholder('En attente de résolution')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComplaints::route('/'),
            'create' => Pages\CreateComplaint::route('/create'),
            'view' => Pages\ViewComplaint::route('/{record}'),
            'edit' => Pages\EditComplaint::route('/{record}/edit'),
        ];
    }
}
