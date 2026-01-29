<?php

namespace App\Filament\Pages;

use App\Models\InAppNotification;
use App\Models\User;
use App\Services\PushNotificationService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SendNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static string $view = 'filament.pages.send-notification';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Envoyer notification';

    protected static ?string $title = 'Envoyer une notification';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public int $recipientCount = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Destinataires')
                    ->description('Choisissez les destinataires de la notification')
                    ->schema([
                        Forms\Components\Radio::make('target_type')
                            ->label('Ciblage')
                            ->options([
                                'all' => '📢 Tous les utilisateurs',
                                'clients' => '👥 Clients uniquement',
                                'couriers' => '🛵 Coursiers uniquement',
                                'active_couriers' => '✅ Coursiers actifs (en ligne)',
                                'specific' => '🎯 Utilisateurs spécifiques',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updateRecipientCount($state)),

                        Forms\Components\Select::make('specific_users')
                            ->label('Sélectionner les utilisateurs')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn () => User::all()->mapWithKeys(fn ($user) => [
                                $user->id => $user->name ?? $user->phone ?? "Utilisateur #{$user->id}"
                            ]))
                            ->visible(fn (Forms\Get $get) => $get('target_type') === 'specific')
                            ->required(fn (Forms\Get $get) => $get('target_type') === 'specific'),

                        Forms\Components\Placeholder::make('recipient_count')
                            ->label('Nombre de destinataires')
                            ->content(fn () => $this->recipientCount . ' utilisateurs')
                            ->visible(fn (Forms\Get $get) => $get('target_type') !== 'specific'),
                    ]),

                Forms\Components\Section::make('Contenu de la notification')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type de notification')
                            ->options([
                                'promo' => '🎁 Promotion',
                                'system' => '🔔 Système / Annonce',
                            ])
                            ->default('system')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ex: Nouvelle promotion !')
                            ->helperText('Maximum 100 caractères'),

                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(4)
                            ->maxLength(500)
                            ->placeholder('Ex: Profitez de -20% sur votre prochaine commande avec le code PROMO20')
                            ->helperText('Maximum 500 caractères'),

                        Forms\Components\TextInput::make('action_url')
                            ->label('Lien d\'action (optionnel)')
                            ->url()
                            ->placeholder('https://...')
                            ->helperText('URL vers laquelle l\'utilisateur sera redirigé'),
                    ])->columns(1),

                Forms\Components\Section::make('Options d\'envoi')
                    ->schema([
                        Forms\Components\Toggle::make('send_push')
                            ->label('Envoyer aussi en push notification')
                            ->helperText('Envoyer une notification push en plus de la notification in-app')
                            ->default(true),

                        Forms\Components\Toggle::make('send_immediately')
                            ->label('Envoyer immédiatement')
                            ->default(true),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function updateRecipientCount(?string $targetType): void
    {
        $this->recipientCount = match ($targetType) {
            'all' => User::count(),
            'clients' => User::where('role', \App\Enums\UserRole::CLIENT)->count(),
            'couriers' => User::where('role', \App\Enums\UserRole::COURIER)->count(),
            'active_couriers' => User::where('role', \App\Enums\UserRole::COURIER)
                ->where('is_available', true)
                ->count(),
            default => 0,
        };
    }

    public function send(): void
    {
        $data = $this->form->getState();

        // Récupérer les destinataires
        $users = match ($data['target_type']) {
            'all' => User::all(),
            'clients' => User::where('role', \App\Enums\UserRole::CLIENT)->get(),
            'couriers' => User::where('role', \App\Enums\UserRole::COURIER)->get(),
            'active_couriers' => User::where('role', \App\Enums\UserRole::COURIER)
                ->where('is_available', true)
                ->get(),
            'specific' => User::whereIn('id', $data['specific_users'] ?? [])->get(),
            default => collect(),
        };

        if ($users->isEmpty()) {
            Notification::make()
                ->danger()
                ->title('Aucun destinataire')
                ->body('Aucun utilisateur ne correspond aux critères sélectionnés.')
                ->send();
            return;
        }

        $count = 0;
        $pushService = app(PushNotificationService::class);

        DB::transaction(function () use ($users, $data, &$count, $pushService) {
            foreach ($users as $user) {
                // Créer notification in-app
                InAppNotification::notify(
                    $user,
                    $data['type'],
                    $data['title'],
                    $data['message'],
                    ['sent_from' => 'admin_panel', 'sent_by' => auth()->id()],
                    $data['action_url'] ?? null
                );

                // Envoyer push si demandé
                if ($data['send_push'] ?? false) {
                    $pushService->send(
                        $user,
                        $data['title'],
                        $data['message'],
                        ['type' => $data['type'], 'action_url' => $data['action_url'] ?? null]
                    );
                }

                $count++;
            }
        });

        Notification::make()
            ->success()
            ->title('Notifications envoyées !')
            ->body("$count notification(s) envoyée(s) avec succès.")
            ->send();

        // Reset form
        $this->form->fill();
        $this->recipientCount = 0;
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('send')
                ->label('📤 Envoyer les notifications')
                ->submit('send')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane'),
        ];
    }
}
