# 📋 Product Requirements Document (PRD)
## OUAGA CHAP - Urban Delivery Application

> **Version:** 1.0 MVP  
> **Date:** January 2026  
> **Author:** Product Team  
> **Status:** Approved for Development

---

## 1. Executive Summary

### 1.1 Product Overview
OUAGA CHAP is a mobile-first urban delivery platform connecting customers who need items delivered with verified couriers (coursiers) in Ouagadougou, Burkina Faso. The platform enables on-demand delivery with real-time tracking and Mobile Money payments.

### 1.2 Business Objectives
| Objective | Target (MVP) | Measurement |
|-----------|--------------|-------------|
| Launch functional MVP | Q1 2026 | Apps published on Play Store |
| Onboard couriers | 30 active | Verified and active couriers |
| Daily orders | 20/day | Average daily completed orders |
| Customer satisfaction | 4.0/5 | Average rating |
| Revenue | 300K FCFA/month | Platform commission (15%) |

### 1.3 Success Criteria
- [ ] Customer can create order in < 2 minutes
- [ ] Courier accepts order in < 3 minutes (average)
- [ ] Delivery completed in < 60 minutes (average)
- [ ] Payment processed successfully > 95% of time
- [ ] App crash rate < 1%

---

## 2. User Roles & Permissions

### 2.1 Role Matrix

| Feature | Customer | Courier | Admin |
|---------|----------|---------|-------|
| Register/Login | ✅ | ✅ | ✅ |
| Create Order | ✅ | ❌ | ❌ |
| View Available Orders | ❌ | ✅ | ✅ |
| Accept Order | ❌ | ✅ | ❌ |
| Track Order | ✅ | ✅ | ✅ |
| Update Order Status | ❌ | ✅ | ✅ |
| Make Payment | ✅ | ❌ | ❌ |
| Withdraw Earnings | ❌ | ✅ | ❌ |
| Rate & Review | ✅ | ✅ | ❌ |
| Manage Users | ❌ | ❌ | ✅ |
| View Reports | ❌ | ❌ | ✅ |
| Handle Disputes | ❌ | ❌ | ✅ |

### 2.2 Authentication Requirements
| Requirement | Implementation |
|-------------|----------------|
| Method | Phone number + OTP |
| OTP validity | 5 minutes |
| OTP length | 6 digits |
| Session duration | 30 days |
| Token type | Bearer (Laravel Sanctum) |

---

## 3. Functional Requirements

### 3.1 Customer App Features

#### FR-C01: Registration & Authentication
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-C01.1 | User registers with phone number | P0 |
| FR-C01.2 | System sends OTP via SMS | P0 |
| FR-C01.3 | User verifies OTP to complete registration | P0 |
| FR-C01.4 | User can add name after registration | P0 |
| FR-C01.5 | User can login with phone + OTP | P0 |
| FR-C01.6 | System maintains session for 30 days | P1 |

#### FR-C02: Order Creation
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-C02.1 | User enters pickup address (text or map pin) | P0 |
| FR-C02.2 | User enters dropoff address (text or map pin) | P0 |
| FR-C02.3 | User adds pickup contact name & phone | P0 |
| FR-C02.4 | User adds dropoff contact name & phone | P0 |
| FR-C02.5 | User adds package description | P1 |
| FR-C02.6 | System calculates estimated price | P0 |
| FR-C02.7 | System shows estimated delivery time | P1 |
| FR-C02.8 | User confirms order | P0 |
| FR-C02.9 | System generates unique order UUID | P0 |

#### FR-C03: Order Tracking
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-C03.1 | User sees order status (pending, accepted, picked_up, delivered, cancelled) | P0 |
| FR-C03.2 | User sees courier location on map (when assigned) | P0 |
| FR-C03.3 | User receives push notification on status change | P1 |
| FR-C03.4 | User can call courier directly from app | P0 |
| FR-C03.5 | User can share tracking link with recipient | P1 |

#### FR-C04: Payment
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-C04.1 | User pays via Mobile Money (Orange/Moov) | P0 |
| FR-C04.2 | Payment is processed before courier pickup | P0 |
| FR-C04.3 | User receives payment confirmation | P0 |
| FR-C04.4 | User can request refund for cancelled orders | P1 |

#### FR-C05: Order History & Rating
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-C05.1 | User views list of past orders | P0 |
| FR-C05.2 | User views order details | P0 |
| FR-C05.3 | User rates courier (1-5 stars) after delivery | P0 |
| FR-C05.4 | User can add text review | P2 |

---

### 3.2 Courier App Features

#### FR-R01: Registration & Verification
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-R01.1 | Courier registers with phone number | P0 |
| FR-R01.2 | Courier uploads CNI (ID card) photo | P0 |
| FR-R01.3 | Courier uploads driver's license photo | P0 |
| FR-R01.4 | Courier uploads vehicle registration photo | P0 |
| FR-R01.5 | Courier adds vehicle details (model, plate) | P1 |
| FR-R01.6 | Admin validates documents before activation | P0 |
| FR-R01.7 | Courier receives notification when approved/rejected | P0 |

#### FR-R02: Order Discovery & Acceptance
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-R02.1 | Courier toggles availability (online/offline) | P0 |
| FR-R02.2 | Courier sees available orders near current location | P0 |
| FR-R02.3 | Order shows pickup address, dropoff address, price | P0 |
| FR-R02.4 | Courier can accept order | P0 |
| FR-R02.5 | Courier can decline order | P0 |
| FR-R02.6 | Order auto-expires if not accepted in 5 minutes | P1 |
| FR-R02.7 | Push notification for new orders nearby | P1 |

#### FR-R03: Order Fulfillment
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-R03.1 | Courier sees navigation to pickup location | P0 |
| FR-R03.2 | Courier marks order as "picked up" | P0 |
| FR-R03.3 | Courier takes photo of package at pickup | P1 |
| FR-R03.4 | Courier sees navigation to dropoff location | P0 |
| FR-R03.5 | Courier marks order as "delivered" | P0 |
| FR-R03.6 | Courier can call customer or recipient | P0 |
| FR-R03.7 | Courier can cancel order with reason | P0 |

#### FR-R04: Earnings & Withdrawal
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-R04.1 | Courier sees earnings dashboard (today, week, month) | P0 |
| FR-R04.2 | Courier sees list of completed orders with amounts | P0 |
| FR-R04.3 | Courier requests withdrawal to Mobile Money | P0 |
| FR-R04.4 | Minimum withdrawal amount: 1000 FCFA | P0 |
| FR-R04.5 | Courier sees withdrawal history | P1 |

#### FR-R05: Profile & Rating
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-R05.1 | Courier views their profile | P0 |
| FR-R05.2 | Courier sees their average rating | P0 |
| FR-R05.3 | Courier can rate customer after delivery | P2 |

---

### 3.3 Admin Panel Features (Filament)

#### FR-A01: Dashboard
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-A01.1 | View total orders (today, week, month) | P0 |
| FR-A01.2 | View total revenue (today, week, month) | P0 |
| FR-A01.3 | View active couriers count | P0 |
| FR-A01.4 | View pending courier applications | P0 |
| FR-A01.5 | View orders by status chart | P1 |
| FR-A01.6 | View revenue trend chart | P1 |

#### FR-A02: User Management
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-A02.1 | List all customers with search/filter | P0 |
| FR-A02.2 | View customer details and order history | P0 |
| FR-A02.3 | Suspend/activate customer account | P0 |
| FR-A02.4 | List all couriers with search/filter | P0 |
| FR-A02.5 | View courier details, documents, earnings | P0 |
| FR-A02.6 | Approve/reject courier application | P0 |
| FR-A02.7 | Suspend/activate courier account | P0 |

#### FR-A03: Order Management
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-A03.1 | List all orders with search/filter | P0 |
| FR-A03.2 | Filter by status, date, customer, courier | P0 |
| FR-A03.3 | View order details (timeline, map, payment) | P0 |
| FR-A03.4 | Cancel order with reason | P0 |
| FR-A03.5 | Reassign order to different courier | P1 |
| FR-A03.6 | Process refund | P1 |

#### FR-A04: Payment Management
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-A04.1 | View all transactions | P0 |
| FR-A04.2 | View pending courier withdrawals | P0 |
| FR-A04.3 | Approve/process withdrawal | P0 |
| FR-A04.4 | View payment reconciliation | P1 |

#### FR-A05: Reports & Export
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-A05.1 | Export orders to CSV | P1 |
| FR-A05.2 | Export transactions to CSV | P1 |
| FR-A05.3 | Export couriers to CSV | P1 |

---

## 4. Non-Functional Requirements

### 4.1 Performance
| Requirement | Target |
|-------------|--------|
| API response time | < 500ms (95th percentile) |
| App launch time | < 3 seconds |
| Map load time | < 2 seconds |
| Order creation time | < 2 seconds |
| Location update frequency | Every 10 seconds |

### 4.2 Scalability
| Requirement | Target |
|-------------|--------|
| Concurrent users | 500 |
| Orders per day | 1,000 |
| Database size | 10GB |

### 4.3 Availability
| Requirement | Target |
|-------------|--------|
| Uptime | 99.5% |
| Planned maintenance window | Sunday 2-4 AM |
| Recovery time | < 1 hour |

### 4.4 Security
| Requirement | Implementation |
|-------------|----------------|
| Authentication | Laravel Sanctum (token-based) |
| Password | Not required (OTP-based) |
| API security | HTTPS only, rate limiting |
| Data encryption | At rest and in transit |
| PII protection | Masked phone numbers in logs |

### 4.5 Mobile Constraints (Africa Context)
| Constraint | Solution |
|------------|----------|
| Low bandwidth (2G/3G) | Compressed images, minimal payloads |
| Network drops | Offline queue, retry logic |
| Limited storage | App size < 25MB |
| Battery drain | Efficient location polling |
| Data costs | Minimize background data |

### 4.6 Localization
| Requirement | Implementation |
|-------------|----------------|
| Primary language | French |
| Currency | FCFA (XOF) |
| Date format | DD/MM/YYYY |
| Time format | 24h |
| Phone format | +226 XX XX XX XX |

---

## 5. Business Rules

### 5.1 Pricing Rules
```
PRICE CALCULATION:
- Base fare: 500 FCFA
- Per km rate: 200 FCFA/km
- Minimum price: 500 FCFA
- Maximum price: 10,000 FCFA

FORMULA: price = max(500, min(10000, 500 + (distance_km * 200)))

PLATFORM COMMISSION: 15% of order price
COURIER EARNINGS: 85% of order price
```

### 5.2 Order Rules
| Rule | Description |
|------|-------------|
| Order expiry | Unaccepted orders expire after 15 minutes |
| Cancellation (customer) | Free if courier not assigned, 50% fee after |
| Cancellation (courier) | Affects rating, 3 cancellations = temporary suspension |
| Maximum distance | 20 km (within Ouagadougou) |
| Operating hours | 7:00 - 21:00 daily |

### 5.3 Courier Rules
| Rule | Description |
|------|-------------|
| Minimum rating | 3.5/5 to remain active |
| Maximum concurrent orders | 1 (MVP) |
| Withdrawal minimum | 1,000 FCFA |
| Withdrawal frequency | Once per day |
| Document validity | Must be renewed annually |

### 5.4 Payment Rules
| Rule | Description |
|------|-------------|
| Payment timing | Pre-payment required before pickup |
| Refund policy | Full refund if cancelled before pickup |
| Courier payout | Available immediately after delivery confirmation |
| Platform hold | 24h before transferring to courier (fraud prevention) |

---

## 6. Data Requirements

### 6.1 Core Entities
| Entity | Key Attributes |
|--------|----------------|
| User | id, phone, name, role, status |
| Order | uuid, customer_id, courier_id, status, price, addresses |
| Payment | id, order_id, amount, method, status |
| Rating | id, order_id, from_user, to_user, score |
| CourierDocument | id, courier_id, type, file_url, status |
| Withdrawal | id, courier_id, amount, status |

### 6.2 Order Status Flow
```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ PENDING  │────▶│ ACCEPTED │────▶│PICKED_UP │────▶│ DELIVERED│
└──────────┘     └──────────┘     └──────────┘     └──────────┘
      │                │                │
      ▼                ▼                ▼
┌──────────┐     ┌──────────┐     ┌──────────┐
│ EXPIRED  │     │CANCELLED │     │CANCELLED │
└──────────┘     └──────────┘     └──────────┘
```

### 6.3 Payment Status Flow
```
┌──────────┐     ┌──────────┐     ┌──────────┐
│ PENDING  │────▶│ COMPLETED│────▶│ DISBURSED│
└──────────┘     └──────────┘     └──────────┘
      │
      ▼
┌──────────┐
│  FAILED  │
└──────────┘
```

---

## 7. Integration Requirements

### 7.1 Google Maps API
| Feature | API Used |
|---------|----------|
| Address autocomplete | Places API |
| Distance calculation | Distance Matrix API |
| Map display | Maps SDK for Android/iOS |
| Directions | Directions API |
| Geocoding | Geocoding API |

### 7.2 SMS Gateway
| Provider | Purpose |
|----------|---------|
| Primary | Twilio (or local provider) |
| Usage | OTP delivery |
| Fallback | WhatsApp Business API (Phase 2) |

### 7.3 Mobile Money (Mock for MVP)
| Provider | Status |
|----------|--------|
| Orange Money | Mock implementation |
| Moov Money | Mock implementation |
| Real integration | Phase 2 |

### 7.4 Push Notifications
| Platform | Service |
|----------|---------|
| Android | Firebase Cloud Messaging (FCM) |
| iOS | Apple Push Notification Service (APNs) |

---

## 8. UI/UX Requirements

### 8.1 Design Principles
1. **Simple & Clear** - Minimal text, clear icons
2. **Fast** - Load quickly on slow networks
3. **Accessible** - Large touch targets, readable fonts
4. **Trustworthy** - Clear feedback, no hidden costs

### 8.2 Key Screens

#### Customer App
| Screen | Purpose |
|--------|---------|
| Splash | Brand, loading |
| Login | Phone + OTP entry |
| Home | Create new order CTA |
| Order Creation | Address entry, price, confirm |
| Order Tracking | Map, status, courier info |
| Order History | List of past orders |
| Profile | Name, phone, settings |

#### Courier App
| Screen | Purpose |
|--------|---------|
| Splash | Brand, loading |
| Login | Phone + OTP entry |
| Registration | Document upload |
| Home | Available orders list |
| Active Order | Navigation, status buttons |
| Earnings | Dashboard, withdrawal |
| Profile | Info, rating, settings |

### 8.3 Color Palette
| Color | Hex | Usage |
|-------|-----|-------|
| Primary | #FF6B00 | Buttons, highlights |
| Secondary | #1A1A2E | Text, headers |
| Success | #4CAF50 | Completed states |
| Warning | #FFC107 | Pending states |
| Error | #F44336 | Errors, cancelled |
| Background | #F5F5F5 | Screen backgrounds |

---

## 9. Testing Requirements

### 9.1 Test Coverage Targets
| Type | Target |
|------|--------|
| Unit tests | 70% |
| Integration tests | 50% |
| E2E tests | Critical paths |

### 9.2 Critical Test Scenarios
| Scenario | Priority |
|----------|----------|
| User registration (OTP flow) | P0 |
| Order creation & payment | P0 |
| Courier accepts & completes order | P0 |
| Real-time tracking updates | P0 |
| Courier withdrawal | P0 |
| Admin approves courier | P0 |

### 9.3 Device Testing Matrix
| Device | OS Version | Priority |
|--------|------------|----------|
| Samsung Galaxy A13 | Android 12+ | P0 |
| Tecno Spark series | Android 11+ | P0 |
| Infinix Hot series | Android 11+ | P0 |
| iPhone SE | iOS 15+ | P1 |

---

## 10. Launch Checklist

### Pre-Launch
- [ ] All P0 features implemented
- [ ] Security audit completed
- [ ] Load testing passed
- [ ] 10 beta couriers onboarded
- [ ] 50 beta customers tested
- [ ] Play Store listing ready
- [ ] Customer support channel ready

### Launch Day
- [ ] Monitor error rates
- [ ] Monitor API latency
- [ ] Support team on standby
- [ ] Rollback plan ready

### Post-Launch (Week 1)
- [ ] Daily bug triage
- [ ] User feedback collection
- [ ] KPI monitoring
- [ ] Hotfix deployment if needed

---

## 11. Appendix

### A. Glossary
| Term | Definition |
|------|------------|
| Coursier | Courier/delivery person |
| Pickup | Location where package is collected |
| Dropoff | Location where package is delivered |
| OTP | One-Time Password |
| FCFA | West African CFA Franc (currency) |
| CNI | Carte Nationale d'Identité (ID card) |

### B. References
- Vision Document: `01_PRODUCT/vision.md`
- Personas: `01_PRODUCT/personas.md`
- Technical Architecture: `03_TECH/architecture.md`
- API Documentation: `05_BACKEND_LARAVEL/api_routes.md`
- Database Schema: `04_DATABASE/schema.sql`

---

*Document approved for MVP development - January 2026*
