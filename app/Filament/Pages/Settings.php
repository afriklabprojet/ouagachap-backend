<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class Settings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $title = 'Paramètres Système';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.settings';

    // Données du formulaire
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getSettings());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        // Tarification
                        Forms\Components\Tabs\Tab::make('Tarification')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\Section::make('Prix de base')
                                    ->description('Configuration des tarifs de livraison')
                                    ->schema([
                                        Forms\Components\TextInput::make('pricing.base_price')
                                            ->label('Prix de base (FCFA)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('FCFA')
                                            ->default(500),
                                        Forms\Components\TextInput::make('pricing.price_per_km')
                                            ->label('Prix par km (FCFA)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('FCFA/km')
                                            ->default(100),
                                        Forms\Components\TextInput::make('pricing.min_price')
                                            ->label('Prix minimum (FCFA)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('FCFA')
                                            ->default(500),
                                        Forms\Components\TextInput::make('pricing.max_distance')
                                            ->label('Distance maximum (km)')
                                            ->numeric()
                                            ->suffix('km')
                                            ->default(50),
                                    ])->columns(2),

                                Forms\Components\Section::make('Suppléments')
                                    ->schema([
                                        Forms\Components\TextInput::make('pricing.small_package')
                                            ->label('Petit colis')
                                            ->numeric()
                                            ->suffix('FCFA')
                                            ->default(0),
                                        Forms\Components\TextInput::make('pricing.medium_package')
                                            ->label('Moyen colis')
                                            ->numeric()
                                            ->suffix('FCFA')
                                            ->default(200),
                                        Forms\Components\TextInput::make('pricing.large_package')
                                            ->label('Grand colis')
                                            ->numeric()
                                            ->suffix('FCFA')
                                            ->default(500),
                                    ])->columns(3),
                            ]),

                        // Commissions
                        Forms\Components\Tabs\Tab::make('Commissions')
                            ->icon('heroicon-o-chart-pie')
                            ->schema([
                                Forms\Components\Section::make('Commission plateforme')
                                    ->description('Pourcentage prélevé sur chaque livraison')
                                    ->schema([
                                        Forms\Components\TextInput::make('commission.platform_rate')
                                            ->label('Taux de commission (%)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('%')
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(15),
                                        Forms\Components\Toggle::make('commission.apply_on_tips')
                                            ->label('Appliquer sur les pourboires')
                                            ->default(false),
                                    ])->columns(2),

                                Forms\Components\Section::make('Retraits coursiers')
                                    ->schema([
                                        Forms\Components\TextInput::make('withdrawal.min_amount')
                                            ->label('Montant minimum de retrait (FCFA)')
                                            ->numeric()
                                            ->required()
                                            ->suffix('FCFA')
                                            ->default(1000),
                                        Forms\Components\TextInput::make('withdrawal.max_daily')
                                            ->label('Retrait max par jour (FCFA)')
                                            ->numeric()
                                            ->suffix('FCFA')
                                            ->default(100000),
                                        Forms\Components\TextInput::make('withdrawal.processing_fee')
                                            ->label('Frais de traitement (FCFA)')
                                            ->numeric()
                                            ->suffix('FCFA')
                                            ->default(100),
                                    ])->columns(3),
                            ]),

                        // Notifications
                        Forms\Components\Tabs\Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Forms\Components\Section::make('Notifications Push')
                                    ->schema([
                                        Forms\Components\Toggle::make('notifications.push_enabled')
                                            ->label('Activer les notifications push')
                                            ->default(true),
                                        Forms\Components\Toggle::make('notifications.sms_enabled')
                                            ->label('Activer les SMS')
                                            ->default(true),
                                        Forms\Components\Toggle::make('notifications.email_enabled')
                                            ->label('Activer les emails')
                                            ->default(false),
                                    ])->columns(3),

                                Forms\Components\Section::make('Événements')
                                    ->schema([
                                        Forms\Components\Toggle::make('notifications.on_order_created')
                                            ->label('Nouvelle commande')
                                            ->default(true),
                                        Forms\Components\Toggle::make('notifications.on_order_assigned')
                                            ->label('Commande assignée')
                                            ->default(true),
                                        Forms\Components\Toggle::make('notifications.on_order_delivered')
                                            ->label('Commande livrée')
                                            ->default(true),
                                        Forms\Components\Toggle::make('notifications.on_withdrawal_approved')
                                            ->label('Retrait approuvé')
                                            ->default(true),
                                    ])->columns(2),
                            ]),

                        // Application
                        Forms\Components\Tabs\Tab::make('Application')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->schema([
                                Forms\Components\Section::make('Configuration générale')
                                    ->schema([
                                        Forms\Components\TextInput::make('app.name')
                                            ->label('Nom de l\'application')
                                            ->default('OUAGA CHAP'),
                                        Forms\Components\TextInput::make('app.support_phone')
                                            ->label('Téléphone support')
                                            ->tel()
                                            ->default('+226 70 00 00 00'),
                                        Forms\Components\TextInput::make('app.support_email')
                                            ->label('Email support')
                                            ->email()
                                            ->default('support@ouagachap.bf'),
                                    ])->columns(3),

                                Forms\Components\Section::make('Heures d\'opération')
                                    ->schema([
                                        Forms\Components\TimePicker::make('app.opening_time')
                                            ->label('Heure d\'ouverture')
                                            ->default('06:00'),
                                        Forms\Components\TimePicker::make('app.closing_time')
                                            ->label('Heure de fermeture')
                                            ->default('22:00'),
                                        Forms\Components\Toggle::make('app.open_24h')
                                            ->label('Ouvert 24h/24')
                                            ->default(false),
                                    ])->columns(3),

                                Forms\Components\Section::make('Maintenance')
                                    ->schema([
                                        Forms\Components\Toggle::make('app.maintenance_mode')
                                            ->label('Mode maintenance')
                                            ->helperText('Désactive temporairement l\'application')
                                            ->default(false),
                                        Forms\Components\Textarea::make('app.maintenance_message')
                                            ->label('Message de maintenance')
                                            ->default('Application en maintenance. Veuillez réessayer plus tard.'),
                                    ]),
                            ]),

                        // Sécurité
                        Forms\Components\Tabs\Tab::make('Sécurité')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Authentification')
                                    ->schema([
                                        Forms\Components\Toggle::make('security.require_otp')
                                            ->label('Exiger OTP pour connexion')
                                            ->default(true),
                                        Forms\Components\TextInput::make('security.otp_expiry_minutes')
                                            ->label('Expiration OTP (minutes)')
                                            ->numeric()
                                            ->default(5),
                                        Forms\Components\TextInput::make('security.max_login_attempts')
                                            ->label('Tentatives max de connexion')
                                            ->numeric()
                                            ->default(5),
                                    ])->columns(3),

                                Forms\Components\Section::make('Sessions')
                                    ->schema([
                                        Forms\Components\TextInput::make('security.token_expiry_days')
                                            ->label('Expiration token (jours)')
                                            ->numeric()
                                            ->default(30),
                                        Forms\Components\Toggle::make('security.single_session')
                                            ->label('Session unique par utilisateur')
                                            ->default(false),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Sauvegarder les paramètres dans le cache (ou base de données)
        foreach ($data as $group => $values) {
            if (is_array($values)) {
                foreach ($values as $key => $value) {
                    Cache::forever("settings.{$group}.{$key}", $value);
                }
            }
        }

        Notification::make()
            ->title('Paramètres sauvegardés')
            ->body('Les modifications ont été enregistrées avec succès.')
            ->success()
            ->send();
    }

    protected function getSettings(): array
    {
        return [
            'pricing' => [
                'base_price' => Cache::get('settings.pricing.base_price', 500),
                'price_per_km' => Cache::get('settings.pricing.price_per_km', 100),
                'min_price' => Cache::get('settings.pricing.min_price', 500),
                'max_distance' => Cache::get('settings.pricing.max_distance', 50),
                'small_package' => Cache::get('settings.pricing.small_package', 0),
                'medium_package' => Cache::get('settings.pricing.medium_package', 200),
                'large_package' => Cache::get('settings.pricing.large_package', 500),
            ],
            'commission' => [
                'platform_rate' => Cache::get('settings.commission.platform_rate', 15),
                'apply_on_tips' => Cache::get('settings.commission.apply_on_tips', false),
            ],
            'withdrawal' => [
                'min_amount' => Cache::get('settings.withdrawal.min_amount', 1000),
                'max_daily' => Cache::get('settings.withdrawal.max_daily', 100000),
                'processing_fee' => Cache::get('settings.withdrawal.processing_fee', 100),
            ],
            'notifications' => [
                'push_enabled' => Cache::get('settings.notifications.push_enabled', true),
                'sms_enabled' => Cache::get('settings.notifications.sms_enabled', true),
                'email_enabled' => Cache::get('settings.notifications.email_enabled', false),
                'on_order_created' => Cache::get('settings.notifications.on_order_created', true),
                'on_order_assigned' => Cache::get('settings.notifications.on_order_assigned', true),
                'on_order_delivered' => Cache::get('settings.notifications.on_order_delivered', true),
                'on_withdrawal_approved' => Cache::get('settings.notifications.on_withdrawal_approved', true),
            ],
            'app' => [
                'name' => Cache::get('settings.app.name', 'OUAGA CHAP'),
                'support_phone' => Cache::get('settings.app.support_phone', '+226 70 00 00 00'),
                'support_email' => Cache::get('settings.app.support_email', 'support@ouagachap.bf'),
                'opening_time' => Cache::get('settings.app.opening_time', '06:00'),
                'closing_time' => Cache::get('settings.app.closing_time', '22:00'),
                'open_24h' => Cache::get('settings.app.open_24h', false),
                'maintenance_mode' => Cache::get('settings.app.maintenance_mode', false),
                'maintenance_message' => Cache::get('settings.app.maintenance_message', 'Application en maintenance.'),
            ],
            'security' => [
                'require_otp' => Cache::get('settings.security.require_otp', true),
                'otp_expiry_minutes' => Cache::get('settings.security.otp_expiry_minutes', 5),
                'max_login_attempts' => Cache::get('settings.security.max_login_attempts', 5),
                'token_expiry_days' => Cache::get('settings.security.token_expiry_days', 30),
                'single_session' => Cache::get('settings.security.single_session', false),
            ],
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('super_admin') || $user->can('edit_settings'));
    }
}
