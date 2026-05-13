<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CourierAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';
    protected static ?string $navigationLabel = 'Config App Coursier';
    protected static ?string $title = 'Configuration de l\'application Coursier';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Configuration';

    protected static string $view = 'filament.pages.courier-app-settings';

    // ── form state ──────────────────────────────────────────────────────────

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::whereIn('group', [
            SiteSetting::GROUP_APP_COURIER,
            SiteSetting::GROUP_DISPATCH,
            SiteSetting::GROUP_WALLET,
        ])->get()->keyBy('key');

        $this->form->fill($settings->mapWithKeys(fn ($s) => [$s->key => $s->getCastValue()])->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ==============================================================
                // Section 1 — App mobile coursier
                // ==============================================================
                Section::make('Application mobile coursier')
                    ->description('Paramètres visibles ou utilisés directement dans l\'app Android/iOS des coursiers.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->columns(2)
                    ->schema([
                        TextInput::make('courier_app_min_version')
                            ->label('Version minimale requise')
                            ->placeholder('1.0.0')
                            ->helperText('Force la mise à jour si l\'app est en-dessous de cette version.'),

                        TextInput::make('courier_app_max_active_orders')
                            ->label('Commandes simultanées max')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->suffix('commandes'),

                        TextInput::make('courier_app_gps_interval_seconds')
                            ->label('Intervalle GPS')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(120)
                            ->suffix('secondes'),

                        TextInput::make('courier_app_support_phone')
                            ->label('Téléphone support')
                            ->tel()
                            ->placeholder('+226 70 00 00 00'),

                        TextInput::make('courier_app_support_whatsapp')
                            ->label('WhatsApp support')
                            ->tel()
                            ->placeholder('+226 70 00 00 00'),

                        Grid::make(2)->schema([
                            Toggle::make('courier_app_maintenance_mode')
                                ->label('Mode maintenance')
                                ->helperText('Bloque la connexion des coursiers.')
                                ->columnSpan(1),
                        ])->columnSpanFull(),

                        Textarea::make('courier_app_maintenance_message')
                            ->label('Message de maintenance')
                            ->rows(2)
                            ->placeholder('Maintenance en cours. Veuillez réessayer dans quelques minutes.')
                            ->columnSpanFull(),
                    ]),

                // ==============================================================
                // Section 2 — Bonus & parrainage
                // ==============================================================
                Section::make('Bonus & Parrainage')
                    ->description('Montants crédités automatiquement sur le portefeuille des coursiers.')
                    ->icon('heroicon-o-gift')
                    ->columns(2)
                    ->schema([
                        TextInput::make('courier_app_welcome_bonus_xof')
                            ->label('Bonus de bienvenue')
                            ->numeric()
                            ->minValue(0)
                            ->step(100)
                            ->suffix('FCFA')
                            ->helperText('Crédité à la 1re activation du compte.'),

                        TextInput::make('courier_app_referral_bonus_xof')
                            ->label('Bonus de parrainage')
                            ->numeric()
                            ->minValue(0)
                            ->step(100)
                            ->suffix('FCFA')
                            ->helperText('Versé au parrain après la 1re livraison du filleul.'),
                    ]),

                // ==============================================================
                // Section 3 — Dispatch & Affectation
                // ==============================================================
                Section::make('Dispatch & Affectation automatique')
                    ->description('Règles d\'assignation des commandes aux coursiers disponibles.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([
                        Toggle::make('dispatch_auto_assign_enabled')
                            ->label('Dispatch automatique')
                            ->helperText('Désactiver pour passer en affectation manuelle.')
                            ->columnSpanFull(),

                        TextInput::make('dispatch_accept_timeout_seconds')
                            ->label('Délai d\'acceptation')
                            ->numeric()
                            ->minValue(10)
                            ->maxValue(120)
                            ->suffix('secondes')
                            ->helperText('Temps avant de passer au coursier suivant.'),

                        TextInput::make('dispatch_radius_km')
                            ->label('Rayon de recherche')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->suffix('km')
                            ->helperText('Distance max autour du point de collecte.'),

                        TextInput::make('dispatch_max_attempts')
                            ->label('Tentatives max')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(20)
                            ->suffix('coursiers')
                            ->helperText('Nombre de coursiers contactés avant échec.'),

                        Select::make('dispatch_algorithm')
                            ->label('Algorithme de sélection')
                            ->options([
                                'nearest' => 'Le plus proche (nearest)',
                                'rating' => 'Meilleure note (rating)',
                                'round_robin' => 'Rotation équitable (round_robin)',
                            ])
                            ->helperText('Stratégie de choix du coursier à notifier en premier.'),

                        TextInput::make('dispatch_surge_multiplier_max')
                            ->label('Plafond du multiplicateur surge')
                            ->numeric()
                            ->minValue(1.0)
                            ->maxValue(5.0)
                            ->step(0.1)
                            ->suffix('×')
                            ->helperText('Ex: 2.0 = prix max doublé en période de forte demande.'),
                    ]),

                // ==============================================================
                // Section 4 — Portefeuille & Retraits
                // ==============================================================
                Section::make('Portefeuille & Retraits')
                    ->description('Règles de gestion du portefeuille et des demandes de retrait des coursiers.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        TextInput::make('wallet_min_withdrawal_xof')
                            ->label('Retrait minimum')
                            ->numeric()
                            ->minValue(0)
                            ->step(100)
                            ->suffix('FCFA'),

                        TextInput::make('wallet_max_withdrawal_xof')
                            ->label('Retrait maximum')
                            ->numeric()
                            ->minValue(0)
                            ->step(1000)
                            ->suffix('FCFA'),

                        TextInput::make('wallet_withdrawal_fee_percent')
                            ->label('Frais de retrait')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->step(0.5)
                            ->suffix('%')
                            ->helperText('0 = aucun frais.'),

                        Select::make('wallet_payout_schedule')
                            ->label('Fréquence de virement')
                            ->options([
                                'daily' => 'Quotidien',
                                'weekly' => 'Hebdomadaire',
                                'manual' => 'Manuel (admin uniquement)',
                            ]),

                        Toggle::make('wallet_auto_payout_enabled')
                            ->label('Virement automatique')
                            ->helperText('Envoie automatiquement les retraits validés via Mobile Money.')
                            ->columnSpanFull(),

                        Toggle::make('wallet_commission_display_enabled')
                            ->label('Afficher le détail de commission dans l\'app')
                            ->helperText('Le coursier voit la commission prélevée sur chaque livraison.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $setting = SiteSetting::where('key', $key)->first();
            if (! $setting) {
                continue;
            }

            $stored = match ($setting->type) {
                SiteSetting::TYPE_BOOLEAN => $value ? '1' : '0',
                SiteSetting::TYPE_JSON => is_array($value) ? json_encode($value) : ($value ?? '[]'),
                default => (string) ($value ?? ''),
            };

            $setting->update(['value' => $stored]);
        }

        SiteSetting::clearCache();

        Notification::make()
            ->title('Configuration sauvegardée')
            ->body('Les paramètres de l\'app coursier ont été mis à jour.')
            ->success()
            ->send();
    }
}
