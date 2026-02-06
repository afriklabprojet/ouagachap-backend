# Tests

## Vue d'ensemble

Le projet utilise **PHPUnit** pour les tests backend et **Flutter Test** pour les applications mobiles.

## Tests Backend (Laravel)

### Exécuter les tests

```bash
cd api

# Tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage

# Tests spécifiques
php artisan test --filter=AuthTest
php artisan test tests/Feature/Api/OrderTest.php

# Tests en parallèle
php artisan test --parallel
```

### Structure des tests

```
tests/
├── Feature/
│   ├── Api/
│   │   ├── AuthTest.php
│   │   ├── OrderTest.php
│   │   ├── CourierTest.php
│   │   └── PaymentTest.php
│   └── Admin/
│       └── FilamentTest.php
├── Unit/
│   ├── Services/
│   │   ├── OrderServiceTest.php
│   │   └── PaymentServiceTest.php
│   └── Models/
│       ├── UserTest.php
│       └── OrderTest.php
└── TestCase.php
```

### Configuration de test

```php
// phpunit.xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
</php>
```

### Exemple: Test d'authentification

```php
// tests/Feature/Api/AuthTest.php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_otp()
    {
        $response = $this->postJson('/api/v1/auth/otp/send', [
            'phone' => '+22670123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP envoyé',
            ]);
    }

    public function test_user_can_verify_otp()
    {
        // Créer un utilisateur avec un OTP
        $user = User::factory()->create([
            'phone' => '+22670123456',
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '+22670123456',
            'otp' => '123456',
            'app_type' => 'client',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'phone'],
                ],
            ]);
    }

    public function test_courier_cannot_login_to_client_app()
    {
        $courier = User::factory()->courier()->create();

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => $courier->phone,
            'otp' => '123456',
            'app_type' => 'client',
        ]);

        $response->assertStatus(403);
    }
}
```

### Exemple: Test de commande

```php
// tests/Feature/Api/OrderTest.php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $client;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = User::factory()->client()->create();
        $this->token = $this->client->createToken('test')->plainTextToken;
    }

    public function test_client_can_create_order()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/orders', [
                'pickup_address' => 'Ouaga 2000',
                'pickup_latitude' => 12.3456,
                'pickup_longitude' => -1.5234,
                'delivery_address' => 'Zone 1',
                'delivery_latitude' => 12.3789,
                'delivery_longitude' => -1.5567,
                'package_description' => 'Documents',
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'uuid',
                    'status',
                    'price',
                    'distance',
                ],
            ]);
    }

    public function test_client_can_view_their_orders()
    {
        Order::factory()->count(3)->create([
            'client_id' => $this->client->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_client_cannot_view_other_client_orders()
    {
        $otherClient = User::factory()->client()->create();
        $order = Order::factory()->create([
            'client_id' => $otherClient->id,
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/v1/orders/{$order->uuid}");

        $response->assertStatus(404);
    }
}
```

### Exemple: Test unitaire de service

```php
// tests/Unit/Services/OrderServiceTest.php

namespace Tests\Unit\Services;

use App\Services\OrderService;
use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    protected OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
    }

    public function test_calculate_distance_between_two_points()
    {
        // Coordonnées de test (Ouaga 2000 → Zone 1)
        $distance = $this->service->calculateDistance(
            12.3456, -1.5234,  // Pickup
            12.3789, -1.5567   // Delivery
        );

        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(50, $distance); // < 50 km
    }

    public function test_calculate_price_based_on_distance()
    {
        $price = $this->service->calculatePrice(5.0); // 5 km

        $this->assertEquals(1500, $price); // Prix minimum
    }

    public function test_calculate_price_for_long_distance()
    {
        $price = $this->service->calculatePrice(15.0); // 15 km

        $this->assertEquals(3000, $price); // 200 FCFA/km * 15
    }
}
```

### Factories

```php
// database/factories/UserFactory.php

public function client(): static
{
    return $this->state(fn () => [
        'role' => UserRole::CUSTOMER,
    ]);
}

public function courier(): static
{
    return $this->state(fn () => [
        'role' => UserRole::COURIER,
        'status' => UserStatus::ACTIVE,
        'vehicle_type' => 'moto',
        'vehicle_plate' => 'BF-1234-A',
    ]);
}

// database/factories/OrderFactory.php

public function definition(): array
{
    return [
        'uuid' => Str::uuid(),
        'client_id' => User::factory()->client(),
        'pickup_address' => fake()->address(),
        'pickup_latitude' => 12.3 + fake()->randomFloat(4, 0, 0.1),
        'pickup_longitude' => -1.5 + fake()->randomFloat(4, 0, 0.1),
        'delivery_address' => fake()->address(),
        'delivery_latitude' => 12.3 + fake()->randomFloat(4, 0, 0.1),
        'delivery_longitude' => -1.5 + fake()->randomFloat(4, 0, 0.1),
        'status' => OrderStatus::PENDING,
        'price' => 1500,
        'distance' => 5.0,
    ];
}
```

## Tests Flutter

### Exécuter les tests

```bash
cd client  # ou coursier

# Tous les tests
flutter test

# Tests avec couverture
flutter test --coverage

# Générer rapport HTML
genhtml coverage/lcov.info -o coverage_report
open coverage_report/index.html

# Test spécifique
flutter test test/features/auth/auth_bloc_test.dart
```

### Structure des tests

```
test/
├── features/
│   ├── auth/
│   │   ├── auth_bloc_test.dart
│   │   ├── auth_repository_test.dart
│   │   └── login_page_test.dart
│   ├── orders/
│   │   ├── order_bloc_test.dart
│   │   └── order_list_test.dart
│   └── profile/
│       └── profile_bloc_test.dart
├── core/
│   └── network/
│       └── api_client_test.dart
└── test_helpers.dart
```

### Exemple: Test de BLoC

```dart
// test/features/auth/auth_bloc_test.dart

import 'package:bloc_test/bloc_test.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';

class MockAuthRepository extends Mock implements AuthRepository {}

void main() {
  late AuthBloc authBloc;
  late MockAuthRepository mockRepository;

  setUp(() {
    mockRepository = MockAuthRepository();
    authBloc = AuthBloc(repository: mockRepository);
  });

  tearDown(() {
    authBloc.close();
  });

  group('SendOtp', () {
    blocTest<AuthBloc, AuthState>(
      'emits [Loading, OtpSent] when SendOtp is successful',
      build: () {
        when(mockRepository.sendOtp(any))
            .thenAnswer((_) async => Right(unit));
        return authBloc;
      },
      act: (bloc) => bloc.add(SendOtp('+22670123456')),
      expect: () => [
        AuthLoading(),
        OtpSent(),
      ],
    );

    blocTest<AuthBloc, AuthState>(
      'emits [Loading, Error] when SendOtp fails',
      build: () {
        when(mockRepository.sendOtp(any))
            .thenAnswer((_) async => Left(ServerFailure()));
        return authBloc;
      },
      act: (bloc) => bloc.add(SendOtp('+22670123456')),
      expect: () => [
        AuthLoading(),
        AuthError('Erreur serveur'),
      ],
    );
  });
}
```

### Exemple: Test de Widget

```dart
// test/features/auth/login_page_test.dart

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

void main() {
  testWidgets('LoginPage displays phone input', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: BlocProvider(
          create: (_) => AuthBloc(),
          child: LoginPage(),
        ),
      ),
    );

    expect(find.byType(TextField), findsOneWidget);
    expect(find.text('Numéro de téléphone'), findsOneWidget);
    expect(find.byType(ElevatedButton), findsOneWidget);
  });

  testWidgets('LoginPage shows error on invalid phone', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: BlocProvider(
          create: (_) => AuthBloc(),
          child: LoginPage(),
        ),
      ),
    );

    // Entrer un numéro invalide
    await tester.enterText(find.byType(TextField), '123');
    await tester.tap(find.byType(ElevatedButton));
    await tester.pump();

    expect(find.text('Numéro invalide'), findsOneWidget);
  });
}
```

### Mocking avec Mockito

```dart
// test/test_helpers.dart

import 'package:mockito/annotations.dart';

@GenerateMocks([
  AuthRepository,
  OrderRepository,
  ApiClient,
])
void main() {}
```

```bash
# Générer les mocks
flutter pub run build_runner build
```

## CI/CD avec GitHub Actions

```yaml
# .github/workflows/test.yml

name: Tests

on: [push, pull_request]

jobs:
  laravel-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          
      - name: Install Dependencies
        run: |
          cd api
          composer install -q --no-ansi --no-interaction
          
      - name: Run Tests
        run: |
          cd api
          php artisan test

  flutter-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Flutter
        uses: subosito/flutter-action@v2
        with:
          flutter-version: '3.x'
          
      - name: Install Dependencies
        run: |
          cd client
          flutter pub get
          
      - name: Run Tests
        run: |
          cd client
          flutter test --coverage
```

## Bonnes pratiques

1. **Tester les cas limites** - Valeurs nulles, listes vides, erreurs réseau
2. **Utiliser des factories** - Créer des données de test cohérentes
3. **Mocker les dépendances externes** - API, SMS, Firebase
4. **Tester les erreurs** - Vérifier les messages d'erreur appropriés
5. **Maintenir la couverture > 70%** - Surtout pour les services critiques
