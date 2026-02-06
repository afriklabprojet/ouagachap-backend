# OUAGA CHAP - API Backend

API Laravel pour l'application de livraison OUAGA CHAP (Ouagadougou, Burkina Faso).

## 🚀 Stack Technique

- **Framework:** Laravel 11
- **Admin Panel:** Filament 3
- **Authentication:** Laravel Sanctum
- **Roles & Permissions:** Spatie Laravel Permission
- **Real-time:** Laravel Reverb (WebSockets)
- **Database:** SQLite (dev) / MySQL (prod)
- **Queue:** Database (dev) / Redis (prod)

## 📋 Prérequis

- PHP 8.2+
- Composer 2.x
- SQLite / MySQL 8.0+
- Node.js 18+ (pour Reverb)

## ⚡ Installation

```bash
# Cloner le repo
cd api

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Créer la base de données SQLite
touch database/database.sqlite

# Exécuter les migrations
php artisan migrate

# Seeder les données de base
php artisan db:seed

# Lancer le serveur
php artisan serve
```

## 🔐 Authentification

L'API utilise **Laravel Sanctum** avec authentification par OTP SMS.

### Flux d'authentification

```
1. POST /api/v1/auth/otp/send    → Envoie OTP au téléphone
2. POST /api/v1/auth/otp/verify  → Vérifie OTP, retourne token
3. Utiliser le token: Authorization: Bearer {token}
```

### Exemple

```bash
# Envoyer OTP
curl -X POST http://127.0.0.1:8000/api/v1/auth/otp/send \
  -H "Content-Type: application/json" \
  -d '{"phone": "70123456"}'

# Vérifier OTP
curl -X POST http://127.0.0.1:8000/api/v1/auth/otp/verify \
  -H "Content-Type: application/json" \
  -d '{"phone": "70123456", "code": "123456"}'
```

### Mode Développement

En mode `local`, le code OTP `123456` est toujours accepté.

## 🛣️ Routes API

### Routes Publiques

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/otp/send` | Envoyer OTP |
| POST | `/api/v1/auth/otp/verify` | Vérifier OTP |
| GET | `/api/v1/config/general` | Configuration générale |
| GET | `/api/v1/zones` | Liste des zones |
| GET | `/api/v1/support/contact` | Informations de contact |
| GET | `/api/v1/support/faqs` | FAQs |
| POST | `/api/v1/track-order` | Suivre une commande (public) |

### Routes Client (Authentifié + role.client)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/auth/me` | Profil utilisateur |
| PUT | `/api/v1/auth/profile` | Mettre à jour profil |
| POST | `/api/v1/orders/estimate` | Estimer le prix |
| POST | `/api/v1/orders` | Créer une commande |
| GET | `/api/v1/orders` | Mes commandes |
| GET | `/api/v1/orders/{id}` | Détails commande |
| POST | `/api/v1/orders/{id}/cancel` | Annuler commande |
| POST | `/api/v1/orders/{id}/rate-courier` | Noter le coursier |
| POST | `/api/v1/promo-codes/validate` | Valider code promo |
| GET | `/api/v1/client-wallet/balance` | Solde wallet |
| POST | `/api/v1/jeko/recharge` | Recharger wallet |
| POST | `/api/v1/jeko/pay-order` | Payer commande |

### Routes Coursier (Authentifié + role.courier)

| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/api/v1/courier/location` | Mettre à jour position |
| PUT | `/api/v1/courier/availability` | Changer disponibilité |
| GET | `/api/v1/courier/available-orders` | Commandes disponibles |
| POST | `/api/v1/courier/orders/{id}/accept` | Accepter commande |
| PUT | `/api/v1/courier/orders/{id}/status` | Changer statut |
| GET | `/api/v1/wallet` | Mon wallet |
| POST | `/api/v1/wallet/withdraw` | Demander retrait |

### Routes Admin (Authentifié + role.admin)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/admin/login` | Connexion admin |
| GET | `/api/v1/admin/dashboard` | Dashboard stats |
| GET | `/api/v1/exports/orders/csv` | Export commandes |

## 📊 Statuts des Commandes

```
pending → assigned → picked_up → delivered
    ↓         ↓          ↓
cancelled  cancelled  cancelled
```

| Statut | Description |
|--------|-------------|
| `pending` | En attente de coursier |
| `assigned` | Coursier assigné |
| `picked_up` | Colis récupéré |
| `delivered` | Livré |
| `cancelled` | Annulé |

## 🔔 Events & WebSockets

L'API broadcast des events en temps réel via Reverb:

| Event | Channel | Description |
|-------|---------|-------------|
| `OrderCreated` | `orders.{orderId}` | Nouvelle commande |
| `OrderStatusChanged` | `orders.{orderId}` | Statut modifié |
| `CourierLocationUpdated` | `orders.{orderId}` | Position coursier |
| `NewOrderAvailable` | `couriers.zone.{zoneId}` | Commande dispo |
| `CourierWentOnline` | `admin-notifications` | Coursier en ligne |

### Configuration WebSocket

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-secret
REVERB_HOST=localhost
REVERB_PORT=8080
```

## 💳 Paiements

### Mobile Money (JEKO)

```bash
# Initier un paiement
POST /api/v1/jeko/pay-order
{
  "order_id": "uuid",
  "payment_method": "orange_money_bf"
}

# Méthodes disponibles
- orange_money_bf
- moov_money_bf
- coris_money_bf
```

### Webhook

Le webhook JEKO est à `POST /api/v1/jeko/webhook`.  
Configure l'URL dans le dashboard JEKO.

## 👥 Rôles & Permissions

| Rôle | Permissions |
|------|-------------|
| `super_admin` | Toutes (57 permissions) |
| `support` | 21 permissions (users, orders, complaints) |
| `operations` | 18 permissions (orders, couriers, zones) |
| `finance` | 14 permissions (payments, withdrawals, reports) |
| `marketing` | 12 permissions (promos, banners, notifications) |
| `viewer` | 12 permissions (lecture seule) |

## 🛡️ Rate Limiting

| Endpoint | Limite |
|----------|--------|
| API général | 60/min |
| Auth | 5/min |
| OTP | 10/min |
| Orders | 10/min |
| Payments | 5/min |
| Location | 120/min |

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage

# Test spécifique
php artisan test --filter=AuthControllerTest
```

## 📁 Structure

```
app/
├── Console/Commands/     # Commandes Artisan
├── Enums/               # Enums PHP 8.1
├── Events/              # Events broadcast
├── Filament/            # Admin panel
├── Http/
│   ├── Controllers/Api/V1/
│   ├── Middleware/
│   └── Requests/
├── Jobs/                # Jobs queue
├── Listeners/           # Event listeners
├── Models/              # Eloquent models
├── Policies/            # Authorization
├── Services/            # Business logic
└── Traits/              # Traits réutilisables
```

## 🚀 Déploiement

### Production Checklist

```bash
# Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Variables d'environnement
APP_ENV=production
APP_DEBUG=false
CORS_ALLOWED_ORIGINS=https://votre-domaine.com
SANCTUM_TOKEN_EXPIRATION=43200
```

### Redis (Recommandé)

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

## 📞 Support

- **Email:** support@ouagachap.com
- **WhatsApp:** +226 XX XX XX XX

## 📄 License

Proprietary - OUAGA CHAP © 2025
