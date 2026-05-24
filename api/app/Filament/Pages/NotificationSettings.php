<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifications';
    protected static ?string $title           = 'Paramètres des notifications';
    protected static ?int    $navigationSort  = 7;
    protected static ?string $navigationGroup = 'Configuration';
    protected static string  $view            = 'filament.pages.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::where('group', SiteSetting::GROUP_NOTIF)
            ->get()->keyBy('key');

        $this->form->fill(
            $settings->mapWithKeys(fn ($s) => [$s->key => $s->getCastValue()])->toArray()
        );
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Canaux actifs')
                ->icon('heroicon-o-signal')
                ->columns(3)
                ->schema([
                    Toggle::make('notif_push_enabled')
                        ->label('Notifications Push (FCM)')
                        ->helperText('Notifications dans l\'app mobile.'),

                    Toggle::make('notif_sms_enabled')
                        ->label('SMS')
                        ->helperText('Envoi de SMS via Infobip.'),

                    Toggle::make('notif_whatsapp_enabled')
                        ->label('WhatsApp')
                        ->helperText('Messages via WhatsApp Business API.'),
                ]),

            Section::make('Templates — Client')
                ->icon('heroicon-o-user')
                ->description('Variables disponibles : {order_number}, {courier_name}, {amount}, {status}, {otp}')
                ->columns(1)
                ->schema([
                    Textarea::make('notif_tpl_order_created_client')
                        ->label('Commande créée')
                        ->rows(2)
                        ->placeholder('Votre commande #{order_number} a été créée. Un coursier va être assigné.'),

                    Textarea::make('notif_tpl_courier_assigned_client')
                        ->label('Coursier assigné')
                        ->rows(2)
                        ->placeholder('{courier_name} a accepté votre commande #{order_number} et est en route.'),

                    Textarea::make('notif_tpl_order_picked_up_client')
                        ->label('Colis récupéré')
                        ->rows(2)
                        ->placeholder('Votre colis (commande #{order_number}) a été récupéré. Livraison en cours.'),

                    Textarea::make('notif_tpl_order_delivered_client')
                        ->label('Commande livrée')
                        ->rows(2)
                        ->placeholder('Votre commande #{order_number} a été livrée. Merci de noter votre coursier.'),

                    Textarea::make('notif_tpl_order_cancelled_client')
                        ->label('Commande annulée')
                        ->rows(2)
                        ->placeholder('Votre commande #{order_number} a été annulée.'),

                    Textarea::make('notif_tpl_payment_success_client')
                        ->label('Paiement réussi')
                        ->rows(2)
                        ->placeholder('Paiement de {amount} FCFA confirmé pour la commande #{order_number}.'),
                ]),

            Section::make('Templates — Coursier')
                ->icon('heroicon-o-truck')
                ->description('Variables disponibles : {order_number}, {pickup_address}, {delivery_address}, {amount}, {distance}')
                ->columns(1)
                ->schema([
                    Textarea::make('notif_tpl_new_order_courier')
                        ->label('Nouvelle commande disponible')
                        ->rows(2)
                        ->placeholder('Nouvelle commande #{order_number} à {distance}km. Montant: {amount} FCFA. Acceptez rapidement !'),

                    Textarea::make('notif_tpl_order_assigned_courier')
                        ->label('Commande assignée')
                        ->rows(2)
                        ->placeholder('La commande #{order_number} vous a été assignée. Rendez-vous à : {pickup_address}'),

                    Textarea::make('notif_tpl_order_cancelled_courier')
                        ->label('Commande annulée (coursier)')
                        ->rows(2)
                        ->placeholder('La commande #{order_number} a été annulée.'),

                    Textarea::make('notif_tpl_wallet_credit_courier')
                        ->label('Crédit wallet')
                        ->rows(2)
                        ->placeholder('Votre compte a été crédité de {amount} FCFA pour la livraison #{order_number}.'),

                    Textarea::make('notif_tpl_withdrawal_processed_courier')
                        ->label('Retrait traité')
                        ->rows(2)
                        ->placeholder('Votre retrait de {amount} FCFA a été envoyé sur votre {method}.'),
                ]),

            Section::make('Limites d\'envoi')
                ->icon('heroicon-o-shield-check')
                ->columns(2)
                ->schema([
                    TextInput::make('notif_max_push_per_day')
                        ->label('Push max par utilisateur / jour')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('notifications'),

                    TextInput::make('notif_max_sms_per_day')
                        ->label('SMS max par numéro / jour')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('SMS'),

                    TextInput::make('notif_otp_expiry_minutes')
                        ->label('Expiration OTP')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->suffix('minutes'),

                    TextInput::make('notif_otp_max_attempts')
                        ->label('Tentatives OTP max')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(10)
                        ->suffix('tentatives'),
                ]),

        ])->statePath('data');
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
            $isBool    = is_bool($value);
            $isNumeric = is_numeric($value) && !$isBool;
            SiteSetting::updateOrCreate(['key' => $key], [
                'value' => $isBool ? ($value ? '1' : '0') : (string) ($value ?? ''),
                'type'  => match (true) {
                    $isBool    => SiteSetting::TYPE_BOOLEAN,
                    $isNumeric => SiteSetting::TYPE_NUMBER,
                    default    => SiteSetting::TYPE_TEXTAREA,
                },
                'group' => SiteSetting::GROUP_NOTIF,
                'label' => $key,
            ]);
        }

        SiteSetting::clearCache();

        Notification::make()
            ->title('Paramètres notifications sauvegardés')
            ->success()
            ->send();
    }
}
