<?php

namespace App\Filament\Resources\SupportChatResource\Pages;

use App\Filament\Resources\SupportChatResource;
use App\Models\SupportMessage;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportChat extends ViewRecord
{
    protected static string $resource = SupportChatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Répondre')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    SupportMessage::create([
                        'support_chat_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'message' => $data['message'],
                        'is_admin' => true,
                        'is_read' => false,
                    ]);
                    
                    $this->record->update(['last_message_at' => now()]);
                    
                    // Marquer les messages utilisateur comme lus
                    $this->record->messages()
                        ->where('is_admin', false)
                        ->where('is_read', false)
                        ->update(['is_read' => true]);
                }),
                
            Actions\Action::make('close')
                ->label('Fermer la conversation')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'open')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'closed'])),
                
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informations de la conversation')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Utilisateur'),
                        TextEntry::make('user.phone')
                            ->label('Téléphone'),
                        TextEntry::make('subject')
                            ->label('Sujet')
                            ->placeholder('Sans sujet'),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'open' => 'success',
                                'closed' => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('last_message_at')
                            ->label('Dernier message')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),
            ]);
    }
}
