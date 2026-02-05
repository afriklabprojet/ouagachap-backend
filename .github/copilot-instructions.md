# OUAGA CHAP - Instructions pour Agents IA

> Plateforme de livraison urbaine pour Ouagadougou, Burkina Faso | MVP en production

## Architecture du Projet

**Structure monorepo** avec 3 applications principales :
- `api/` - Backend Laravel 11 + panneau admin Filament 3
- `client/` - Application Flutter client (commander des livraisons)
- `coursier/` - Application Flutter coursier (effectuer des livraisons)

**Schéma de communication** : Applications mobiles → API REST (auth Laravel Sanctum) → Base de données MySQL

## Contexte Technique Essentiel

### Authentification & Autorisation
- **Pas de mots de passe** : Téléphone + OTP uniquement (via SMS en production, code `123456` en local)
- **3 rôles** : `customer`, `courier`, `admin` (enum dans `app/Enums/UserRole.php`)
- **Flux de token** : `POST /api/v1/auth/otp/send` → `POST /api/v1/auth/otp/verify` → Bearer token
- **Mode développement** : L'OTP `123456` fonctionne toujours quand `APP_ENV=local`
- **Middlewares** : `role.client`, `role.courier`, `role.admin` imposent la séparation des rôles
- Les coursiers ont un champ `status` supplémentaire : doit être `active` pour travailler

**Séparation des applications** :
- Le paramètre `app_type` dans `/otp/verify` valide que l'utilisateur utilise la bonne application
- Un client ne peut **pas** se connecter à l'app coursier (erreur 403)
- Un coursier ne peut **pas** se connecter à l'app client (erreur 403)
- Un admin ne peut **pas** se connecter aux apps mobiles
- L'app client envoie `app_type: 'client'`, l'app coursier envoie `app_type: 'courier'`

### Patterns Backend (Laravel 11)

**Architecture à couche Service** - La logique métier est dans les Services, pas les Controllers :
```
Controllers (légers) → Services (lourds) → Models (données) + Events (notifications)
```

**Services clés** dans `app/Services/` :
- `OrderService` - Calcul de distance (Haversine), tarification, cycle de vie des commandes
- `AuthService` - Génération/vérification OTP, normalisation téléphone, validation rôle/app
- `PaymentService` - Intégration Mobile Money (mock)
- `NotificationService` - SMS (Twilio) + Push (Firebase FCM)
- `CacheService` - Zones, FAQs, config en cache (Redis)

**Mises à jour event-driven** : Utiliser les Events (`app/Events/`) pour actions asynchrones :
- `OrderCreated` → notifier les coursiers, logger l'activité
- `OrderStatusChanged` → mettre à jour client, déclencher paiements
- `CourierLocationUpdated` → diffuser aux clients de suivi

**Conventions base de données** :
- Numéros de téléphone au format E.164 (`+22670123456`)
- UUIDs pour les IDs publics (commandes)
- Enums pour les statuts (OrderStatus, PaymentStatus, UserRole, etc.)
- Pas de soft deletes (suppression dure préférée)

**Versioning API** : Toutes les routes sous préfixe `/api/v1/`

### Applications Mobiles (Flutter 3.x)

**Architecture Clean** avec gestion d'état BLoC :
```
lib/
├── features/          # Modules par fonctionnalité (auth, orders, profile, etc.)
│   └── [feature]/
│       ├── data/      # Modèles API, sources de données, implémentations repository
│       ├── domain/    # Entités, interfaces repository, cas d'usage
│       └── presentation/ # BLoC, pages, widgets
└── core/              # Utilitaires partagés, constantes, client réseau
```

**Injection de dépendances** : GetIt + pattern Injectable (`injection_container.dart`)

**Client API** : Dio avec intercepteurs pour tokens auth, gestion erreurs, réessais

**Pattern d'état** : BLoC pour toutes les fonctionnalités - les événements déclenchent des changements d'état, l'UI se reconstruit sur l'état

**Packages partagés** : Les deux apps (`client/`, `coursier/`) utilisent la même stack technique mais des fonctionnalités différentes

### Système de Paiement (MVP)

**Mock Mobile Money** pour le développement :
- Fournisseurs réels : Orange Money, Moov Money (Burkina Faso)
- Implémentation mock dans `PaymentService` - simule le flux USSD
- Vérification PIN : Accepte n'importe quel PIN à 4 chiffres en mode mock
- Flux de statut : `pending` → `processing` → `completed` / `failed`

**Point d'intégration** : Passerelle de paiement Jeko (documentée dans `06_PAYMENT/jeko_integration.md`)

### Temps Réel & Notifications

**Suivi de localisation** : Les coursiers envoient lat/lng à `/api/v1/courier/location/update` toutes les 10 secondes
- Stocké dans `users.current_latitude`, `users.current_longitude`
- Diffusé via Laravel Reverb (WebSockets) aux clients de suivi

**Notifications push** : Firebase FCM via `kreait/laravel-firebase`
- Tokens stockés dans `users.fcm_token`
- Envoyées via `PushNotificationService` pour mises à jour commandes

**Notifications SMS** : Twilio pour OTP et alertes commandes (configurable dans `config/sms.php`)

## Système de Feedback & Notation

### Vue d'Ensemble

Système de notation bidirectionnelle permettant clients et coursiers de s'évaluer mutuellement. Les notes influencent directement l'algorithme de matching et la réputation des utilisateurs.

### Fonctionnalités de Notation

**Client note le coursier** :
- Endpoint : `POST /api/v1/orders/{order}/rate-courier`
- Note : 1-5 étoiles (obligatoire, validé par `RateOrderRequest`)
- Commentaire : Texte libre max 500 caractères (optionnel)
- Tags prédéfinis positifs : `rapide`, `professionnel`, `aimable`, `ponctuel`, `soigneux`, `communicatif`
- Tags négatifs : `lent`, `impoli`, `retard`, `colis_abime`, `difficile_joindre`

**Coursier note le client** :
- Endpoint : `POST /api/v1/orders/{order}/rate-client`
- Mêmes règles (1-5 étoiles + commentaire)
- Permet d'identifier clients problématiques

### Implémentation Technique

**Flow Backend complet** :
```php
// 1. Validation dans OrderController
if (!$order->isCompleted()) {
    return $this->error('La commande doit être livrée pour être notée.');
}
if ($order->courier_rating) {
    return $this->error('Vous avez déjà noté ce coursier.');
}

// 2. Enregistrement via Order Model
$order->rateCourier($request->rating, $request->review);

// 3. Calcul automatique dans Order::rateCourier()
$this->update([
    'courier_rating' => $rating,
    'courier_review' => $review,
]);
$this->courier->updateRating($rating); // Mise à jour moyenne

// 4. Recalcul moyenne dans User::updateRating()
$totalRatings = $this->total_ratings + 1;
$averageRating = (($this->average_rating * $this->total_ratings) + $newRating) / $totalRatings;
$this->update([
    'average_rating' => round($averageRating, 2),
    'total_ratings' => $totalRatings,
]);
```

**Structure données** :
```php
// Table orders (simplifié)
'client_rating' => integer     // Note donnée par le client
'client_review' => text        // Commentaire du client
'courier_rating' => integer    // Note donnée par le coursier
'courier_review' => text       // Commentaire du coursier

// Table users (calculs automatiques)
'average_rating' => decimal(2,1)  // Moyenne des notes reçues
'total_ratings' => integer         // Nombre total de notes
```

**Table `ratings` (système avancé)** :
```php
- order_id (FK vers orders)
- rater_id (utilisateur qui note)
- rated_id (utilisateur noté)
- type (client_to_courier | courier_to_client)
- rating (1-5)
- comment (texte libre)
- tags (JSON array) // Tags prédéfinis
- is_visible (bool) // Modération admin
```

### Règles Métier & Contraintes

**Validation stricte** :
```php
// RateOrderRequest
'rating' => ['required', 'integer', 'between:1,5'],
'review' => ['sometimes', 'string', 'max:500'],
```

**Contraintes de notation** :
- ✅ Peut noter **uniquement après** statut `delivered` (`$order->isCompleted()`)
- ✅ **Une seule notation** par commande (vérification via `if ($order->courier_rating)`)
- ✅ Client et coursier notent **indépendamment** (deux champs distincts)
- ❌ **Impossible** de noter si commande `cancelled`
- ❌ **Pas de modification** après notation (immutable)

**Visibilité et modération** :
- Toutes les notes accessibles dans panneau admin Filament
- Flag `is_visible` permet masquage si contenu inapproprié
- Méthode `Rating::statsForUser()` génère statistiques complètes
- Notes anonymes pour coursiers (ne voient pas qui les a notés)

### Impact sur l'Algorithme de Matching

**Priorisation par réputation** :
```php
// Dans OrderService::findNearestAvailableCouriers()
$couriers = User::query()
    ->where('role', UserRole::COURIER)
    ->where('status', UserStatus::ACTIVE)
    ->where('is_available', true)
    ->where('average_rating', '>=', 3.0) // Seuil qualité minimum
    ->orderBy('average_rating', 'desc')  // Meilleurs coursiers d'abord
    ->orderBy('distance')                // Puis par proximité
    ->get();
```

**Logique métier** :
- Coursiers avec `average_rating < 3.0` exclus du matching automatique
- Tri prioritaire par note (meilleurs coursiers privilégiés)
- Balance entre qualité de service et proximité géographique

### Statistiques & Analytics

**Méthodes utiles du Model Rating** :
```php
// Moyenne pour un utilisateur
Rating::averageForUser($userId, $type); // Returns float|null

// Statistiques complètes
Rating::statsForUser($userId, $type); 
// Returns: ['average' => 4.5, 'count' => 23, 
//           'distribution' => [5=>10, 4=>8, 3=>3, 2=>1, 1=>1],
//           'tags' => ['rapide' => 15, 'ponctuel' => 12]]

// Scopes disponibles
Rating::forCourier()->visible()->get();  // Notes de coursiers visibles
Rating::forClient()->get();              // Notes de clients
```

### Intégration Mobile (Flutter)

**Flow UI recommandé** :
```dart
// 1. Détection livraison complétée
if (order.status == OrderStatus.delivered && !order.hasRatedCourier) {
  showRatingDialog();
}

// 2. Modal de notation
RatingDialog(
  onSubmit: (rating, comment, tags) async {
    await orderRepository.rateCourier(
      orderId: order.id,
      rating: rating,        // 1-5
      review: comment,       // Optional
      tags: tags,           // ['rapide', 'aimable']
    );
  }
)

// 3. Request API
POST /api/v1/orders/{orderId}/rate-courier
{
  "rating": 5,
  "review": "Excellent service, très rapide !",
  "tags": ["rapide", "professionnel"]
}
```

### Cas d'Usage pour Nouvelles Fonctionnalités

**Ajouter un dashboard de réputation coursier** :
```php
// Controller
$stats = Rating::statsForUser($courier->id, Rating::TYPE_CLIENT_TO_COURIER);
return view('courier.reputation', compact('stats'));
```

**Filtrer coursiers par qualité** :
```php
// Service
$topCouriers = User::courier()
    ->where('average_rating', '>=', 4.5)
    ->where('total_ratings', '>=', 10) // Minimum expérience
    ->orderBy('average_rating', 'desc')
    ->limit(10)
    ->get();
```

**Alertes admin pour notes basses** :
```php
// Job/Command
$badRatings = Rating::where('rating', '<=', 2)
    ->where('created_at', '>=', now()->subDay())
    ->with(['order', 'rated'])
    ->get();
// → Envoyer notification admin
```

### Fichiers Clés

**Backend** :
- `app/Models/Rating.php` - Model avec constantes tags et méthodes stats
- `app/Models/Order.php` - Méthodes `rateCourier()`, `rateClient()`
- `app/Models/User.php` - Méthode `updateRating()` pour calcul moyenne
- `app/Http/Requests/Order/RateOrderRequest.php` - Validation
- `app/Http/Controllers/Api/V1/OrderController.php` - Endpoints `rateCourier()`, `rateClient()`

**Constants importantes** :
- `Rating::POSITIVE_TAGS` - Liste complète tags positifs
- `Rating::NEGATIVE_TAGS` - Liste complète tags négatifs
- `Rating::TYPE_CLIENT_TO_COURIER` - Constante type
- `Rating::TYPE_COURIER_TO_CLIENT` - Constante type

### Points d'Attention

⚠️ **Ne pas** permettre modification d'une note existante (vérifier `if ($order->courier_rating)`)  
⚠️ **Toujours vérifier** statut `delivered` avant notation  
⚠️ **Utiliser** les constantes de tags au lieu de strings libres  
⚠️ **Arrondir** la moyenne à 2 décimales (`round($avg, 2)`)  
⚠️ **Penser** à l'impact sur matching lors changements algorithme  
⚠️ **Tester** le recalcul de moyenne avec edge cases (première note, note unique)

## Workflows de Développement

### Configuration Backend
```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite  # Ou configurer MySQL
php artisan migrate --seed
php artisan serve  # http://127.0.0.1:8000
```

**Panneau admin** : Accès Filament sur `/admin` (défaut: admin@ouagachap.com / mot de passe du seeder)

**Traitement des queues** : `php artisan queue:work` (requis pour SMS/notifications)

**WebSockets** : `php artisan reverb:start` (pour le suivi en temps réel)

### Configuration Flutter
```bash
cd client  # ou coursier
flutter pub get
flutter run
```

**Point d'API** : Défini dans `lib/core/constants/api_constants.dart` (défaut: localhost:8000)

**Tests** : Rapports de couverture dans `coverage_report/` (client a couverture de tests)

### Stratégie de Tests

**Backend** : Tests PHPUnit existants (audit mentionne 67 tests)
- Exécuter : `php artisan test`
- Couverture : Tests de fonctionnalités pour endpoints API, tests unitaires pour Services

**Frontend** : Tests Flutter dans répertoires `test/`
- Exécuter : `flutter test`
- Focus : Logique BLoC, cas d'usage, implémentations repository

## Conventions Spécifiques au Projet

### Style de Code
- **Laravel** : Laravel Pint pour formatage (`composer pint`)
- **Flutter** : Formatage Dart standard (`flutter format .`)
- **Nommage** : Snake_case pour base de données/API, camelCase pour Dart, PascalCase pour classes

### Migrations de Base de Données
- **Ne jamais modifier** les migrations existantes en production
- **Toujours créer nouvelle** migration pour changements de schéma
- Les clés étrangères utilisent `ON DELETE CASCADE` pour données liées aux commandes

### Gestion des Erreurs
- **Réponses API** : Toujours retourner format `{success: bool, message: string, data: object}`
- **Codes HTTP** : 200 (succès), 401 (non autorisé), 422 (validation), 500 (erreur serveur)
- **Flutter** : Utiliser classes `Failure` (dans `core/errors/`) pour propagation d'erreurs

### Variables d'Environnement
- **Critiques** : `TWILIO_*` (SMS), `FIREBASE_*` (push), `GOOGLE_MAPS_API_KEY`
- **Tests** : Définir `APP_ENV=local` pour bypass OTP, `DB_CONNECTION=sqlite` pour tests rapides

## Points d'Intégration Critiques

### Cycle de Vie d'une Commande
1. Client crée commande → `OrderService::createOrder()`
2. Statut commande : `pending` → Système trouve coursiers à proximité
3. Coursier accepte → `OrderStatus::ACCEPTED`, envoie notification
4. Coursier récupère → `OrderStatus::PICKED_UP`, démarre suivi
5. Coursier livre → `OrderStatus::DELIVERED`, déclenche paiement
6. Paiement complété → Portefeuille coursier crédité, commande terminée

**Fichiers clés** :
- `app/Services/OrderService.php` (logique métier)
- `app/Http/Controllers/Api/V1/OrderController.php` (endpoints API)
- `database/migrations/*_create_orders_table.php` (schéma)

### Algorithme de Matching des Coursiers
Situé dans `OrderService::findNearestAvailableCouriers()` :
- Recherche coursiers dans rayon (commence à 5km, s'étend à 20km)
- Filtres : `role=courier`, `status=active`, `is_available=true`
- Utilise formule Haversine pour calcul de distance
- Diffuse événement `NewOrderAvailable` aux coursiers correspondants

## Références Documentation

Pour implémenter des fonctionnalités, consulter :
- `05_BACKEND_LARAVEL/api_routes.md` - Spécification API complète
- `07_FLUTTER/architecture.md` - Détails architecture Flutter
- `04_DATABASE/schema.sql` - Schéma complet base de données
- `01_PRODUCT/prd.md` - Exigences métier et flux utilisateurs

## Pièges Courants à Éviter

- **Ne pas** mettre logique métier dans Controllers - utiliser Services
- **Ne pas** faire queries dans boucles - eager load relations (`with()`)
- **Ne pas** oublier de dispatcher Events pour changements statut commandes
- **Ne pas** exposer IDs internes - utiliser UUIDs pour commandes, IDs users OK en interne
- **Ne pas** commit `.env` ou `storage/firebase-credentials.json`
- **Se rappeler** : OTP local est toujours `123456`, prod utilise Twilio SMS réels
