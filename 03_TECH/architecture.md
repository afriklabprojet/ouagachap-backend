# 🏗️ Technical Architecture - OUAGA CHAP

> System architecture documentation for the urban delivery platform

---

## 1. Architecture Overview

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CLIENTS                                         │
├─────────────────┬─────────────────┬─────────────────────────────────────────┤
│  Customer App   │   Courier App   │           Admin Panel                   │
│   (Flutter)     │    (Flutter)    │          (Filament v3)                  │
│   Android/iOS   │   Android/iOS   │            Web Browser                  │
└────────┬────────┴────────┬────────┴──────────────────┬──────────────────────┘
         │                 │                           │
         │    HTTPS/REST   │       HTTPS/REST          │     HTTPS
         │                 │                           │
┌────────▼─────────────────▼───────────────────────────▼──────────────────────┐
│                         API GATEWAY / LOAD BALANCER                          │
│                              (Nginx / Laravel)                               │
└────────────────────────────────────┬────────────────────────────────────────┘
                                     │
┌────────────────────────────────────▼────────────────────────────────────────┐
│                           LARAVEL 11 APPLICATION                             │
├─────────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   Sanctum   │  │ Controllers │  │  Services   │  │   Models    │         │
│  │    Auth     │  │   (API)     │  │   Layer     │  │  (Eloquent) │         │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │  Filament   │  │    Jobs     │  │   Events    │  │Notifications│         │
│  │   Admin     │  │   (Queue)   │  │ & Listeners │  │  (SMS/Push) │         │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘         │
└────────────────────────────────────┬────────────────────────────────────────┘
                                     │
         ┌───────────────────────────┼───────────────────────────┐
         │                           │                           │
┌────────▼────────┐      ┌───────────▼───────────┐    ┌─────────▼─────────┐
│     MySQL       │      │        Redis          │    │   File Storage    │
│    Database     │      │    (Cache/Queue)      │    │  (Local/S3)       │
└─────────────────┘      └───────────────────────┘    └───────────────────┘
```

### 1.2 Architecture Style
- **Pattern:** Monolithic with Service Layer (MVP-appropriate)
- **API Style:** RESTful JSON API
- **Authentication:** Token-based (Laravel Sanctum)
- **Real-time:** Polling (MVP) → WebSockets (Phase 2)

---

## 2. Component Architecture

### 2.1 Backend Components (Laravel 11)

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── AuthController.php          # OTP login/register
│   │   │   ├── OrderController.php         # Order CRUD
│   │   │   ├── CourierController.php       # Courier operations
│   │   │   ├── PaymentController.php       # Payment processing
│   │   │   ├── TrackingController.php      # Location updates
│   │   │   └── ProfileController.php       # User profile
│   │   └── Webhook/
│   │       └── MobileMoneyController.php   # Payment callbacks
│   ├── Middleware/
│   │   ├── EnsureUserIsCustomer.php
│   │   ├── EnsureUserIsCourier.php
│   │   └── EnsureCourierIsApproved.php
│   ├── Requests/
│   │   ├── CreateOrderRequest.php
│   │   ├── UpdateOrderStatusRequest.php
│   │   └── ...
│   └── Resources/
│       ├── OrderResource.php
│       ├── UserResource.php
│       └── ...
├── Models/
│   ├── User.php
│   ├── Order.php
│   ├── Payment.php
│   ├── Rating.php
│   ├── CourierDocument.php
│   ├── CourierLocation.php
│   └── Withdrawal.php
├── Services/
│   ├── OtpService.php                      # OTP generation/verification
│   ├── PricingService.php                  # Price calculation
│   ├── OrderService.php                    # Order business logic
│   ├── PaymentService.php                  # Payment processing
│   ├── NotificationService.php             # SMS/Push notifications
│   └── DistanceService.php                 # Google Maps integration
├── Jobs/
│   ├── SendOtpSms.php
│   ├── SendOrderNotification.php
│   ├── ProcessPayment.php
│   └── ExpireUnacceptedOrders.php
├── Events/
│   ├── OrderCreated.php
│   ├── OrderAccepted.php
│   ├── OrderPickedUp.php
│   ├── OrderDelivered.php
│   └── CourierLocationUpdated.php
├── Listeners/
│   ├── NotifyCustomerOrderAccepted.php
│   ├── NotifyCourierNewOrder.php
│   └── ...
└── Filament/
    ├── Resources/
    │   ├── UserResource.php
    │   ├── OrderResource.php
    │   ├── CourierResource.php
    │   ├── PaymentResource.php
    │   └── WithdrawalResource.php
    ├── Pages/
    │   └── Dashboard.php
    └── Widgets/
        ├── OrdersChart.php
        ├── RevenueStats.php
        └── ...
```

### 2.2 Mobile App Components (Flutter)

```
lib/
├── main.dart
├── app/
│   ├── app.dart
│   └── routes.dart
├── core/
│   ├── constants/
│   │   ├── api_constants.dart
│   │   ├── app_constants.dart
│   │   └── color_constants.dart
│   ├── errors/
│   │   ├── exceptions.dart
│   │   └── failures.dart
│   ├── network/
│   │   ├── api_client.dart
│   │   ├── network_info.dart
│   │   └── interceptors.dart
│   └── utils/
│       ├── validators.dart
│       └── formatters.dart
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── datasources/
│   │   │   ├── models/
│   │   │   └── repositories/
│   │   ├── domain/
│   │   │   ├── entities/
│   │   │   ├── repositories/
│   │   │   └── usecases/
│   │   └── presentation/
│   │       ├── bloc/
│   │       ├── pages/
│   │       └── widgets/
│   ├── orders/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   ├── tracking/
│   │   ├── data/
│   │   ├── domain/
│   │   └── presentation/
│   └── profile/
│       ├── data/
│       ├── domain/
│       └── presentation/
└── shared/
    ├── widgets/
    ├── themes/
    └── services/
```

---

## 3. Data Flow Architecture

### 3.1 Order Creation Flow

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ Customer │     │  Flutter │     │  Laravel │     │  MySQL   │     │   SMS    │
│   App    │     │   App    │     │   API    │     │    DB    │     │ Gateway  │
└────┬─────┘     └────┬─────┘     └────┬─────┘     └────┬─────┘     └────┬─────┘
     │                │                │                │                │
     │ 1. Enter       │                │                │                │
     │    addresses   │                │                │                │
     │───────────────▶│                │                │                │
     │                │ 2. POST        │                │                │
     │                │    /orders     │                │                │
     │                │───────────────▶│                │                │
     │                │                │ 3. Validate    │                │
     │                │                │    & Calculate │                │
     │                │                │    Price       │                │
     │                │                │───────────────▶│                │
     │                │                │                │                │
     │                │                │◀───────────────│                │
     │                │                │ 4. Save Order  │                │
     │                │                │    (UUID)      │                │
     │                │                │───────────────▶│                │
     │                │                │                │                │
     │                │                │ 5. Dispatch    │                │
     │                │                │    Event       │                │
     │                │                │────────────────────────────────▶│
     │                │                │                │                │
     │                │◀───────────────│                │    6. Notify   │
     │                │ Response       │                │    Couriers    │
     │◀───────────────│ (Order UUID)   │                │                │
     │ Show           │                │                │                │
     │ Confirmation   │                │                │                │
     │                │                │                │                │
```

### 3.2 Real-Time Tracking Flow

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                           TRACKING DATA FLOW                                  │
└──────────────────────────────────────────────────────────────────────────────┘

COURIER APP                    BACKEND                         CUSTOMER APP
     │                            │                                 │
     │  POST /location            │                                 │
     │  {lat, lng, order_id}      │                                 │
     │───────────────────────────▶│                                 │
     │                            │ Store in                        │
     │                            │ courier_locations               │
     │                            │ table                           │
     │                            │                                 │
     │                            │                                 │
     │                            │◀────────────────────────────────│
     │                            │  GET /orders/{id}/tracking      │
     │                            │  (polling every 10s)            │
     │                            │                                 │
     │                            │─────────────────────────────────▶
     │                            │  {lat, lng, status, eta}        │
     │                            │                                 │

MVP: Polling every 10 seconds
Phase 2: WebSocket for real-time updates (Laravel Reverb)
```

### 3.3 Payment Flow

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                            PAYMENT FLOW (MOCK)                               │
└──────────────────────────────────────────────────────────────────────────────┘

     CUSTOMER              FLUTTER              LARAVEL           MOCK PROVIDER
         │                    │                    │                    │
         │ 1. Confirm Order   │                    │                    │
         │───────────────────▶│                    │                    │
         │                    │ 2. POST /payments  │                    │
         │                    │    {order_id,      │                    │
         │                    │     phone,         │                    │
         │                    │     provider}      │                    │
         │                    │───────────────────▶│                    │
         │                    │                    │ 3. Init Payment    │
         │                    │                    │───────────────────▶│
         │                    │                    │                    │
         │                    │                    │◀───────────────────│
         │                    │                    │ 4. Mock Response   │
         │                    │                    │    (pending)       │
         │                    │◀───────────────────│                    │
         │◀───────────────────│ 5. Show USSD      │                    │
         │                    │    Instructions    │                    │
         │                    │                    │                    │
         │ 6. "Confirm" USSD  │                    │                    │
         │   (simulated)      │                    │                    │
         │───────────────────▶│                    │                    │
         │                    │ 7. POST /payments  │                    │
         │                    │    /confirm (mock) │                    │
         │                    │───────────────────▶│                    │
         │                    │                    │ 8. Update Payment  │
         │                    │                    │    Status          │
         │                    │                    │ 9. Update Order    │
         │                    │                    │    Status          │
         │                    │◀───────────────────│                    │
         │◀───────────────────│ 10. Payment       │                    │
         │                    │     Confirmed      │                    │
```

---

## 4. Database Architecture

### 4.1 Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│      users      │       │     orders      │       │    payments     │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │       │ id (PK)         │       │ id (PK)         │
│ phone           │◀──────│ customer_id(FK) │       │ order_id (FK)───│──┐
│ name            │◀──────│ courier_id (FK) │       │ amount          │  │
│ role            │       │ uuid            │───────│ provider        │  │
│ status          │       │ status          │       │ phone           │  │
│ ...             │       │ pickup_*        │       │ status          │  │
└────────┬────────┘       │ dropoff_*       │       │ transaction_id  │  │
         │                │ price           │       │ ...             │  │
         │                │ ...             │       └─────────────────┘  │
         │                └────────┬────────┘                            │
         │                         │                                     │
         │                         │         ┌───────────────────────────┘
         │                         │         │
         │                ┌────────▼─────────▼──┐
         │                │      ratings        │
         │                ├─────────────────────┤
         │                │ id (PK)             │
         │                │ order_id (FK)       │
         │                │ from_user_id (FK)   │
         │                │ to_user_id (FK)     │
         │                │ score               │
         │                │ comment             │
         │                └─────────────────────┘
         │
    ┌────▼────────────────┐       ┌─────────────────────┐
    │ courier_documents   │       │  courier_locations  │
    ├─────────────────────┤       ├─────────────────────┤
    │ id (PK)             │       │ id (PK)             │
    │ courier_id (FK)     │       │ courier_id (FK)     │
    │ type                │       │ latitude            │
    │ file_path           │       │ longitude           │
    │ status              │       │ recorded_at         │
    └─────────────────────┘       └─────────────────────┘

    ┌─────────────────────┐
    │    withdrawals      │
    ├─────────────────────┤
    │ id (PK)             │
    │ courier_id (FK)     │
    │ amount              │
    │ phone               │
    │ provider            │
    │ status              │
    │ processed_at        │
    └─────────────────────┘
```

### 4.2 Indexing Strategy

| Table | Index | Columns | Purpose |
|-------|-------|---------|---------|
| users | idx_phone | phone | Login lookup |
| users | idx_role_status | role, status | Filter users by type |
| orders | idx_customer | customer_id | Customer order history |
| orders | idx_courier | courier_id | Courier order history |
| orders | idx_status | status | Filter by status |
| orders | idx_uuid | uuid | Public order lookup |
| payments | idx_order | order_id | Payment by order |
| courier_locations | idx_courier_time | courier_id, recorded_at | Latest location |

---

## 5. API Architecture

### 5.1 API Versioning
```
Base URL: https://api.ouagachap.com/api/v1
```

### 5.2 Authentication Flow
```
┌────────────────────────────────────────────────────────────────────────────┐
│                         AUTHENTICATION FLOW                                 │
└────────────────────────────────────────────────────────────────────────────┘

1. REQUEST OTP
   POST /api/v1/auth/otp/request
   Body: { "phone": "+22670000000" }
   Response: { "message": "OTP sent", "expires_in": 300 }

2. VERIFY OTP
   POST /api/v1/auth/otp/verify
   Body: { "phone": "+22670000000", "otp": "123456" }
   Response: { 
     "token": "1|abc123...",
     "user": { ... },
     "is_new_user": true
   }

3. AUTHENTICATED REQUESTS
   Headers: {
     "Authorization": "Bearer 1|abc123...",
     "Accept": "application/json"
   }

4. LOGOUT
   POST /api/v1/auth/logout
   Headers: { "Authorization": "Bearer 1|abc123..." }
```

### 5.3 Response Format
```json
// Success Response
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}

// Error Response
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "phone": ["The phone field is required."]
    }
  }
}

// Paginated Response
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### 5.4 Rate Limiting
| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| OTP Request | 3 | 15 minutes |
| General API | 60 | 1 minute |
| Location Updates | 120 | 1 minute |

---

## 6. Security Architecture

### 6.1 Security Layers

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           SECURITY LAYERS                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ LAYER 1: TRANSPORT SECURITY                                          │    │
│  │ • HTTPS/TLS 1.3 only                                                 │    │
│  │ • Certificate pinning (mobile apps)                                  │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ LAYER 2: AUTHENTICATION                                              │    │
│  │ • Laravel Sanctum tokens                                             │    │
│  │ • OTP-based (no passwords)                                           │    │
│  │ • Token expiration: 30 days                                          │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ LAYER 3: AUTHORIZATION                                               │    │
│  │ • Role-based (customer, courier, admin)                              │    │
│  │ • Middleware guards                                                  │    │
│  │ • Policy-based resource access                                       │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ LAYER 4: INPUT VALIDATION                                            │    │
│  │ • Form Request validation                                            │    │
│  │ • SQL injection prevention (Eloquent)                                │    │
│  │ • XSS prevention (sanitization)                                      │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │ LAYER 5: RATE LIMITING                                               │    │
│  │ • Throttle middleware                                                │    │
│  │ • OTP abuse prevention                                               │    │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Data Protection
| Data Type | Protection |
|-----------|------------|
| Phone numbers | Stored plain (needed for SMS) |
| OTP codes | Hashed, auto-expire |
| API tokens | Hashed in database |
| Documents | Private storage, signed URLs |
| Locations | Retention: 7 days |

---

## 7. Infrastructure Architecture

### 7.1 MVP Infrastructure

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         MVP INFRASTRUCTURE                                   │
└─────────────────────────────────────────────────────────────────────────────┘

                    ┌─────────────────────────────┐
                    │      Cloud Provider         │
                    │   (DigitalOcean / Hetzner)  │
                    └─────────────────────────────┘
                                 │
          ┌──────────────────────┼──────────────────────┐
          │                      │                      │
┌─────────▼─────────┐  ┌─────────▼─────────┐  ┌───────▼─────────┐
│   Web Server      │  │    Database       │  │  File Storage   │
│   (VPS 4GB)       │  │    (MySQL)        │  │   (S3/Spaces)   │
├───────────────────┤  ├───────────────────┤  ├─────────────────┤
│ • Ubuntu 22.04    │  │ • Managed MySQL   │  │ • Documents     │
│ • Nginx           │  │ • 2GB RAM         │  │ • Images        │
│ • PHP 8.2         │  │ • Daily backups   │  │ • CDN enabled   │
│ • Laravel 11      │  │ • 20GB storage    │  │                 │
│ • Redis (local)   │  │                   │  │                 │
│ • Supervisor      │  │                   │  │                 │
└───────────────────┘  └───────────────────┘  └─────────────────┘

EXTERNAL SERVICES:
┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│  Google Maps  │  │  SMS Gateway  │  │    FCM/APNs   │
│     API       │  │   (Twilio)    │  │ (Push Notif)  │
└───────────────┘  └───────────────┘  └───────────────┘
```

### 7.2 Estimated Costs (MVP)

| Service | Provider | Monthly Cost |
|---------|----------|--------------|
| VPS (4GB RAM) | DigitalOcean | $24 |
| Managed MySQL | DigitalOcean | $15 |
| Object Storage | DigitalOcean | $5 |
| Domain + SSL | Cloudflare | Free |
| SMS (500/month) | Twilio | $20 |
| Google Maps | Google | $50 (estimate) |
| **Total** | | **~$115/month** |

### 7.3 Deployment Pipeline

```
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│  Code   │────▶│  Test   │────▶│  Build  │────▶│ Deploy  │
│  Push   │     │  (CI)   │     │ (Docker)│     │ (CD)    │
└─────────┘     └─────────┘     └─────────┘     └─────────┘
     │               │               │               │
     │           Run tests       Create          SSH + Pull
     │           PHPUnit         release          Artisan
     │           Pest            tag              commands
     │
  GitHub ─────────────────── GitHub Actions ──────────────────▶ Server
```

---

## 8. Scalability Considerations

### 8.1 Horizontal Scaling Path

```
MVP (Current)              Phase 2                    Phase 3
┌─────────────┐        ┌─────────────┐          ┌─────────────────┐
│  Single VPS │   ──▶  │ Load Balancer│    ──▶  │  Kubernetes     │
│  + MySQL    │        │ + 2 App Servers        │  + Microservices│
│             │        │ + Redis Cluster│       │  + Event Queue  │
└─────────────┘        └─────────────┘          └─────────────────┘
   500 users            5,000 users               50,000+ users
```

### 8.2 Database Scaling

| Phase | Strategy |
|-------|----------|
| MVP | Single MySQL instance |
| Growth | Read replicas |
| Scale | Sharding by city |

### 8.3 Caching Strategy

| Data | Cache | TTL |
|------|-------|-----|
| User profiles | Redis | 1 hour |
| Active orders | Redis | Real-time |
| Pricing config | Redis | 24 hours |
| API responses | None (MVP) | - |

---

## 9. Monitoring & Observability

### 9.1 Logging Stack

```
Application Logs ──▶ Laravel Log ──▶ Daily Files ──▶ Log Rotation
                         │
                         ▼
                    Sentry (Errors)
```

### 9.2 Key Metrics to Monitor

| Category | Metric | Alert Threshold |
|----------|--------|-----------------|
| **API** | Response time | > 1s |
| **API** | Error rate | > 5% |
| **API** | Request count | Baseline |
| **DB** | Query time | > 500ms |
| **DB** | Connections | > 80% |
| **Server** | CPU usage | > 80% |
| **Server** | Memory usage | > 85% |
| **Server** | Disk usage | > 90% |
| **Business** | Orders/hour | < 5 (low) |
| **Business** | Payment failures | > 10% |

### 9.3 Health Checks

```
GET /api/health
{
  "status": "healthy",
  "database": "connected",
  "redis": "connected",
  "queue": "running",
  "timestamp": "2026-01-20T10:00:00Z"
}
```

---

## 10. Disaster Recovery

### 10.1 Backup Strategy

| Data | Frequency | Retention | Location |
|------|-----------|-----------|----------|
| MySQL | Daily | 30 days | Off-site S3 |
| Files | Daily | 30 days | Off-site S3 |
| Code | On push | Infinite | GitHub |

### 10.2 Recovery Procedures

| Scenario | RTO | RPO | Procedure |
|----------|-----|-----|-----------|
| App crash | 15 min | 0 | Supervisor restart |
| Server failure | 1 hour | 24 hours | Restore from snapshot |
| DB corruption | 2 hours | 24 hours | Restore from backup |
| Total loss | 4 hours | 24 hours | Full rebuild + restore |

---

## 11. Development Environment

### 11.1 Local Setup Requirements

```bash
# Required Software
- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.x
- Redis 6+
- Flutter 3.x
- Android Studio / Xcode

# Laravel Setup
git clone git@github.com:afriklabprojet/ouagachap-api.git
cd ouagachap-api
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve

# Flutter Setup
git clone git@github.com:afriklabprojet/ouagachap-mobile.git
cd ouagachap-mobile
flutter pub get
flutter run
```

### 11.2 Environment Variables

```env
# Application
APP_NAME="OUAGA CHAP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.ouagachap.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ouagachap
DB_USERNAME=ouagachap
DB_PASSWORD=secret

# Sanctum
SANCTUM_STATEFUL_DOMAINS=ouagachap.com

# Google Maps
GOOGLE_MAPS_API_KEY=your_key_here

# SMS (Twilio)
TWILIO_SID=your_sid
TWILIO_TOKEN=your_token
TWILIO_FROM=+1234567890

# Firebase (Push Notifications)
FCM_SERVER_KEY=your_key_here

# File Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=fra1
AWS_BUCKET=ouagachap-files
```

---

*Document maintenu par l'équipe technique - Dernière mise à jour: Janvier 2026*
