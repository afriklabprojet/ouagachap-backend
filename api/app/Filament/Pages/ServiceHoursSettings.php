<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ServiceHoursSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Heures d\'ouverture';
    protected static ?string $title           = 'Heures d\'ouverture du service';
    protected static ?int    $navigationSort  = 6;
    protected static ?string $navigationGroup = 'Configuration';
    protected static string  $view            = 'filament.pages.service-hours-settings';

    public ?array $data = [];

    private static array $DAYS = [
        'monday'    => 'Lundi',
        'tuesday'   => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday'  => 'Jeudi',
        'friday'    => 'Vendredi',
        'saturday'  => 'Samedi',
        'sunday'    => 'Dimanche',
    ];

    private static function hours(): array
    {
        $h = [];
        for ($i = 0; $i < 24; $i++) {
            $h[sprintf('%02d:00', $i)] = sprintf('%02dh00', $i);
            $h[sprintf('%02d:30', $i)] = sprintf('%02dh30', $i);
        }
        return $h;
    }

    public function mount(): void
    {
        $settings = SiteSetting::where('group', SiteSetting::GROUP_HOURS)
            ->get()->keyBy('key');

        $this->form->fill(
            $settings->mapWithKeys(fn ($s) => [$s->key => $s->getCastValue()])->toArray()
        );
    }

    public function form(Form $form): Form
    {
        $hours = self::hours();

        $dayFields = [];
        foreach (self::$DAYS as $key => $label) {
            $dayFields[] = Section::make($label)
                ->columns(3)
                ->compact()
                ->schema([
                    Toggle::make("hours_{$key}_open")
                        ->label('Ouvert')
                        ->inline(false),

                    Select::make("hours_{$key}_from")
                        ->label('Ouverture')
                        ->options($hours)
                        ->default('06:00'),

                    Select::make("hours_{$key}_to")
                        ->label('Fermeture')
                        ->options($hours)
                        ->default('22:00'),
                ]);
        }

        return $form->schema([

            Section::make('Service 24h/24')
                ->icon('heroicon-o-sun')
                ->schema([
                    Toggle::make('hours_service_24h')
                        ->label('Service disponible 24h/24 7j/7')
                        ->helperText('Si activé, les horaires par jour sont ignorés.')
                        ->columnSpanFull(),
                ]),

            Section::make('Horaires par jour')
                ->icon('heroicon-o-calendar-days')
                ->description('Jours et heures pendant lesquels les commandes sont acceptées.')
                ->schema($dayFields),

            Section::make('Jours fériés & fermeture exceptionnelle')
                ->icon('heroicon-o-x-circle')
                ->schema([
                    Toggle::make('hours_closed_today')
                        ->label('Fermé aujourd\'hui (fermeture exceptionnelle)')
                        ->helperText('Désactive le service pour la journée courante uniquement.')
                        ->columnSpanFull(),

                    Textarea::make('hours_closed_message')
                        ->label('Message affiché quand fermé')
                        ->rows(2)
                        ->placeholder('Le service est actuellement fermé. Nous reprenons à 6h00.')
                        ->columnSpanFull(),
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
                'type'  => $isBool ? SiteSetting::TYPE_BOOLEAN : SiteSetting::TYPE_TEXT,
                'group' => SiteSetting::GROUP_HOURS,
                'label' => $key,
            ]);
        }

        SiteSetting::clearCache();

        Notification::make()
            ->title('Horaires sauvegardés')
            ->success()
            ->send();
    }
}
