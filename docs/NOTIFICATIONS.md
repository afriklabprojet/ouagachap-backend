# Notifications Push

## Vue d'ensemble

OUAGA CHAP utilise **Firebase Cloud Messaging (FCM)** pour les notifications push vers les applications mobiles.

## Configuration

### 1. Créer un projet Firebase

1. Aller sur [Firebase Console](https://console.firebase.google.com)
2. Créer un nouveau projet "OUAGA CHAP"
3. Ajouter les applications Android et iOS
4. Télécharger les fichiers de configuration:
   - `google-services.json` (Android)
   - `GoogleService-Info.plist` (iOS)

### 2. Obtenir les credentials serveur

1. Dans Firebase Console → Paramètres du projet → Comptes de service
2. Générer une nouvelle clé privée
3. Sauvegarder le fichier JSON

### 3. Configuration Laravel

```env
# .env
FIREBASE_CREDENTIALS=/path/to/firebase-credentials.json
```

```php
// config/firebase.php
return [
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('firebase-credentials.json')),
    ],
];
```

### 4. Stockage du fichier credentials

```bash
# Copier le fichier dans storage (ne pas commit!)
cp ~/Downloads/firebase-credentials.json storage/firebase-credentials.json

# Ajouter au .gitignore
echo "storage/firebase-credentials.json" >> .gitignore
```

## PushNotificationService

```php
// app/Services/PushNotificationService.php

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class PushNotificationService
{
    protected $messaging;
    
    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }
    
    /**
     * Envoyer une notification à un utilisateur
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->fcm_token) {
            return false;
        }
        
        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);
                
            $this->messaging->send($message);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            // Token invalide, le supprimer
            if (str_contains($e->getMessage(), 'not a valid FCM registration token')) {
                $user->update(['fcm_token' => null]);
            }
            
            return false;
        }
    }
    
    /**
     * Envoyer à plusieurs utilisateurs
     */
    public function sendToMany(array $tokens, string $title, string $body, array $data = []): array
    {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);
            
        return $this->messaging->sendMulticast($message, $tokens);
    }
    
    /**
     * Envoyer à un topic (ex: tous les coursiers)
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);
            
        $this->messaging->send($message);
        
        return true;
    }
}
```

## Types de notifications

### 1. Nouvelle commande disponible (pour coursiers)

```php
// Event: OrderCreated
// Listener: NotifyCouriersOfNewOrder

public function handle(OrderCreated $event)
{
    $order = $event->order;
    
    // Trouver les coursiers à proximité
    $couriers = $this->orderService->findNearestAvailableCouriers(
        $order->pickup_latitude,
        $order->pickup_longitude
    );
    
    foreach ($couriers as $courier) {
        $this->pushService->sendToUser(
            $courier,
            'Nouvelle commande !',
            "Une livraison vers {$order->delivery_address} vous attend",
            [
                'type' => 'new_order',
                'order_id' => $order->uuid,
                'pickup_address' => $order->pickup_address,
                'delivery_address' => $order->delivery_address,
                'price' => (string) $order->price,
            ]
        );
    }
}
```

### 2. Commande acceptée (pour client)

```php
public function handle(OrderAccepted $event)
{
    $order = $event->order;
    
    $this->pushService->sendToUser(
        $order->client,
        'Coursier assigné',
        "{$order->courier->name} a accepté votre commande",
        [
            'type' => 'order_accepted',
            'order_id' => $order->uuid,
            'courier_name' => $order->courier->name,
            'courier_phone' => $order->courier->phone,
        ]
    );
}
```

### 3. Colis récupéré

```php
$this->pushService->sendToUser(
    $order->client,
    'Colis récupéré',
    'Votre colis est en route vers la destination',
    [
        'type' => 'order_picked_up',
        'order_id' => $order->uuid,
    ]
);
```

### 4. Livraison effectuée

```php
$this->pushService->sendToUser(
    $order->client,
    'Livraison effectuée !',
    'Votre colis a été livré. Merci d\'utiliser OUAGA CHAP !',
    [
        'type' => 'order_delivered',
        'order_id' => $order->uuid,
    ]
);
```

### 5. Paiement reçu (pour coursier)

```php
$this->pushService->sendToUser(
    $order->courier,
    'Paiement reçu',
    "Vous avez reçu {$amount} FCFA pour la livraison",
    [
        'type' => 'payment_received',
        'order_id' => $order->uuid,
        'amount' => (string) $amount,
    ]
);
```

## Enregistrement du token FCM

### Endpoint API

```http
POST /api/v1/users/fcm-token
Authorization: Bearer {token}
Content-Type: application/json

{
    "fcm_token": "fcm_device_token_here"
}
```

### Controller

```php
// app/Http/Controllers/Api/V1/UserController.php

public function updateFcmToken(Request $request)
{
    $request->validate([
        'fcm_token' => 'required|string',
    ]);
    
    $user = auth()->user();
    $user->update(['fcm_token' => $request->fcm_token]);
    
    return $this->success(null, 'Token mis à jour');
}
```

### Flutter (client/coursier)

```dart
// Dans main.dart ou au login

final fcmToken = await FirebaseMessaging.instance.getToken();
await apiService.updateFcmToken(fcmToken);

// Écouter les changements de token
FirebaseMessaging.instance.onTokenRefresh.listen((newToken) {
  apiService.updateFcmToken(newToken);
});
```

## Topics

### S'abonner à un topic

```php
// Abonner un coursier au topic "couriers"
$this->messaging->subscribeToTopic('couriers', [$courier->fcm_token]);

// Désabonner
$this->messaging->unsubscribeFromTopic('couriers', [$courier->fcm_token]);
```

### Envoyer à tous les coursiers

```php
$this->pushService->sendToTopic(
    'couriers',
    'Maintenance prévue',
    'L\'application sera indisponible de 2h à 4h',
    ['type' => 'announcement']
);
```

## Gestion des erreurs

### Tokens invalides

```php
public function cleanInvalidTokens(): int
{
    $users = User::whereNotNull('fcm_token')->get();
    $invalidCount = 0;
    
    foreach ($users as $user) {
        try {
            // Envoyer un message de validation
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withData(['validate' => 'true']);
            $this->messaging->validate($message);
        } catch (\Exception $e) {
            $user->update(['fcm_token' => null]);
            $invalidCount++;
        }
    }
    
    return $invalidCount;
}
```

### Command de nettoyage

```bash
php artisan fcm:clean-tokens
```

## Configuration Flutter

### Android (android/app/build.gradle)

```gradle
dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.0.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

### iOS (ios/Runner/Info.plist)

```xml
<key>UIBackgroundModes</key>
<array>
    <string>fetch</string>
    <string>remote-notification</string>
</array>
```

### Dart (lib/main.dart)

```dart
import 'package:firebase_messaging/firebase_messaging.dart';

Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  // Handle background message
  print('Handling a background message: ${message.messageId}');
}

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  
  runApp(MyApp());
}
```

## Tests

### Envoyer une notification de test

```bash
php artisan tinker
```

```php
$user = User::find(1);
app(PushNotificationService::class)->sendToUser(
    $user,
    'Test',
    'Ceci est une notification de test'
);
```

### Via HTTP (Firebase Console)

1. Aller dans Firebase Console → Messaging
2. Créer une nouvelle campagne
3. Cibler par token ou topic
4. Envoyer
