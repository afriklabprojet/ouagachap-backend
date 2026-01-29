<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Storage;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.site-settings';
    protected static ?string $navigationLabel = 'Paramètres du Site';
    protected static ?string $title = 'Paramètres du Site Web';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    protected function loadSettings(): array
    {
        $settings = SiteSetting::all()->pluck('value', 'key')->toArray();
        
        // Convertir les JSON en tableaux
        foreach (['features', 'pricing', 'testimonials'] as $jsonKey) {
            if (isset($settings[$jsonKey])) {
                $settings[$jsonKey] = json_decode($settings[$jsonKey], true) ?? [];
            }
        }
        
        return $settings;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        // Onglet Général
                        Tabs\Tab::make('Général')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Section::make('Identité du site')
                                    ->description('Logo et informations générales')
                                    ->schema([
                                        FileUpload::make('site_logo')
                                            ->label('Logo du site')
                                            ->image()
                                            ->directory('site')
                                            ->visibility('public')
                                            ->imageResizeMode('contain')
                                            ->imageCropAspectRatio('1:1')
                                            ->maxSize(2048),
                                        TextInput::make('site_name')
                                            ->label('Nom du site')
                                            ->default('OUAGA CHAP')
                                            ->required(),
                                        TextInput::make('site_tagline')
                                            ->label('Slogan')
                                            ->default('Livraison rapide à Ouagadougou'),
                                    ])->columns(2),
                                    
                                Section::make('SEO')
                                    ->description('Optimisation pour les moteurs de recherche')
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('seo_title')
                                            ->label('Titre SEO')
                                            ->maxLength(70),
                                        Textarea::make('seo_description')
                                            ->label('Description SEO')
                                            ->maxLength(160)
                                            ->rows(2),
                                        TextInput::make('seo_keywords')
                                            ->label('Mots-clés (séparés par des virgules)'),
                                    ]),
                            ]),

                        // Onglet Hero
                        Tabs\Tab::make('Section Hero')
                            ->icon('heroicon-o-rocket-launch')
                            ->schema([
                                Section::make('Textes principaux')
                                    ->schema([
                                        TextInput::make('hero_badge')
                                            ->label('Badge (petit texte au-dessus)')
                                            ->default('🚀 #1 à Ouagadougou'),
                                        TextInput::make('hero_title')
                                            ->label('Titre principal')
                                            ->default('Livraison express à Ouagadougou')
                                            ->required(),
                                        TextInput::make('hero_highlight')
                                            ->label('Mot mis en évidence (coloré)')
                                            ->default('express'),
                                        Textarea::make('hero_description')
                                            ->label('Description')
                                            ->rows(3)
                                            ->default('Vos colis livrés en moins de 30 minutes. Courses, documents, repas... Nous livrons tout ce dont vous avez besoin, partout dans la ville.'),
                                    ]),
                                    
                                Section::make('Statistiques')
                                    ->description('Chiffres affichés dans le hero')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('stat_deliveries')
                                                ->label('Nombre de livraisons')
                                                ->default('10K+'),
                                            TextInput::make('stat_couriers')
                                                ->label('Nombre de coursiers')
                                                ->default('500+'),
                                            TextInput::make('stat_rating')
                                                ->label('Note moyenne')
                                                ->default('4.8★'),
                                        ]),
                                    ]),
                            ]),

                        // Onglet Fonctionnalités
                        Tabs\Tab::make('Fonctionnalités')
                            ->icon('heroicon-o-squares-2x2')
                            ->schema([
                                Section::make('Section Fonctionnalités')
                                    ->schema([
                                        TextInput::make('features_title')
                                            ->label('Titre de la section')
                                            ->default('Pourquoi choisir OUAGA CHAP?'),
                                        Textarea::make('features_description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->default('Une application conçue pour faciliter votre quotidien avec des fonctionnalités pensées pour vous.'),
                                    ]),
                                    
                                Repeater::make('features')
                                    ->label('Liste des fonctionnalités')
                                    ->schema([
                                        TextInput::make('icon')
                                            ->label('Emoji/Icône')
                                            ->default('⚡'),
                                        TextInput::make('title')
                                            ->label('Titre')
                                            ->required(),
                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->required(),
                                        TextInput::make('color')
                                            ->label('Couleur (ex: primary, green, blue)')
                                            ->default('primary'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(6)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Nouvelle fonctionnalité'),
                            ]),

                        // Onglet Tarifs
                        Tabs\Tab::make('Tarifs')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Section::make('Section Tarifs')
                                    ->schema([
                                        TextInput::make('pricing_title')
                                            ->label('Titre de la section')
                                            ->default('Des prix transparents'),
                                        Textarea::make('pricing_description')
                                            ->label('Description')
                                            ->rows(2)
                                            ->default('Pas de frais cachés. Le prix affiché est le prix payé.'),
                                    ]),
                                    
                                Repeater::make('pricing')
                                    ->label('Plans tarifaires')
                                    ->schema([
                                        TextInput::make('emoji')
                                            ->label('Emoji')
                                            ->default('🛵'),
                                        TextInput::make('name')
                                            ->label('Nom du plan')
                                            ->required(),
                                        TextInput::make('subtitle')
                                            ->label('Sous-titre')
                                            ->default('Petits colis'),
                                        TextInput::make('base_price')
                                            ->label('Prix de base (FCFA)')
                                            ->numeric()
                                            ->required(),
                                        TextInput::make('price_per_km')
                                            ->label('Prix par km (FCFA)')
                                            ->numeric()
                                            ->required(),
                                        Textarea::make('features')
                                            ->label('Caractéristiques (une par ligne)')
                                            ->rows(4)
                                            ->helperText('Écrivez une caractéristique par ligne'),
                                        Toggle::make('is_popular')
                                            ->label('Populaire (mis en avant)')
                                            ->default(false),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(3)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nouveau plan'),
                            ]),

                        // Onglet Témoignages
                        Tabs\Tab::make('Témoignages')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Section Témoignages')
                                    ->schema([
                                        TextInput::make('testimonials_title')
                                            ->label('Titre de la section')
                                            ->default('Ce que disent nos utilisateurs'),
                                    ]),
                                    
                                Repeater::make('testimonials')
                                    ->label('Liste des témoignages')
                                    ->schema([
                                        Textarea::make('content')
                                            ->label('Témoignage')
                                            ->rows(3)
                                            ->required(),
                                        TextInput::make('author')
                                            ->label('Nom')
                                            ->required(),
                                        TextInput::make('role')
                                            ->label('Rôle/Profession')
                                            ->required(),
                                        TextInput::make('initials')
                                            ->label('Initiales')
                                            ->maxLength(2)
                                            ->default('AB'),
                                        TextInput::make('rating')
                                            ->label('Note (1-5)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(5)
                                            ->default(5),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(3)
                                    ->reorderable()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['author'] ?? 'Nouveau témoignage'),
                            ]),

                        // Onglet Coursier
                        Tabs\Tab::make('Section Coursier')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                Section::make('Appel à devenir coursier')
                                    ->schema([
                                        TextInput::make('courier_title')
                                            ->label('Titre')
                                            ->default('Devenez coursier et gagnez de l\'argent'),
                                        Textarea::make('courier_description')
                                            ->label('Description')
                                            ->rows(3)
                                            ->default('Rejoignez notre équipe de coursiers et travaillez à votre rythme. Gagnez jusqu\'à 150,000 FCFA par mois en effectuant des livraisons.'),
                                        TextInput::make('courier_commission')
                                            ->label('Commission coursier (%)')
                                            ->numeric()
                                            ->default(85),
                                        Textarea::make('courier_benefits')
                                            ->label('Avantages (un par ligne)')
                                            ->rows(4)
                                            ->default("Horaires flexibles - Travaillez quand vous voulez\nPaiements quotidiens - Retirez vos gains chaque jour\nBonus et primes - Gagnez plus avec les défis"),
                                    ]),
                            ]),

                        // Onglet Contact
                        Tabs\Tab::make('Contact')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Informations de contact')
                                    ->schema([
                                        TextInput::make('contact_phone')
                                            ->label('Téléphone')
                                            ->tel()
                                            ->default('+226 70 00 00 00'),
                                        TextInput::make('contact_whatsapp')
                                            ->label('WhatsApp')
                                            ->tel()
                                            ->default('+226 70 00 00 00'),
                                        TextInput::make('contact_email')
                                            ->label('Email')
                                            ->email()
                                            ->default('contact@ouagachap.com'),
                                        TextInput::make('contact_address')
                                            ->label('Adresse')
                                            ->default('Ouagadougou, Burkina Faso'),
                                    ])->columns(2),
                                    
                                Section::make('Réseaux sociaux')
                                    ->schema([
                                        TextInput::make('social_facebook')
                                            ->label('Facebook URL')
                                            ->url()
                                            ->placeholder('https://facebook.com/ouagachap'),
                                        TextInput::make('social_twitter')
                                            ->label('Twitter/X URL')
                                            ->url()
                                            ->placeholder('https://twitter.com/ouagachap'),
                                        TextInput::make('social_instagram')
                                            ->label('Instagram URL')
                                            ->url()
                                            ->placeholder('https://instagram.com/ouagachap'),
                                        TextInput::make('social_tiktok')
                                            ->label('TikTok URL')
                                            ->url()
                                            ->placeholder('https://tiktok.com/@ouagachap'),
                                    ])->columns(2),
                            ]),

                        // Onglet Téléchargements
                        Tabs\Tab::make('Téléchargements')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->schema([
                                Section::make('Applications')
                                    ->schema([
                                        FileUpload::make('apk_client')
                                            ->label('APK Application Client')
                                            ->acceptedFileTypes(['application/vnd.android.package-archive', 'application/octet-stream'])
                                            ->directory('downloads')
                                            ->visibility('public')
                                            ->maxSize(102400), // 100MB
                                        TextInput::make('apk_client_version')
                                            ->label('Version Client')
                                            ->default('1.0.0'),
                                        TextInput::make('apk_client_size')
                                            ->label('Taille Client')
                                            ->default('25 MB'),
                                    ])->columns(3),
                                    
                                Section::make('Application Coursier')
                                    ->schema([
                                        FileUpload::make('apk_courier')
                                            ->label('APK Application Coursier')
                                            ->acceptedFileTypes(['application/vnd.android.package-archive', 'application/octet-stream'])
                                            ->directory('downloads')
                                            ->visibility('public')
                                            ->maxSize(102400),
                                        TextInput::make('apk_courier_version')
                                            ->label('Version Coursier')
                                            ->default('1.0.0'),
                                        TextInput::make('apk_courier_size')
                                            ->label('Taille Coursier')
                                            ->default('28 MB'),
                                    ])->columns(3),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        foreach ($data as $key => $value) {
            // Déterminer le type et le groupe
            $type = $this->getSettingType($key);
            $group = $this->getSettingGroup($key);
            
            // Convertir les tableaux en JSON
            if (is_array($value)) {
                $value = json_encode($value);
                $type = SiteSetting::TYPE_JSON;
            }
            
            SiteSetting::set($key, $value, $type, $group);
        }
        
        // Vider le cache
        SiteSetting::clearCache();
        
        Notification::make()
            ->title('Paramètres sauvegardés')
            ->body('Les modifications ont été enregistrées avec succès.')
            ->success()
            ->send();
    }

    protected function getSettingType(string $key): string
    {
        return match(true) {
            str_contains($key, 'description') => SiteSetting::TYPE_TEXTAREA,
            str_contains($key, 'logo') || str_contains($key, 'image') || str_contains($key, 'apk_') => SiteSetting::TYPE_IMAGE,
            str_contains($key, 'price') || str_contains($key, 'commission') || str_contains($key, 'rating') => SiteSetting::TYPE_NUMBER,
            in_array($key, ['features', 'pricing', 'testimonials']) => SiteSetting::TYPE_JSON,
            default => SiteSetting::TYPE_TEXT,
        };
    }

    protected function getSettingGroup(string $key): string
    {
        return match(true) {
            str_starts_with($key, 'hero_') || str_starts_with($key, 'stat_') => SiteSetting::GROUP_HERO,
            str_starts_with($key, 'feature') => SiteSetting::GROUP_FEATURES,
            str_starts_with($key, 'pricing') => SiteSetting::GROUP_PRICING,
            str_starts_with($key, 'testimonial') => SiteSetting::GROUP_TESTIMONIALS,
            str_starts_with($key, 'contact_') => SiteSetting::GROUP_CONTACT,
            str_starts_with($key, 'social_') => SiteSetting::GROUP_SOCIAL,
            str_starts_with($key, 'seo_') => SiteSetting::GROUP_SEO,
            str_starts_with($key, 'courier_') => 'courier',
            default => SiteSetting::GROUP_GENERAL,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Sauvegarder')
                ->icon('heroicon-o-check')
                ->action('save')
                ->color('primary'),
                
            Action::make('preview')
                ->label('Voir le site')
                ->icon('heroicon-o-eye')
                ->url('/')
                ->openUrlInNewTab()
                ->color('gray'),
        ];
    }
}
