<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ClientAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Config App Client';
    protected static ?string $title           = 'Configuration de l\'application Client';
    protected static ?int    $navigationSort  = 3;
    protected static ?string $navigationGroup = 'Configuration';
    protected static string  $view            = 'filament.pages.client-app-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::where('group', SiteSetting::GROUP_APP_CLIENT)
            ->get()->keyBy('key');

        $this->form->fill(
            $settings->mapWithKeys(fn ($s) => [$s->key => $s->getCastValue()])->toArray()
        );
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Versions & Mises à jour')
                ->icon('heroicon-o-arrow-up-circle')
                ->columns(2)
                ->schema([
                    TextInput::make('client_app_min_version')
                        ->label('Version minimale requise')
                        ->placeholder('1.0.0')
                        ->helperText('Force la mise à jour si l\'app est en-dessous de cette version.'),

                    TextInput::make('client_app_latest_version')
                        ->label('Dernière version disponible')
                        ->placeholder('1.2.0'),

                    TextInput::make('client_app_google_play_url')
                        ->label('Lien Google Play')
                        ->url()
                        ->placeholder('https://play.google.com/store/apps/details?id=...')
                        ->columnSpanFull(),

                    TextInput::make('client_app_app_store_url')
                        ->label('Lien App Store')
                        ->url()
                        ->placeholder('https://apps.apple.com/...')
                        ->columnSpanFull(),

                    TextInput::make('client_app_apk_direct_url')
                        ->label('Lien APK direct (Android)')
                        ->url()
                        ->placeholder('https://ouagachap.pro/app/client-latest.apk')
                        ->columnSpanFull(),
                ]),

            Section::make('Maintenance')
                ->icon('heroicon-o-wrench-screwdriver')
                ->columns(2)
                ->schema([
                    Toggle::make('client_app_maintenance_mode')
                        ->label('Mode maintenance activé')
                        ->helperText('Bloque l\'accès à l\'app client.')
                        ->columnSpanFull(),

                    Textarea::make('client_app_maintenance_message')
                        ->label('Message affiché')
                        ->rows(2)
                        ->placeholder('Maintenance en cours. Veuillez réessayer dans quelques minutes.')
                        ->columnSpanFull(),
                ]),

            Section::make('Limites & Règles')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columns(2)
                ->schema([
                    TextInput::make('client_app_max_orders_per_day')
                        ->label('Commandes max par jour / client')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('commandes'),

                    TextInput::make('client_app_order_cancel_delay_minutes')
                        ->label('Délai annulation commande')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('minutes')
                        ->helperText('Après ce délai, le client ne peut plus annuler.'),

                    TextInput::make('client_app_support_phone')
                        ->label('Téléphone support client')
                        ->tel()
                        ->placeholder('+226 70 00 00 00'),

                    TextInput::make('client_app_support_whatsapp')
                        ->label('WhatsApp support client')
                        ->tel()
                        ->placeholder('+226 70 00 00 00'),
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
            SiteSetting::updateOrCreate(['key' => $key], [
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''),
                'type'  => is_bool($value) ? SiteSetting::TYPE_BOOLEAN : SiteSetting::TYPE_TEXT,
                'group' => SiteSetting::GROUP_APP_CLIENT,
                'label' => $key,
            ]);
        }

        SiteSetting::clearCache();

        Notification::make()
            ->title('Configuration sauvegardée')
            ->success()
            ->send();
    }
}
