# Suivi en Temps Réel (WebSockets)

## Vue d'ensemble

OUAGA CHAP utilise **Laravel Reverb** pour le suivi en temps réel:
- Position du coursier pendant la livraison
- Mises à jour de statut de commande
- Notifications instantanées

## Configuration

### Variables d'environnement

```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=ouagachap
REVERB_APP_KEY=reverb-key
REVERB_APP_SECRET=reverb-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Production avec SSL
# REVERB_SCHEME=https
# REVERB_HOST=ws.ouagachap.com
# REVERB_PORT=443
```

### Démarrer le serveur WebSocket

```bash
php artisan reverb:start

# Avec plus de logs
php artisan reverb:start --debug

# En arrière-plan (production)
php artisan reverb:start --host=0.0.0.0 --port=8080
```

## Canaux de diffusion

### 1. Canal de commande (privé)

Pour le suivi d'une commande spécifique:

```php
// routes/channels.php

Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    
    // Seul le client ou le coursier peut écouter
    return $order && (
        $order->client_id === $user->id ||
        $order->courier_id === $user->id
    );
});
```

### 2. Canal coursier disponible (présence)

Pour les nouvelles commandes à proximité:

```php
Broadcast::channel('couriers.{zone}', function ($user, $zone) {
    if ($user->role !== UserRole::COURIER) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});
```

## Events

### CourierLocationUpdated

```php
// app/Events/CourierLocationUpdated.php

class CourierLocationUpdated implements ShouldBroadcast
{
    public Order $order;
    public float $latitude;
    public float $longitude;
    
    public function __construct(Order $order, float $latitude, float $longitude)
    {
        $this->order = $order;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }
    
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('orders.' . $this->order->id);
    }
    
    public function broadcastAs(): string
    {
        return 'courier.location';
    }
    
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->uuid,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### OrderStatusChanged

```php
// app/Events/OrderStatusChanged.php

class OrderStatusChanged implements ShouldBroadcast
{
    public Order $order;
    
    public function __construct(Order $order)
    {
        $this->order = $order->load('courier');
    }
    
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('orders.' . $this->order->id);
    }
    
    public function broadcastAs(): string
    {
        return 'status.changed';
    }
    
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->uuid,
            'status' => $this->order->status->value,
            'courier' => $this->order->courier ? [
                'id' => $this->order->courier->id,
                'name' => $this->order->courier->name,
                'phone' => $this->order->courier->phone,
            ] : null,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
```

### NewOrderAvailable

```php
// app/Events/NewOrderAvailable.php

class NewOrderAvailable implements ShouldBroadcast
{
    public Order $order;
    public string $zone;
    
    public function __construct(Order $order, string $zone)
    {
        $this->order = $order;
        $this->zone = $zone;
    }
    
    public function broadcastOn(): Channel
    {
        return new Channel('couriers.' . $this->zone);
    }
    
    public function broadcastAs(): string
    {
        return 'new.order';
    }
    
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->uuid,
            'pickup_address' => $this->order->pickup_address,
            'delivery_address' => $this->order->delivery_address,
            'price' => $this->order->price,
            'distance' => $this->order->distance,
        ];
    }
}
```

## API: Mise à jour de position

### Endpoint

```http
POST /api/v1/courier/location/update
Authorization: Bearer {token}
Content-Type: application/json

{
    "latitude": 12.3456,
    "longitude": -1.5234
}
```

### Controller

```php
// app/Http/Controllers/Api/V1/CourierController.php

public function updateLocation(Request $request)
{
    $request->validate([
        'latitude' => 'required|numeric|between:-90,90',
        'longitude' => 'required|numeric|between:-180,180',
    ]);
    
    $courier = auth()->user();
    
    // Mettre à jour la position
    $courier->update([
        'current_latitude' => $request->latitude,
        'current_longitude' => $request->longitude,
        'last_location_update' => now(),
    ]);
    
    // Si le coursier a une commande en cours, diffuser la position
    $activeOrder = Order::where('courier_id', $courier->id)
        ->whereIn('status', [OrderStatus::ACCEPTED, OrderStatus::PICKED_UP])
        ->first();
        
    if ($activeOrder) {
        broadcast(new CourierLocationUpdated(
            $activeOrder,
            $request->latitude,
            $request->longitude
        ));
    }
    
    return $this->success(null, 'Position mise à jour');
}
```

## Intégration Flutter

### Configuration Laravel Echo

```dart
// pubspec.yaml
dependencies:
  laravel_echo: ^1.0.0
  pusher_client: ^2.0.0
```

### Initialisation

```dart
// lib/core/realtime/echo_client.dart

import 'package:laravel_echo/laravel_echo.dart';
import 'package:pusher_client/pusher_client.dart';

class EchoClient {
  late Echo echo;
  
  void init(String token) {
    PusherClient pusher = PusherClient(
      'reverb-key',
      PusherOptions(
        host: 'api.ouagachap.com',
        wsPort: 8080,
        encrypted: false, // true en production avec SSL
        auth: PusherAuth(
          'https://api.ouagachap.com/broadcasting/auth',
          headers: {
            'Authorization': 'Bearer $token',
          },
        ),
      ),
    );
    
    echo = Echo(
      broadcaster: EchoBroadcasterType.Pusher,
      client: pusher,
    );
  }
  
  void subscribeToOrder(String orderId, {
    required Function(dynamic) onLocationUpdate,
    required Function(dynamic) onStatusChange,
  }) {
    echo.private('orders.$orderId')
      ..listen('.courier.location', (data) {
        onLocationUpdate(data);
      })
      ..listen('.status.changed', (data) {
        onStatusChange(data);
      });
  }
  
  void unsubscribeFromOrder(String orderId) {
    echo.leave('orders.$orderId');
  }
  
  void disconnect() {
    echo.disconnect();
  }
}
```

### Usage dans BLoC

```dart
// lib/features/tracking/presentation/bloc/tracking_bloc.dart

class TrackingBloc extends Bloc<TrackingEvent, TrackingState> {
  final EchoClient _echoClient;
  
  TrackingBloc(this._echoClient) : super(TrackingInitial()) {
    on<StartTracking>(_onStartTracking);
    on<StopTracking>(_onStopTracking);
    on<CourierLocationReceived>(_onLocationReceived);
  }
  
  Future<void> _onStartTracking(StartTracking event, Emitter emit) async {
    _echoClient.subscribeToOrder(
      event.orderId,
      onLocationUpdate: (data) {
        add(CourierLocationReceived(
          latitude: data['latitude'],
          longitude: data['longitude'],
        ));
      },
      onStatusChange: (data) {
        add(OrderStatusReceived(status: data['status']));
      },
    );
    
    emit(TrackingActive(orderId: event.orderId));
  }
  
  void _onLocationReceived(CourierLocationReceived event, Emitter emit) {
    if (state is TrackingActive) {
      emit((state as TrackingActive).copyWith(
        courierLatitude: event.latitude,
        courierLongitude: event.longitude,
      ));
    }
  }
}
```

### Widget de carte

```dart
// lib/features/tracking/presentation/widgets/tracking_map.dart

class TrackingMap extends StatelessWidget {
  final double courierLat;
  final double courierLng;
  final double deliveryLat;
  final double deliveryLng;
  
  @override
  Widget build(BuildContext context) {
    return GoogleMap(
      initialCameraPosition: CameraPosition(
        target: LatLng(courierLat, courierLng),
        zoom: 15,
      ),
      markers: {
        Marker(
          markerId: MarkerId('courier'),
          position: LatLng(courierLat, courierLng),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
          infoWindow: InfoWindow(title: 'Coursier'),
        ),
        Marker(
          markerId: MarkerId('delivery'),
          position: LatLng(deliveryLat, deliveryLng),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
          infoWindow: InfoWindow(title: 'Destination'),
        ),
      },
      polylines: {
        Polyline(
          polylineId: PolylineId('route'),
          points: [
            LatLng(courierLat, courierLng),
            LatLng(deliveryLat, deliveryLng),
          ],
          color: Colors.blue,
          width: 4,
        ),
      },
    );
  }
}
```

## Fréquence des mises à jour

### Côté coursier (envoi)

```dart
// Envoyer la position toutes les 10 secondes
Timer.periodic(Duration(seconds: 10), (timer) async {
  final position = await Geolocator.getCurrentPosition();
  await apiClient.post('/courier/location/update', data: {
    'latitude': position.latitude,
    'longitude': position.longitude,
  });
});
```

### Optimisation

```dart
// N'envoyer que si la position a changé significativement
double _lastLat = 0;
double _lastLng = 0;

void updateLocation(Position position) {
  final distance = Geolocator.distanceBetween(
    _lastLat, _lastLng,
    position.latitude, position.longitude,
  );
  
  // N'envoyer que si déplacé de plus de 20 mètres
  if (distance > 20) {
    _lastLat = position.latitude;
    _lastLng = position.longitude;
    _sendLocationUpdate(position);
  }
}
```

## Nginx configuration (production)

```nginx
# WebSocket reverse proxy
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_read_timeout 60s;
    proxy_send_timeout 60s;
}
```

## Débogage

### Logs Reverb

```bash
php artisan reverb:start --debug
```

### Tester avec wscat

```bash
npm install -g wscat
wscat -c ws://localhost:8080/app/reverb-key
```

### Vérifier les events

```php
// Dans tinker
event(new \App\Events\CourierLocationUpdated($order, 12.34, -1.56));
```
