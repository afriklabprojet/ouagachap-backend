<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Services\SappayService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class SappaySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Paiement Sappay';
    protected static ?string $title           = 'Configuration Sappay';
    protected static ?int    $navigationSort  = 8;
    protected static ?string $navigationGroup = 'Configuration';
    protected static string  $view            = 'filament.pages.sappay-settings';

    public ?array $data = [];
    public string $connectionStatus = 'unknown';
    public string $connectionMessage = '';

    public function mount(): void
    {
        $this->data = [
            'sappay_sandbox'        => config('sappay.sandbox', false),
            'sappay_public_url'     => config('sappay.public_url'),
            'sappay_checkout_url'   => config('sappay.checkout_url'),
            'sappay_min_amount'     => config('sappay.min_amount', 100),
            'sappay_max_amount'     => config('sappay.max_amount', 1000000),
        ];

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Credentials (fichier .env)')
                ->icon('heroicon-o-key')
                ->description('Ces valeurs sont dans le fichier .env du serveur. Modifiez-les directement sur le serveur.')
                ->columns(2)
                ->schema([
                    TextInput::make('sappay_client_id_display')
                        ->label('SAPPAY_CLIENT_ID')
                        ->default(fn () => $this->maskValue(config('sappay.client_id', '')))
                        ->disabled()
                        ->helperText('Configuré dans .env'),

                    TextInput::make('sappay_client_secret_display')
                        ->label('SAPPAY_CLIENT_SECRET')
                        ->default(fn () => $this->maskValue(config('sappay.client_secret', '')))
                        ->disabled()
                        ->helperText('Configuré dans .env'),

                    TextInput::make('sappay_username_display')
                        ->label('SAPPAY_USERNAME')
                        ->default(fn () => config('sappay.username', '—'))
                        ->disabled()
                        ->helperText('Email du compte business Sappay'),

                    Placeholder::make('token_status')
                        ->label('Token en cache')
                        ->content(fn () => Cache::has('sappay:access_token') ? '✅ Token valide en cache' : '⚠️ Pas de token — sera généré au premier paiement'),
                ]),

            Section::make('Paramètres')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(2)
                ->schema([
                    Toggle::make('sappay_sandbox')
                        ->label('Mode sandbox (test)')
                        ->helperText('Désactiver en production.')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('sappay_public_url')
                        ->label('Base URL Public API')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('sappay_checkout_url')
                        ->label('Base URL Checkout API')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Limites de montant')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    TextInput::make('sappay_min_amount')
                        ->label('Montant minimum')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('FCFA')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Configuré dans config/sappay.php'),

                    TextInput::make('sappay_max_amount')
                        ->label('Montant maximum')
                        ->numeric()
                        ->suffix('FCFA')
                        ->disabled()
                        ->dehydrated(false),
                ]),

            Section::make('Moyens de paiement actifs')
                ->icon('heroicon-o-device-phone-mobile')
                ->schema([
                    Placeholder::make('payment_methods_list')
                        ->label('')
                        ->content(function () {
                            $methods = config('sappay.payment_methods', []);
                            $lines = collect($methods)->map(fn ($m, $code) =>
                                "{$m['icon']} **{$m['name']}** — processor_id: `{$m['payment_processor_id']}` — OTP: " .
                                ($m['requires_get_otp'] ? 'Oui' : 'Non')
                            )->implode("\n\n");
                            return $lines ?: 'Aucun moyen de paiement configuré.';
                        }),
                ]),

        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label('Tester la connexion')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action('testConnection'),
        ];
    }

    public function testConnection(): void
    {
        try {
            $service = app(SappayService::class);
            Cache::forget('sappay:access_token');
            $token = $service->getAccessToken();

            if ($token) {
                Notification::make()
                    ->title('Connexion Sappay réussie')
                    ->body('Token obtenu avec succès. Le service est opérationnel.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Connexion échouée')
                    ->body('Impossible d\'obtenir un token. Vérifiez SAPPAY_CLIENT_ID, SAPPAY_CLIENT_SECRET, SAPPAY_USERNAME et SAPPAY_PASSWORD dans le .env.')
                    ->danger()
                    ->persistent()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de connexion')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        // Cette page est en lecture seule pour la config .env
        Notification::make()
            ->title('Les credentials se configurent dans le fichier .env')
            ->warning()
            ->send();
    }

    private function maskValue(string $value): string
    {
        if (empty($value)) return '— non configuré —';
        $len = strlen($value);
        if ($len <= 6) return str_repeat('*', $len);
        return substr($value, 0, 3) . str_repeat('*', $len - 6) . substr($value, -3);
    }
}
