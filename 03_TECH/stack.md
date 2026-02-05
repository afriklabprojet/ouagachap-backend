# 🛠️ Technology Stack - OUAGA CHAP

> Complete technology stack documentation with versions, configurations, and justifications

---

## 1. Stack Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          OUAGA CHAP TECH STACK                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  FRONTEND (Mobile)          BACKEND                    INFRASTRUCTURE       │
│  ┌─────────────────┐       ┌─────────────────┐        ┌─────────────────┐  │
│  │ Flutter 3.x     │       │ Laravel 11      │        │ Ubuntu 22.04    │  │
│  │ Dart 3.x        │       │ PHP 8.2         │        │ Nginx           │  │
│  │ BLoC Pattern    │       │ Filament v3     │        │ Supervisor      │  │
│  │ Dio HTTP        │       │ Laravel Sanctum │        │ Redis           │  │
│  │ Google Maps SDK │       │ Laravel Queues  │        │ MySQL 8.x       │  │
│  └─────────────────┘       └─────────────────┘        └─────────────────┘  │
│                                                                              │
│  EXTERNAL SERVICES                                                           │
│  ┌─────────────────┐       ┌─────────────────┐        ┌─────────────────┐  │
│  │ Google Maps API │       │ Twilio SMS      │        │ Firebase FCM    │  │
│  │ - Places        │       │                 │        │ (Push Notif)    │  │
│  │ - Directions    │       │                 │        │                 │  │
│  │ - Distance      │       │                 │        │                 │  │
│  └─────────────────┘       └─────────────────┘        └─────────────────┘  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Backend Stack

### 2.1 Laravel 11

| Attribute | Value |
|-----------|-------|
| **Version** | 11.x (LTS) |
| **PHP Version** | 8.2+ |
| **Release Date** | March 2024 |
| **Support Until** | March 2026 |

#### Why Laravel?
- ✅ Rapid development with elegant syntax
- ✅ Built-in authentication (Sanctum)
- ✅ Excellent ORM (Eloquent)
- ✅ Queue system for background jobs
- ✅ Great documentation and community
- ✅ Filament admin panel integration

#### Key Laravel Packages

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "laravel/sanctum": "^4.0",
        "filament/filament": "^3.2",
        "twilio/sdk": "^7.0",
        "kreait/laravel-firebase": "^5.0",
        "spatie/laravel-permission": "^6.0",
        "spatie/laravel-medialibrary": "^11.0",
        "league/flysystem-aws-s3-v3": "^3.0"
    },
    "require-dev": {
        "pestphp/pest": "^2.0",
        "laravel/pint": "^1.0",
        "laravel/telescope": "^5.0"
    }
}
```

### 2.2 Filament v3

| Attribute | Value |
|-----------|-------|
| **Version** | 3.2.x |
| **Purpose** | Admin Panel |
| **Features** | Resources, Pages, Widgets, Actions |

#### Filament Configuration

```php
// config/filament.php
return [
    'brand' => 'OUAGA CHAP Admin',
    'auth' => [
        'guard' => 'web',
        'pages' => [
            'login' => \Filament\Pages\Auth\Login::class,
        ],
    ],
    'default_filesystem_disk' => 's3',
    'default_avatar_provider' => \Filament\AvatarProviders\UiAvatarsProvider::class,
];
```

#### Filament Resources to Create

| Resource | Model | Features |
|----------|-------|----------|
| UserResource | User | CRUD, filter by role, bulk actions |
| OrderResource | Order | CRUD, status filter, timeline view |
| CourierResource | User (courier) | Document review, approval workflow |
| PaymentResource | Payment | List, filter, export |
| WithdrawalResource | Withdrawal | Approval workflow |

### 2.3 Laravel Sanctum

| Attribute | Value |
|-----------|-------|
| **Version** | 4.x |
| **Purpose** | API Authentication |
| **Token Type** | Bearer tokens |

#### Sanctum Configuration

```php
// config/sanctum.php
return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '')),
    'guard' => ['web'],
    'expiration' => 43200, // 30 days in minutes
    'token_prefix' => '',
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
```

---

## 3. Database Stack

### 3.1 MySQL 8.x

| Attribute | Value |
|-----------|-------|
| **Version** | 8.0.x |
| **Engine** | InnoDB |
| **Charset** | utf8mb4 |
| **Collation** | utf8mb4_unicode_ci |

#### Why MySQL?
- ✅ Widely supported and documented
- ✅ Excellent Laravel integration
- ✅ Available as managed service everywhere
- ✅ ACID compliant
- ✅ JSON support for flexible data

#### MySQL Configuration

```ini
# my.cnf optimizations for OUAGA CHAP
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 200
query_cache_type = 0
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Slow query logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
```

### 3.2 Redis

| Attribute | Value |
|-----------|-------|
| **Version** | 7.x |
| **Purpose** | Cache, Session, Queue |
| **Memory** | 512MB (MVP) |

#### Redis Usage

```php
// config/database.php - Redis configuration
'redis' => [
    'client' => 'phpredis',
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_CACHE_DB', 1),
    ],
],
```

| Use Case | Redis DB | TTL |
|----------|----------|-----|
| Session | DB 0 | 30 days |
| Cache | DB 1 | Varies |
| Queue | DB 2 | Until processed |

---

## 4. Mobile Stack

### 4.1 Flutter

| Attribute | Value |
|-----------|-------|
| **Version** | 3.19.x |
| **Dart Version** | 3.3.x |
| **Min Android** | API 21 (5.0) |
| **Min iOS** | 12.0 |

#### Why Flutter?
- ✅ Single codebase for Android & iOS
- ✅ Native performance
- ✅ Hot reload for fast development
- ✅ Rich widget library
- ✅ Strong typing with Dart
- ✅ Growing African developer community

#### Flutter Dependencies

```yaml
# pubspec.yaml
dependencies:
  flutter:
    sdk: flutter
  
  # State Management
  flutter_bloc: ^8.1.4
  equatable: ^2.0.5
  
  # Networking
  dio: ^5.4.0
  retrofit: ^4.1.0
  
  # Local Storage
  shared_preferences: ^2.2.2
  flutter_secure_storage: ^9.0.0
  
  # Maps & Location
  google_maps_flutter: ^2.5.3
  geolocator: ^11.0.0
  geocoding: ^3.0.0
  
  # UI Components
  flutter_svg: ^2.0.9
  cached_network_image: ^3.3.1
  shimmer: ^3.0.0
  
  # Utilities
  intl: ^0.19.0
  url_launcher: ^6.2.4
  permission_handler: ^11.3.0
  
  # Firebase
  firebase_core: ^2.27.0
  firebase_messaging: ^14.7.19

dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^3.0.1
  build_runner: ^2.4.8
  retrofit_generator: ^8.1.0
  mockito: ^5.4.4
```

### 4.2 State Management - BLoC

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           BLoC ARCHITECTURE                                  │
└─────────────────────────────────────────────────────────────────────────────┘

  UI (Widgets)              BLoC                    Repository
       │                     │                          │
       │  Add Event          │                          │
       │────────────────────▶│                          │
       │                     │  Call method             │
       │                     │─────────────────────────▶│
       │                     │                          │
       │                     │◀─────────────────────────│
       │                     │  Return data             │
       │◀────────────────────│                          │
       │  Emit new State     │                          │
       │                     │                          │
```

### 4.3 HTTP Client - Dio

```dart
// lib/core/network/api_client.dart
class ApiClient {
  static Dio createDio() {
    final dio = Dio(BaseOptions(
      baseUrl: ApiConstants.baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));

    dio.interceptors.addAll([
      AuthInterceptor(),
      RetryInterceptor(),
      LogInterceptor(
        requestBody: true,
        responseBody: true,
      ),
    ]);

    return dio;
  }
}
```

---

## 5. External Services

### 5.1 Google Maps Platform

| API | Purpose | Pricing |
|-----|---------|---------|
| **Maps SDK** | Display maps in app | $7/1000 loads |
| **Places API** | Address autocomplete | $17/1000 requests |
| **Directions API** | Route calculation | $5/1000 requests |
| **Distance Matrix** | Distance/time estimation | $5/1000 elements |
| **Geocoding API** | Address to coordinates | $5/1000 requests |

#### API Key Configuration

```dart
// Android: android/app/src/main/AndroidManifest.xml
<meta-data
    android:name="com.google.android.geo.API_KEY"
    android:value="${GOOGLE_MAPS_API_KEY}"/>

// iOS: ios/Runner/AppDelegate.swift
GMSServices.provideAPIKey("YOUR_API_KEY")
```

#### Monthly Cost Estimate (MVP)

| API | Estimated Usage | Cost |
|-----|-----------------|------|
| Maps SDK | 5,000 loads | $35 |
| Places | 2,000 requests | $34 |
| Directions | 1,000 requests | $5 |
| Distance Matrix | 500 elements | $2.50 |
| **Total** | | **~$77/month** |

### 5.2 SMS Gateway - Twilio

| Attribute | Value |
|-----------|-------|
| **Service** | Twilio Programmable SMS |
| **Cost** | ~$0.05/SMS to Burkina Faso |
| **Sender ID** | Custom (OUAGACHAP) |

#### Twilio Integration

```php
// app/Services/OtpService.php
class OtpService
{
    private TwilioClient $twilio;

    public function __construct()
    {
        $this->twilio = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendOtp(string $phone): bool
    {
        $otp = random_int(100000, 999999);
        
        // Store OTP in cache (5 min expiry)
        Cache::put("otp:{$phone}", Hash::make($otp), now()->addMinutes(5));

        // Send SMS
        $this->twilio->messages->create($phone, [
            'from' => config('services.twilio.from'),
            'body' => "Votre code OUAGA CHAP est: {$otp}. Valide 5 minutes.",
        ]);

        return true;
    }
}
```

### 5.3 Push Notifications - Firebase

| Platform | Service |
|----------|---------|
| Android | Firebase Cloud Messaging (FCM) |
| iOS | FCM + APNs |

#### Firebase Setup

```dart
// lib/services/notification_service.dart
class NotificationService {
  final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  Future<void> initialize() async {
    // Request permission
    await _messaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Get FCM token
    final token = await _messaging.getToken();
    // Send token to backend

    // Handle messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    FirebaseMessaging.onBackgroundMessage(_handleBackgroundMessage);
  }
}
```

#### Notification Types

| Type | Trigger | Content |
|------|---------|---------|
| `order_accepted` | Courier accepts | "Votre coursier arrive!" |
| `order_picked_up` | Courier picks up | "Colis récupéré, en route!" |
| `order_delivered` | Delivery confirmed | "Livraison confirmée!" |
| `new_order` | New order (courier) | "Nouvelle course disponible!" |
| `withdrawal_processed` | Payout done | "Retrait de X FCFA effectué" |

---

## 6. DevOps & Infrastructure

### 6.1 Server Stack

| Component | Technology | Purpose |
|-----------|------------|---------|
| OS | Ubuntu 22.04 LTS | Server operating system |
| Web Server | Nginx 1.24 | Reverse proxy, static files |
| PHP | PHP 8.2 FPM | Laravel runtime |
| Process Manager | Supervisor | Queue workers, schedules |
| SSL | Let's Encrypt | HTTPS certificates |

#### Nginx Configuration

```nginx
# /etc/nginx/sites-available/ouagachap.conf
server {
    listen 80;
    server_name api.ouagachap.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.ouagachap.com;
    root /var/www/ouagachap/public;

    ssl_certificate /etc/letsencrypt/live/api.ouagachap.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.ouagachap.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 6.2 Supervisor Configuration

```ini
# /etc/supervisor/conf.d/ouagachap-worker.conf
[program:ouagachap-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ouagachap/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ouagachap/storage/logs/worker.log
stopwaitsecs=3600
```

### 6.3 CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
      - name: Run tests
        run: php artisan test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to server
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.SERVER_HOST }}
          username: ${{ secrets.SERVER_USER }}
          key: ${{ secrets.SERVER_SSH_KEY }}
          script: |
            cd /var/www/ouagachap
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo supervisorctl restart ouagachap-worker:*
```

---

## 7. Development Tools

### 7.1 Required Tools

| Tool | Version | Purpose |
|------|---------|---------|
| VS Code | Latest | IDE |
| Android Studio | Latest | Android emulator, Flutter tools |
| Xcode | Latest | iOS build (Mac only) |
| Postman | Latest | API testing |
| TablePlus | Latest | Database GUI |
| Git | Latest | Version control |

### 7.2 VS Code Extensions

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "shufo.vscode-blade-formatter",
    "onecentlin.laravel-blade",
    "Dart-Code.dart-code",
    "Dart-Code.flutter",
    "mikestead.dotenv",
    "esbenp.prettier-vscode",
    "dbaeumer.vscode-eslint"
  ]
}
```

### 7.3 Laravel Development Commands

```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Clear caches
php artisan optimize:clear

# Run queue worker
php artisan queue:work

# Run tests
php artisan test

# Generate IDE helper
php artisan ide-helper:generate
php artisan ide-helper:models

# Create Filament resources
php artisan make:filament-resource User --generate
```

### 7.4 Flutter Development Commands

```bash
# Get dependencies
flutter pub get

# Run app
flutter run

# Build APK
flutter build apk --release

# Build iOS
flutter build ios --release

# Run tests
flutter test

# Analyze code
flutter analyze

# Generate code (BLoC, Retrofit)
dart run build_runner build
```

---

## 8. Version Matrix

| Component | MVP Version | Next Upgrade |
|-----------|-------------|--------------|
| Laravel | 11.x | 12.x (2027) |
| PHP | 8.2 | 8.3 |
| Filament | 3.2 | 3.x |
| Flutter | 3.19 | 3.x |
| Dart | 3.3 | 3.x |
| MySQL | 8.0 | 8.x |
| Redis | 7.0 | 7.x |
| Node.js | 18 LTS | 20 LTS |

---

## 9. Security Considerations

### 9.1 Backend Security

```php
// Security headers middleware
class SecurityHeaders
{
    public function handle($request, $next)
    {
        $response = $next($request);
        
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        
        return $response;
    }
}
```

### 9.2 Mobile Security

```dart
// Secure storage for tokens
final storage = FlutterSecureStorage(
  aOptions: AndroidOptions(
    encryptedSharedPreferences: true,
  ),
);

await storage.write(key: 'auth_token', value: token);
```

### 9.3 API Security Checklist

- [x] HTTPS only
- [x] Rate limiting
- [x] Input validation
- [x] SQL injection prevention (Eloquent)
- [x] XSS prevention
- [x] CORS configuration
- [x] Token expiration
- [ ] API versioning
- [ ] Request signing (Phase 2)

---

*Document maintenu par l'équipe technique - Dernière mise à jour: Janvier 2026*
