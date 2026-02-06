# Dépannage (Troubleshooting)

## Problèmes courants Backend

### 1. Erreur 500 - Internal Server Error

**Symptômes:** L'API retourne une erreur 500 sans détails.

**Solutions:**

```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Activer le debug temporairement
php artisan config:clear
# Éditer .env: APP_DEBUG=true
```

**Causes fréquentes:**
- Permissions incorrectes sur `storage/`
- Configuration manquante dans `.env`
- Erreur de syntaxe PHP

### 2. Token invalide / 401 Unauthorized

**Symptômes:** Toutes les requêtes authentifiées échouent.

**Solutions:**

```bash
# Vérifier que le token est bien envoyé
# Header: Authorization: Bearer {token}

# Vérifier que Sanctum est configuré
php artisan config:clear

# Vérifier la date d'expiration du token
php artisan tinker
>>> \App\Models\PersonalAccessToken::find(1);
```

**Causes fréquentes:**
- Token expiré
- Header `Authorization` mal formaté
- `APP_URL` incorrect dans `.env`

### 3. OTP ne fonctionne pas

**Symptômes:** L'OTP n'est pas envoyé ou la vérification échoue.

**En développement:**
```bash
# L'OTP est toujours 123456 en mode local
# Vérifier APP_ENV=local dans .env
```

**En production:**
```bash
# Vérifier les credentials Twilio
php artisan tinker
>>> config('sms.twilio');

# Tester manuellement
>>> app(\App\Services\SmsService::class)->send('+22670123456', 'Test');
```

**Causes fréquentes:**
- Credentials Twilio incorrects
- Numéro de téléphone mal formaté (doit être E.164: +22670123456)
- Solde Twilio insuffisant

### 4. Commandes en file d'attente non traitées

**Symptômes:** Les notifications ne sont pas envoyées, les jobs restent pending.

**Solutions:**

```bash
# Vérifier que le worker tourne
ps aux | grep queue:work

# Démarrer manuellement
php artisan queue:work

# Vérifier Redis
redis-cli ping

# Voir les jobs en attente
php artisan queue:failed
php artisan queue:retry all
```

**Causes fréquentes:**
- Worker non démarré
- Redis non accessible
- Erreur dans le job (voir `failed_jobs`)

### 5. Migration échoue

**Symptômes:** `php artisan migrate` retourne une erreur.

**Solutions:**

```bash
# Reset et recommencer (ATTENTION: perte de données!)
php artisan migrate:fresh --seed

# Vérifier la connexion DB
php artisan tinker
>>> DB::connection()->getPdo();

# Problème de clé étrangère
php artisan migrate --pretend  # Voir le SQL généré
```

**Causes fréquentes:**
- Base de données non créée
- Credentials incorrects
- Conflit de clés étrangères

### 6. Filament admin inaccessible

**Symptômes:** Page blanche ou erreur 404 sur `/admin`.

**Solutions:**

```bash
# Clear tout le cache
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan filament:clear-cache

# Republier les assets
php artisan filament:assets

# Vérifier que l'utilisateur est admin
php artisan tinker
>>> \App\Models\User::where('role', 'admin')->first();
```

## Problèmes courants Flutter

### 1. Erreur de connexion à l'API

**Symptômes:** `SocketException` ou `Connection refused`.

**Solutions:**

```dart
// Vérifier l'URL de l'API dans lib/core/constants/api_constants.dart
static const String baseUrl = 'http://10.0.2.2:8000/api/v1'; // Émulateur Android
static const String baseUrl = 'http://localhost:8000/api/v1'; // iOS Simulator
```

**Pour appareil physique:**
```dart
// Utiliser l'IP de votre machine
static const String baseUrl = 'http://192.168.1.X:8000/api/v1';
```

```bash
# Vérifier que l'API est accessible
curl http://localhost:8000/api/v1/health
```

### 2. Build Android échoue

**Symptômes:** Erreur Gradle ou SDK manquant.

**Solutions:**

```bash
# Nettoyer le build
cd android
./gradlew clean
cd ..
flutter clean
flutter pub get

# Mettre à jour Gradle
# android/gradle/wrapper/gradle-wrapper.properties
distributionUrl=https\://services.gradle.org/distributions/gradle-8.0-all.zip
```

**Vérifier les versions:**
```bash
flutter doctor -v
```

### 3. Build iOS échoue

**Symptômes:** Erreur CocoaPods ou signing.

**Solutions:**

```bash
cd ios
pod deintegrate
pod install --repo-update
cd ..
flutter clean
flutter pub get
```

**Problème de signing:**
1. Ouvrir `ios/Runner.xcworkspace` dans Xcode
2. Sélectionner Runner → Signing & Capabilities
3. Choisir votre équipe de développement

### 4. Firebase non initialisé

**Symptômes:** `FirebaseException` au démarrage.

**Solutions:**

```bash
# Vérifier que les fichiers sont en place
# Android: android/app/google-services.json
# iOS: ios/Runner/GoogleService-Info.plist

# Régénérer si nécessaire depuis Firebase Console
```

```dart
// Vérifier l'initialisation dans main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();  // Cette ligne est cruciale
  runApp(MyApp());
}
```

### 5. État BLoC incorrect

**Symptômes:** L'interface ne se met pas à jour.

**Solutions:**

```dart
// Vérifier que vous utilisez BlocBuilder ou BlocListener
BlocBuilder<OrderBloc, OrderState>(
  builder: (context, state) {
    if (state is OrderLoaded) {
      return OrderList(orders: state.orders);
    }
    return LoadingWidget();
  },
)

// Éviter de créer des BLoC multiples pour la même feature
// Utiliser BlocProvider.of<OrderBloc>(context) au lieu de créer un nouveau
```

### 6. Notifications push non reçues

**Symptômes:** Les notifications n'arrivent pas sur l'appareil.

**Solutions:**

```dart
// Vérifier que le token FCM est enregistré
final token = await FirebaseMessaging.instance.getToken();
print('FCM Token: $token');

// S'assurer que le token est envoyé à l'API
await apiService.updateFcmToken(token);
```

**Android:**
- Vérifier que l'app a la permission de notification
- Vérifier que l'app n'est pas en mode économie batterie

**iOS:**
- Vérifier les capabilities (Push Notifications activé)
- Tester sur appareil réel (simulateur ne supporte pas push)

## Commandes de diagnostic utiles

### Backend

```bash
# Vérifier la santé générale
php artisan about

# Voir toutes les routes
php artisan route:list

# Tester la connexion DB
php artisan db:show

# Voir les jobs en attente
php artisan queue:monitor redis:default

# Vérifier le cache
php artisan cache:clear
redis-cli KEYS "*"
```

### Flutter

```bash
# Diagnostic complet
flutter doctor -v

# Nettoyer tout
flutter clean && flutter pub get

# Voir les dépendances
flutter pub deps

# Analyser le code
flutter analyze
```

## Logs et debugging

### Laravel

```php
// Ajouter des logs personnalisés
Log::info('Commande créée', ['order_id' => $order->id]);
Log::error('Paiement échoué', ['error' => $e->getMessage()]);

// Dans le code, utiliser dump/dd
dd($variable);  // Dump and die
dump($variable);  // Dump without dying
```

### Flutter

```dart
// Debug print
debugPrint('État actuel: $state');

// Logger avec niveau
import 'package:logger/logger.dart';
final logger = Logger();
logger.d('Debug message');
logger.e('Error message');
```

## Support

Si le problème persiste:

1. Vérifier les logs complets
2. Reproduire le problème de manière isolée
3. Consulter la documentation du framework
4. Ouvrir une issue sur GitHub avec les détails du problème
