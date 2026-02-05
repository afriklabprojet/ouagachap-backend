# 🚀 OUAGA CHAP - Urban Delivery Application

> **Version:** MVP 1.0  
> **Last Updated:** January 2026  
> **Target Market:** Ouagadougou, Burkina Faso (Africa)

---

## 📋 Project Overview

**OUAGA CHAP** is a mobile-first urban delivery application designed for the African context. The platform connects customers who need items delivered with local couriers (coursiers) who fulfill deliveries using motorcycles.

### Key Value Proposition
- **Fast delivery** within Ouagadougou city limits
- **Mobile Money integration** for cashless payments
- **Real-time tracking** via Google Maps
- **Simple UX** optimized for low-bandwidth conditions

---

## 🏗️ Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Backend API** | Laravel | 11.x |
| **Admin Panel** | Filament | 3.x |
| **Mobile App** | Flutter | 3.x |
| **Database** | MySQL | 8.x |
| **Maps** | Google Maps API | Latest |
| **Payment** | Mobile Money (Mock) | MVP |
| **Authentication** | Laravel Sanctum | Latest |

---

## 📁 Folder Structure

```
OUAGA_CHAP/
├── 00_README_START_HERE.md    # You are here
├── 01_PRODUCT/                 # Product documentation
│   ├── prd.md                  # Product Requirements Document
│   ├── vision.md               # Product vision & goals
│   └── personas.md             # User personas
├── 03_TECH/                    # Technical documentation
│   ├── architecture.md         # System architecture
│   └── stack.md                # Technology stack details
├── 04_DATABASE/                # Database documentation
│   └── schema.sql              # Complete MySQL schema
├── 05_BACKEND_LARAVEL/         # Backend documentation
│   └── api_routes.md           # REST API endpoints
├── 06_PAYMENT/                 # Payment documentation
│   └── mobile_money_mock.md    # Mobile Money mock logic
├── 07_FLUTTER/                 # Mobile app documentation
│   └── architecture.md         # Flutter clean architecture
└── 10_PROMPTS/                 # AI coding prompts
    └── copilot_backend.txt     # Backend development prompts
```

---

## 👥 User Roles

| Role | Description | App |
|------|-------------|-----|
| **Customer (Client)** | Orders deliveries | Flutter Mobile |
| **Courier (Coursier)** | Fulfills deliveries | Flutter Mobile |
| **Admin** | Manages platform | Filament Web Panel |

---

## 🎯 MVP Features

### Customer Features
- [ ] Phone number authentication (OTP)
- [ ] Create delivery order (pickup → dropoff)
- [ ] Real-time order tracking
- [ ] Mobile Money payment
- [ ] Order history
- [ ] Rate courier

### Courier Features
- [ ] Phone number authentication (OTP)
- [ ] View available orders nearby
- [ ] Accept/reject orders
- [ ] Navigation to pickup/dropoff
- [ ] Mark order status updates
- [ ] Earnings dashboard

### Admin Features
- [ ] Dashboard with KPIs
- [ ] User management (customers, couriers)
- [ ] Order management
- [ ] Courier approval/verification
- [ ] Payment reconciliation
- [ ] Zone management

---

## 🚦 Getting Started

### For Product Team
1. Read `01_PRODUCT/vision.md` for project goals
2. Review `01_PRODUCT/prd.md` for detailed requirements
3. Understand users via `01_PRODUCT/personas.md`

### For Backend Developers
1. Study `03_TECH/architecture.md` for system design
2. Implement database from `04_DATABASE/schema.sql`
3. Build APIs following `05_BACKEND_LARAVEL/api_routes.md`
4. Use prompts from `10_PROMPTS/copilot_backend.txt`

### For Mobile Developers
1. Follow `07_FLUTTER/architecture.md` for app structure
2. Integrate APIs from `05_BACKEND_LARAVEL/api_routes.md`
3. Implement payment via `06_PAYMENT/mobile_money_mock.md`

### For Admin Panel
1. Build Filament resources based on `04_DATABASE/schema.sql`
2. Follow architecture in `03_TECH/architecture.md`

---

## 🌍 African Context Considerations

| Constraint | Solution |
|------------|----------|
| **Low bandwidth** | Minimal API payloads, image compression |
| **Network instability** | Offline-first patterns, retry logic |
| **Feature phones** | SMS fallback for notifications |
| **Mobile Money** | Primary payment method |
| **Low smartphone storage** | Small app size (~20MB target) |
| **French language** | French as primary UI language |

---

## 📞 Contact

- **Project Lead:** [To be defined]
- **Tech Lead:** [To be defined]
- **Repository:** github.com/afriklabprojet/bnh

---

## 📜 License

Proprietary - Afriklab © 2026
