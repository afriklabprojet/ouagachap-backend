<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Models\Zone;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PricingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Tarification';
    protected static ?string $title           = 'Paramètres de tarification';
    protected static ?int    $navigationSort  = 5;
    protected static ?string $navigationGroup = 'Configuration';
    protected static string  $view            = 'filament.pages.pricing-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::where('group', SiteSetting::GROUP_PRICING_GLOBAL)
            ->get()->keyBy('key');

        $this->form->fill(
            $settings->mapWithKeys(fn ($s) => [$s->key => $s->getCastValue()])->toArray()
        );
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Tarification de base')
                ->icon('heroicon-o-calculator')
                ->description('Ces valeurs s\'appliquent aux zones qui n\'ont pas de tarification spécifique.')
                ->columns(3)
                ->schema([
                    TextInput::make('pricing_base_price_xof')
                        ->label('Prix de départ')
                        ->numeric()
                        ->minValue(0)
                        ->step(50)
                        ->suffix('FCFA')
                        ->helperText('Montant fixe à chaque commande.'),

                    TextInput::make('pricing_price_per_km_xof')
                        ->label('Prix par kilomètre')
                        ->numeric()
                        ->minValue(0)
                        ->step(10)
                        ->suffix('FCFA/km'),

                    TextInput::make('pricing_min_order_price_xof')
                        ->label('Prix minimum de commande')
                        ->numeric()
                        ->minValue(0)
                        ->step(50)
                        ->suffix('FCFA'),
                ]),

            Section::make('Commission plateforme')
                ->icon('heroicon-o-percent-badge')
                ->columns(3)
                ->schema([
                    TextInput::make('pricing_commission_rate_percent')
                        ->label('Taux de commission')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->step(0.5)
                        ->suffix('%')
                        ->helperText('Prélevé sur le montant versé au coursier.'),

                    TextInput::make('pricing_commission_min_xof')
                        ->label('Commission minimum')
                        ->numeric()
                        ->minValue(0)
                        ->step(25)
                        ->suffix('FCFA'),

                    TextInput::make('pricing_commission_max_xof')
                        ->label('Commission maximum')
                        ->numeric()
                        ->minValue(0)
                        ->step(100)
                        ->suffix('FCFA'),
                ]),

            Section::make('Surge Pricing (tarification dynamique)')
                ->icon('heroicon-o-arrow-trending-up')
                ->description('Augmente automatiquement les prix en période de forte demande.')
                ->columns(3)
                ->schema([
                    Toggle::make('pricing_surge_enabled')
                        ->label('Surge pricing activé')
                        ->columnSpanFull(),

                    TextInput::make('pricing_surge_multiplier_max')
                        ->label('Multiplicateur maximum')
                        ->numeric()
                        ->minValue(1.0)
                        ->maxValue(5.0)
                        ->step(0.1)
                        ->suffix('×')
                        ->helperText('Ex: 2.0 = prix doublé.'),

                    TextInput::make('pricing_surge_min_orders_threshold')
                        ->label('Seuil de déclenchement')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('commandes en attente')
                        ->helperText('Nombre de commandes pending avant activation du surge.'),

                    TextInput::make('pricing_surge_low_couriers_threshold')
                        ->label('Seuil bas de coursiers')
                        ->numeric()
                        ->minValue(1)
                        ->suffix('coursiers disponibles')
                        ->helperText('Si moins de N coursiers sont disponibles, le surge s\'active.'),
                ]),

            Section::make('Frais additionnels')
                ->icon('heroicon-o-plus-circle')
                ->columns(2)
                ->schema([
                    TextInput::make('pricing_extra_stop_fee_xof')
                        ->label('Frais par arrêt supplémentaire')
                        ->numeric()
                        ->minValue(0)
                        ->step(25)
                        ->suffix('FCFA'),

                    TextInput::make('pricing_night_fee_percent')
                        ->label('Supplément nuit')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->step(5)
                        ->suffix('%')
                        ->helperText('Appliqué entre 22h et 6h.'),

                    TextInput::make('pricing_fragile_fee_xof')
                        ->label('Frais colis fragile')
                        ->numeric()
                        ->minValue(0)
                        ->step(50)
                        ->suffix('FCFA'),

                    TextInput::make('pricing_heavy_fee_xof')
                        ->label('Frais colis lourd (> 5kg)')
                        ->numeric()
                        ->minValue(0)
                        ->step(50)
                        ->suffix('FCFA'),
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
            $isBool = is_bool($value);
            SiteSetting::updateOrCreate(['key' => $key], [
                'value' => $isBool ? ($value ? '1' : '0') : (string) ($value ?? ''),
                'type'  => $isBool ? SiteSetting::TYPE_BOOLEAN : SiteSetting::TYPE_NUMBER,
                'group' => SiteSetting::GROUP_PRICING_GLOBAL,
                'label' => $key,
            ]);
        }

        SiteSetting::clearCache();

        Notification::make()
            ->title('Tarification mise à jour')
            ->success()
            ->send();
    }
}
