# Panneau d'Administration Filament

## Vue d'ensemble

Le panneau d'administration utilise **Filament 3** pour gérer:
- Utilisateurs (clients & coursiers)
- Commandes et livraisons
- Paiements et portefeuilles
- Pages légales
- Paramètres du site

## Accès

- **URL**: `https://api.ouagachap.com/admin`
- **Connexion**: Email + Mot de passe (rôle `admin` requis)

## Ressources disponibles

### Users (Utilisateurs)

Gestion complète des utilisateurs.

**Filtres:**
- Rôle (client, coursier, admin)
- Statut (actif, inactif, suspendu)
- Date d'inscription

**Actions:**
- Activer/Désactiver un compte
- Voir les commandes de l'utilisateur
- Gérer le portefeuille coursier

```php
// app/Filament/Resources/UserResource.php

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('phone')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('role')
                    ->options(UserRole::toArray())
                    ->required(),
                Select::make('status')
                    ->options(UserStatus::toArray()),
                    
                // Section coursier
                Section::make('Informations coursier')
                    ->visible(fn ($record) => $record?->role === UserRole::COURIER)
                    ->schema([
                        TextInput::make('vehicle_type'),
                        TextInput::make('vehicle_plate'),
                        Toggle::make('is_available'),
                    ]),
            ]);
    }
}
```

### Orders (Commandes)

Suivi et gestion des commandes.

**Colonnes:**
- UUID, Client, Coursier
- Statut, Prix, Distance
- Adresses (pickup/delivery)
- Date de création

**Filtres:**
- Statut (pending, accepted, picked_up, delivered, cancelled)
- Date (aujourd'hui, cette semaine, ce mois)
- Zone géographique

**Actions:**
- Voir les détails
- Assigner un coursier
- Annuler la commande

```php
// app/Filament/Resources/OrderResource.php

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('uuid')
                ->searchable()
                ->copyable(),
            TextColumn::make('client.name'),
            TextColumn::make('courier.name'),
            BadgeColumn::make('status')
                ->colors([
                    'warning' => 'pending',
                    'info' => 'accepted',
                    'primary' => 'picked_up',
                    'success' => 'delivered',
                    'danger' => 'cancelled',
                ]),
            TextColumn::make('price')
                ->money('XOF'),
            TextColumn::make('created_at')
                ->dateTime(),
        ])
        ->defaultSort('created_at', 'desc');
}
```

### Payments (Paiements)

Suivi des transactions.

**Colonnes:**
- Commande, Montant, Méthode
- Statut, Référence externe
- Date

### Wallets (Portefeuilles)

Gestion des portefeuilles coursiers.

**Actions:**
- Voir l'historique des transactions
- Ajuster le solde manuellement
- Approuver les retraits

### LegalPages (Pages légales)

Gestion du contenu légal.

**Pages:**
- CGU (Conditions Générales)
- Politique de confidentialité
- Mentions légales
- FAQ

```php
// app/Filament/Resources/LegalPageResource.php

class LegalPageResource extends Resource
{
    protected static ?string $model = LegalPage::class;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('title')->required(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options([
                        'cgu' => 'CGU',
                        'privacy' => 'Politique de confidentialité',
                        'mentions' => 'Mentions légales',
                        'faq' => 'FAQ',
                    ]),
                RichEditor::make('content')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_active'),
            ]);
    }
}
```

### SiteSettings (Paramètres du site)

Configuration de la landing page.

**Champs éditables:**
- Textes hero (titre, sous-titre)
- Statistiques (utilisateurs, livraisons, etc.)
- Liens de téléchargement (App Store, Play Store)
- Coordonnées de contact

```php
// app/Filament/Resources/SiteSettingResource.php

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('key')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
                TextInput::make('label')->required(),
                TextInput::make('value'),
                Select::make('type')
                    ->options([
                        'text' => 'Texte',
                        'number' => 'Nombre',
                        'url' => 'URL',
                    ]),
            ]);
    }
}
```

## Widgets Dashboard

### Statistiques globales

```php
// app/Filament/Widgets/StatsOverview.php

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Commandes aujourd\'hui', Order::whereDate('created_at', today())->count())
                ->description('Total des commandes')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),
                
            Stat::make('Revenus', Order::whereDate('created_at', today())->sum('price') . ' FCFA')
                ->description('Chiffre d\'affaires')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
                
            Stat::make('Coursiers actifs', User::courier()->active()->available()->count())
                ->description('En ligne maintenant')
                ->descriptionIcon('heroicon-m-truck'),
        ];
    }
}
```

### Graphique des commandes

```php
// app/Filament/Widgets/OrdersChart.php

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Commandes par jour';
    
    protected function getData(): array
    {
        $data = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();
            
        return [
            'datasets' => [
                [
                    'label' => 'Commandes',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
```

## Personnalisation

### Thème et couleurs

```php
// app/Providers/Filament/AdminPanelProvider.php

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors([
            'primary' => Color::Red,  // Rouge OUAGA CHAP
        ])
        ->brandName('OUAGA CHAP Admin')
        ->brandLogo(asset('images/logo.png'))
        ->favicon(asset('images/favicon.ico'));
}
```

### Navigation

```php
// Dans chaque Resource

protected static ?string $navigationIcon = 'heroicon-o-users';
protected static ?string $navigationGroup = 'Gestion';
protected static ?int $navigationSort = 1;
```

## Création d'un admin

```bash
php artisan make:filament-user
```

Ou via seeder:

```php
User::create([
    'name' => 'Admin',
    'phone' => '+22670000000',
    'role' => UserRole::ADMIN,
    'status' => UserStatus::ACTIVE,
    'password' => bcrypt('password'),
]);
```

## Permissions

Les politiques Laravel contrôlent l'accès aux ressources:

```php
// app/Policies/OrderPolicy.php

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }
    
    public function update(User $user, Order $order): bool
    {
        return $user->role === UserRole::ADMIN;
    }
    
    public function delete(User $user, Order $order): bool
    {
        return false; // Les commandes ne peuvent pas être supprimées
    }
}
```

## Export de données

```php
// Ajouter un export Excel

use App\Exports\OrdersExport;

public static function table(Table $table): Table
{
    return $table
        ->headerActions([
            ExportAction::make()
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('commandes-' . date('Y-m-d')),
                ]),
        ]);
}
```

## Notifications

Les admins reçoivent des notifications Filament pour:
- Nouvelles commandes
- Demandes de retrait
- Signalements utilisateurs

```php
// Envoyer une notification admin

Notification::make()
    ->title('Nouvelle demande de retrait')
    ->body("Le coursier {$courier->name} demande {$amount} FCFA")
    ->actions([
        Action::make('approve')
            ->label('Approuver')
            ->url(route('filament.admin.resources.withdrawals.edit', $withdrawal)),
    ])
    ->sendToDatabase(User::admin()->get());
```
