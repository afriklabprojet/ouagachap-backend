# Applications Flutter

## Vue d'ensemble

OUAGA CHAP comprend deux applications mobiles Flutter:

| App | Description | Repository |
|-----|-------------|------------|
| **Client** | Commander des livraisons | [ouagachap-client](https://github.com/afriklabprojet/ouagachap-client) |
| **Coursier** | Effectuer des livraisons | [ouagachap-coursier](https://github.com/afriklabprojet/ouagachap-coursier) |

## Architecture commune

Les deux applications suivent la **Clean Architecture** avec gestion d'état **BLoC**:

```
lib/
├── core/                    # Utilitaires partagés
│   ├── constants/           # URLs API, couleurs, textes
│   ├── errors/              # Classes d'erreur (Failure)
│   ├── network/             # Client API (Dio)
│   └── utils/               # Helpers, formatters
│
├── features/                # Modules par fonctionnalité
│   └── [feature]/
│       ├── data/            # Couche données
│       │   ├── datasources/ # Sources (API, local)
│       │   ├── models/      # DTOs JSON
│       │   └── repositories/# Implémentation repo
│       │
│       ├── domain/          # Couche métier
│       │   ├── entities/    # Objets métier
│       │   ├── repositories/# Interfaces (contracts)
│       │   └── usecases/    # Cas d'usage
│       │
│       └── presentation/    # Couche UI
│           ├── bloc/        # BLoC + Events + States
│           ├── pages/       # Écrans
│           └── widgets/     # Composants réutilisables
│
└── injection_container.dart # Configuration GetIt/Injectable
```

## Configuration

### 1. Installer les dépendances

```bash
cd client  # ou coursier
flutter pub get
```

### 2. Configurer l'API

```dart
// lib/core/constants/api_constants.dart

class ApiConstants {
  // Développement
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1'; // Émulateur Android
  // static const String baseUrl = 'http://localhost:8000/api/v1'; // iOS Simulator
  
  // Production
  // static const String baseUrl = 'https://api.ouagachap.com/api/v1';
}
```

### 3. Configurer Firebase

**Android:**
1. Télécharger `google-services.json` depuis Firebase Console
2. Placer dans `android/app/google-services.json`

**iOS:**
1. Télécharger `GoogleService-Info.plist`
2. Placer dans `ios/Runner/GoogleService-Info.plist`

### 4. Lancer l'application

```bash
flutter run
```

## Application Client

### Fonctionnalités principales

- **Authentification** : Connexion par téléphone + OTP
- **Nouvelle commande** : Sélection adresses, calcul prix
- **Suivi en temps réel** : Carte avec position du coursier
- **Historique** : Liste des commandes passées
- **Notation** : Évaluer le coursier après livraison
- **Support** : FAQ et contact

### Structure des features

```
features/
├── auth/           # Login, OTP, profil
├── home/           # Écran d'accueil
├── orders/         # Créer/suivre/historique commandes
├── tracking/       # Suivi temps réel
├── support/        # FAQ, contact
└── profile/        # Paramètres utilisateur
```

### Flux de création de commande

```dart
// 1. Sélectionner les adresses
PickupAddressSelected → DeliveryAddressSelected

// 2. Calculer le prix
OrderPriceCalculated(price: 1500, distance: 5.2)

// 3. Confirmer et créer
CreateOrder() → OrderCreated(order)

// 4. Attendre un coursier
OrderStatusChanged(status: 'accepted', courier: {...})

// 5. Suivre la livraison
CourierLocationUpdated(lat, lng)

// 6. Livraison effectuée
OrderDelivered() → ShowRatingDialog()
```

## Application Coursier

### Fonctionnalités principales

- **Authentification** : Login avec validation statut `active`
- **Disponibilité** : Toggle online/offline
- **Nouvelles commandes** : Notifications push, accepter/refuser
- **Navigation** : Itinéraire vers pickup/delivery
- **Statut commande** : Mettre à jour (picked_up, delivered)
- **Portefeuille** : Solde, historique, retrait

### Structure des features

```
features/
├── auth/           # Login coursier
├── home/           # Dashboard, toggle disponibilité
├── orders/         # Commandes en cours et historique
├── delivery/       # Navigation et mise à jour statut
├── wallet/         # Solde et retraits
└── profile/        # Paramètres, véhicule
```

### Flux d'acceptation de commande

```dart
// 1. Recevoir notification push
NewOrderAvailable(orderId, pickupAddress, price)

// 2. Voir les détails
LoadOrderDetails(orderId) → OrderDetailsLoaded(order)

// 3. Accepter la commande
AcceptOrder(orderId) → OrderAccepted()

// 4. Naviguer vers pickup
StartNavigation(destination: order.pickupLocation)

// 5. Récupérer le colis
MarkAsPickedUp(orderId) → OrderPickedUp()

// 6. Naviguer vers delivery
StartNavigation(destination: order.deliveryLocation)

// 7. Confirmer livraison
MarkAsDelivered(orderId) → OrderDelivered()
```

## Packages communs

```yaml
dependencies:
  # État
  flutter_bloc: ^8.1.0
  equatable: ^2.0.5
  
  # Injection de dépendances
  get_it: ^7.6.0
  injectable: ^2.3.0
  
  # Réseau
  dio: ^5.3.0
  connectivity_plus: ^5.0.0
  
  # Stockage local
  shared_preferences: ^2.2.0
  flutter_secure_storage: ^9.0.0
  
  # Firebase
  firebase_core: ^2.24.0
  firebase_messaging: ^14.7.0
  
  # Cartes
  google_maps_flutter: ^2.5.0
  geolocator: ^10.1.0
  
  # UI
  flutter_svg: ^2.0.9
  cached_network_image: ^3.3.0
  shimmer: ^3.0.0
```

## Client API (Dio)

```dart
// lib/core/network/api_client.dart

class ApiClient {
  late Dio _dio;
  
  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: ApiConstants.baseUrl,
      connectTimeout: Duration(seconds: 30),
      receiveTimeout: Duration(seconds: 30),
    ));
    
    _dio.interceptors.add(AuthInterceptor());
    _dio.interceptors.add(LogInterceptor(responseBody: true));
  }
  
  Future<Response> get(String path, {Map<String, dynamic>? params}) {
    return _dio.get(path, queryParameters: params);
  }
  
  Future<Response> post(String path, {dynamic data}) {
    return _dio.post(path, data: data);
  }
}

// Intercepteur d'authentification
class AuthInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _secureStorage.read(key: 'auth_token');
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }
  
  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 401) {
      // Token expiré, rediriger vers login
      // ...
    }
    handler.next(err);
  }
}
```

## Gestion des erreurs

```dart
// lib/core/errors/failures.dart

abstract class Failure {
  final String message;
  Failure(this.message);
}

class ServerFailure extends Failure {
  ServerFailure([String message = 'Erreur serveur']) : super(message);
}

class NetworkFailure extends Failure {
  NetworkFailure() : super('Pas de connexion internet');
}

class AuthFailure extends Failure {
  AuthFailure([String message = 'Non autorisé']) : super(message);
}
```

## Thème et couleurs

```dart
// lib/core/constants/colors.dart

class AppColors {
  static const Color primary = Color(0xFFE31E24);    // Rouge OUAGA CHAP
  static const Color secondary = Color(0xFFF9A825); // Jaune
  static const Color background = Color(0xFFF5F5F5);
  static const Color text = Color(0xFF212121);
  static const Color textLight = Color(0xFF757575);
  static const Color success = Color(0xFF4CAF50);
  static const Color error = Color(0xFFE53935);
}
```

## Build et déploiement

### Android

```bash
# Build APK debug
flutter build apk --debug

# Build APK release
flutter build apk --release

# Build App Bundle (pour Play Store)
flutter build appbundle --release
```

### iOS

```bash
# Build pour simulateur
flutter build ios --simulator

# Build pour release
flutter build ios --release

# Archive pour App Store (depuis Xcode)
```

## Tests

```bash
# Exécuter tous les tests
flutter test

# Tests avec couverture
flutter test --coverage

# Générer rapport HTML
genhtml coverage/lcov.info -o coverage_report
```

## Ressources

- [Documentation Flutter](https://docs.flutter.dev/)
- [BLoC Library](https://bloclibrary.dev/)
- [Clean Architecture Flutter](https://resocoder.com/flutter-clean-architecture-tdd/)
