# Architecture du Projet

## Vue d'ensemble

OUAGA CHAP utilise une architecture en couches (Layered Architecture) avec le pattern Service pour séparer la logique métier des controllers.

```
┌─────────────────────────────────────────────────────────────┐
│                    Applications Mobiles                      │
│              (Client Flutter / Coursier Flutter)             │
└─────────────────────────┬───────────────────────────────────┘
                          │ REST API (JSON)
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                      API Laravel 11                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │ Controllers │──│  Services   │──│     Repositories    │  │
│  │   (légers)  │  │  (lourds)   │  │  (data access)      │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
│         │               │                    │              │
│         ▼               ▼                    ▼              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  Requests   │  │   Events    │  │      Models         │  │
│  │ (validation)│  │ (async)     │  │   (Eloquent ORM)    │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    Base de données MySQL                     │
└─────────────────────────────────────────────────────────────┘
```

## Structure des dossiers

```
api/
├── app/
│   ├── Console/           # Commandes Artisan personnalisées
│   ├── DTOs/              # Data Transfer Objects
│   ├── Enums/             # Énumérations (UserRole, OrderStatus, etc.)
│   ├── Events/            # Événements pour actions asynchrones
│   ├── Exports/           # Classes d'export (Excel, PDF)
│   ├── Filament/          # Panneau d'administration
│   │   ├── Pages/         # Pages personnalisées
│   │   ├── Resources/     # CRUD pour chaque modèle
│   │   └── Widgets/       # Widgets du dashboard
│   ├── Http/
│   │   ├── Controllers/   # Controllers API
│   │   ├── Middleware/    # Middlewares personnalisés
│   │   ├── Requests/      # Form Requests (validation)
│   │   └── Resources/     # API Resources (transformation JSON)
│   ├── Jobs/              # Jobs pour les queues
│   ├── Listeners/         # Listeners d'événements
│   ├── Models/            # Modèles Eloquent
│   ├── Policies/          # Politiques d'autorisation
│   ├── Repositories/      # Pattern Repository
│   ├── Services/          # Logique métier
│   └── Traits/            # Traits réutilisables
├── config/                # Configuration
├── database/
│   ├── factories/         # Factories pour les tests
│   ├── migrations/        # Migrations de la BDD
│   └── seeders/           # Seeders de données
├── resources/
│   └── views/             # Vues Blade (landing, emails)
├── routes/
│   ├── api.php            # Routes API
│   ├── web.php            # Routes web
│   └── channels.php       # Canaux WebSocket
└── tests/                 # Tests PHPUnit
```

## Couche Controller

Les controllers sont **légers** et ne contiennent que:
- La validation via Form Requests
- L'appel au Service approprié
- La transformation de la réponse

```php
// Exemple: OrderController
public function store(CreateOrderRequest $request)
{
    $order = $this->orderService->createOrder($request->validated());
    
    return $this->success(
        new OrderResource($order),
        'Commande créée avec succès',
        201
    );
}
```

## Couche Service

Les services contiennent toute la **logique métier**:

```php
// Services principaux
app/Services/
├── AuthService.php        # Authentification, OTP
├── OrderService.php       # Création, cycle de vie commandes
├── CourierService.php     # Gestion des coursiers
├── PaymentService.php     # Paiements Mobile Money
├── NotificationService.php # SMS et Push
├── WalletService.php      # Portefeuilles et transactions
└── CacheService.php       # Gestion du cache
```

## Events & Listeners

Le système est **event-driven** pour les actions asynchrones:

```php
// Événements
OrderCreated::class      → NotifyNearbyCouriers, LogActivity
OrderStatusChanged::class → NotifyClient, UpdateStats
CourierLocationUpdated::class → BroadcastToClient
PaymentCompleted::class  → CreditCourierWallet, SendReceipt
```

## Enums

Les statuts sont gérés par des Enums PHP 8.1+:

```php
// app/Enums/
UserRole::class       // customer, courier, admin
UserStatus::class     // pending, active, suspended
OrderStatus::class    // pending, accepted, picked_up, delivered, cancelled
PaymentStatus::class  // pending, processing, completed, failed
PaymentMethod::class  // orange_money, moov_money, cash
```

## Middlewares

```php
// Middlewares personnalisés
'role.client'  → EnsureIsClient::class   // Vérifie rôle client
'role.courier' → EnsureIsCourier::class  // Vérifie rôle coursier
'role.admin'   → EnsureIsAdmin::class    // Vérifie rôle admin
```

## Format des réponses API

Toutes les réponses suivent ce format:

```json
{
    "success": true,
    "message": "Description de l'action",
    "data": { ... }
}
```

Erreurs:
```json
{
    "success": false,
    "message": "Description de l'erreur",
    "errors": { ... }  // Optionnel pour validation
}
```

## Cache Strategy

Le cache est utilisé pour:
- Configuration du site (SiteSetting)
- Zones de livraison
- FAQs
- Tarifs

```php
// Exemple
$settings = Cache::remember('site_settings', 3600, function () {
    return SiteSetting::all()->pluck('value', 'key');
});
```
