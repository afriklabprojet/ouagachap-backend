<?php

namespace App\Filament\Resources\SupportChatResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $recordTitleAttribute = 'message';
    
    protected static ?string $title = 'Messages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('user.name')
                            ->label('De')
                            ->badge()
                            ->color(fn ($record) => $record->is_admin ? 'success' : 'info')
                            ->formatStateUsing(fn ($state, $record) => $record->is_admin ? "👨‍💼 $state (Admin)" : "👤 $state"),
                            
                        Tables\Columns\TextColumn::make('created_at')
                            ->label('Date')
                            ->dateTime('d/m/Y H:i')
                            ->alignEnd()
                            ->color('gray'),
                    ]),
                    
                    Tables\Columns\TextColumn::make('message')
                        ->label('Message')
                        ->wrap(),
                ]),
            ])
            ->contentGrid([
                'md' => 1,
                'xl' => 1,
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Envoyer un message')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['is_admin'] = true;
                        return $data;
                    })
                    ->after(function () {
                        $this->ownerRecord->update(['last_message_at' => now()]);
                        
                        // Marquer les messages utilisateur comme lus
                        $this->ownerRecord->messages()
                            ->where('is_admin', false)
                            ->where('is_read', false)
                            ->update(['is_read' => true]);
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
