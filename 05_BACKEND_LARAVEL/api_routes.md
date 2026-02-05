# 🔌 API Routes Documentation - OUAGA CHAP

> Complete REST API specification for the urban delivery platform

---

## 1. API Overview

### Base URL
```
Production: https://api.ouagachap.com/api/v1
Development: http://localhost:8000/api/v1
```

### Headers
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}  # Required for authenticated routes
```

### Response Format
```json
// Success
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}

// Error
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": { ... }
  }
}

// Paginated
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

---

## 2. Authentication Endpoints

### 2.1 Request OTP

**POST** `/auth/otp/request`

Request an OTP code to be sent via SMS.

**Request Body:**
```json
{
  "phone": "+22670123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "OTP envoyé avec succès",
  "data": {
    "phone": "+22670123456",
    "expires_in": 300
  }
}
```

**Errors:**
| Code | Message |
|------|---------|
| 422 | Invalid phone number format |
| 429 | Too many OTP requests. Try again in X minutes |

---

### 2.2 Verify OTP & Login

**POST** `/auth/otp/verify`

Verify OTP and receive authentication token. Creates user if new.

**Request Body:**
```json
{
  "phone": "+22670123456",
  "otp": "123456",
  "fcm_token": "firebase_token_here",  // Optional
  "device_name": "Samsung Galaxy A13"   // Optional
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "token": "1|abc123xyz...",
    "user": {
      "id": 1,
      "phone": "+22670123456",
      "name": null,
      "role": "customer",
      "status": "active",
      "is_new_user": true
    }
  }
}
```

**Errors:**
| Code | Message |
|------|---------|
| 401 | Invalid or expired OTP |
| 422 | Validation error |
| 429 | Too many failed attempts |

---

### 2.3 Logout

**POST** `/auth/logout`

Revoke current authentication token.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

---

### 2.4 Get Current User

**GET** `/auth/me`

Get authenticated user details.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "phone": "+22670123456",
    "name": "Aminata Ouédraogo",
    "role": "customer",
    "status": "active",
    "total_orders": 15,
    "average_rating": 4.8,
    "created_at": "2026-01-15T10:30:00Z"
  }
}
```

---

## 3. Customer Endpoints

### 3.1 Update Profile

**PUT** `/profile`

Update user profile information.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "name": "Aminata Ouédraogo"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profil mis à jour",
  "data": {
    "id": 1,
    "phone": "+22670123456",
    "name": "Aminata Ouédraogo",
    "role": "customer",
    "status": "active"
  }
}
```

---

### 3.2 Calculate Price

**POST** `/orders/calculate`

Calculate estimated price for a delivery.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "pickup_latitude": 12.3714,
  "pickup_longitude": -1.5197,
  "dropoff_latitude": 12.3456,
  "dropoff_longitude": -1.4890
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "distance_km": 4.2,
    "estimated_duration_min": 15,
    "base_price": 500,
    "distance_price": 840,
    "total_price": 1340,
    "currency": "XOF"
  }
}
```

---

### 3.3 Create Order

**POST** `/orders`

Create a new delivery order.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "pickup_address": "Marché Central, Ouagadougou",
  "pickup_latitude": 12.3714,
  "pickup_longitude": -1.5197,
  "pickup_contact_name": "Aminata",
  "pickup_contact_phone": "+22670123456",
  "pickup_instructions": "Boutique bleue à côté de la pharmacie",
  
  "dropoff_address": "Ouaga 2000, Villa 45",
  "dropoff_latitude": 12.3456,
  "dropoff_longitude": -1.4890,
  "dropoff_contact_name": "Fatou",
  "dropoff_contact_phone": "+22675987654",
  "dropoff_instructions": "Appeler à l'arrivée",
  
  "package_description": "3 pagnes",
  "package_size": "small"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Commande créée avec succès",
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "pending",
    "pickup_address": "Marché Central, Ouagadougou",
    "dropoff_address": "Ouaga 2000, Villa 45",
    "distance_km": 4.2,
    "estimated_duration_min": 15,
    "total_price": 1340,
    "currency": "XOF",
    "expires_at": "2026-01-20T10:45:00Z",
    "created_at": "2026-01-20T10:30:00Z"
  }
}
```

**Errors:**
| Code | Message |
|------|---------|
| 422 | Validation error |
| 400 | Service not available in this area |
| 400 | Distance exceeds maximum allowed |

---

### 3.4 Get Order Details

**GET** `/orders/{uuid}`

Get detailed information about an order.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "picked_up",
    
    "pickup": {
      "address": "Marché Central, Ouagadougou",
      "latitude": 12.3714,
      "longitude": -1.5197,
      "contact_name": "Aminata",
      "contact_phone": "+22670123456",
      "instructions": "Boutique bleue à côté de la pharmacie"
    },
    
    "dropoff": {
      "address": "Ouaga 2000, Villa 45",
      "latitude": 12.3456,
      "longitude": -1.4890,
      "contact_name": "Fatou",
      "contact_phone": "+22675987654",
      "instructions": "Appeler à l'arrivée"
    },
    
    "package": {
      "description": "3 pagnes",
      "size": "small",
      "photo_url": null
    },
    
    "pricing": {
      "distance_km": 4.2,
      "estimated_duration_min": 15,
      "total_price": 1340,
      "currency": "XOF"
    },
    
    "courier": {
      "id": 5,
      "name": "Moussa Sawadogo",
      "phone": "+22676543210",
      "average_rating": 4.7,
      "vehicle_type": "moto",
      "vehicle_plate": "BF 1234 A",
      "current_location": {
        "latitude": 12.3600,
        "longitude": -1.5100,
        "updated_at": "2026-01-20T10:35:00Z"
      }
    },
    
    "payment": {
      "status": "completed",
      "provider": "orange_money",
      "completed_at": "2026-01-20T10:32:00Z"
    },
    
    "timeline": [
      {"status": "pending", "at": "2026-01-20T10:30:00Z"},
      {"status": "accepted", "at": "2026-01-20T10:31:00Z"},
      {"status": "picked_up", "at": "2026-01-20T10:40:00Z"}
    ],
    
    "created_at": "2026-01-20T10:30:00Z"
  }
}
```

---

### 3.5 List Customer Orders

**GET** `/orders`

List orders for the authenticated customer.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| status | string | all | Filter by status |
| page | int | 1 | Page number |
| per_page | int | 15 | Items per page |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "status": "delivered",
      "pickup_address": "Marché Central",
      "dropoff_address": "Ouaga 2000",
      "total_price": 1340,
      "courier_name": "Moussa S.",
      "created_at": "2026-01-20T10:30:00Z",
      "delivered_at": "2026-01-20T11:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 23,
    "last_page": 2
  }
}
```

---

### 3.6 Cancel Order (Customer)

**POST** `/orders/{uuid}/cancel`

Cancel an order (only before courier picks up).

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "reason": "Je n'ai plus besoin de la livraison"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Commande annulée",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "cancelled",
    "refund_amount": 1340,
    "refund_status": "pending"
  }
}
```

**Errors:**
| Code | Message |
|------|---------|
| 400 | Cannot cancel order after pickup |
| 404 | Order not found |

---

### 3.7 Rate Courier

**POST** `/orders/{uuid}/rate`

Rate the courier after delivery.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "score": 5,
  "comment": "Très rapide et professionnel",
  "was_on_time": true,
  "was_professional": true,
  "package_condition_ok": true
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Merci pour votre évaluation",
  "data": {
    "score": 5,
    "comment": "Très rapide et professionnel"
  }
}
```

---

## 4. Courier Endpoints

### 4.1 Register as Courier

**POST** `/courier/register`

Register as a courier (submit for verification).

**Headers:** `Authorization: Bearer {token}`

**Request Body (multipart/form-data):**
```
name: Moussa Sawadogo
vehicle_type: moto
vehicle_plate: BF 1234 A
vehicle_model: Jakarta 110cc
cni: [file]
driver_license: [file]
vehicle_registration: [file]
profile_photo: [file]
```

**Response (201):**
```json
{
  "success": true,
  "message": "Inscription soumise. Vérification en cours.",
  "data": {
    "user_id": 5,
    "status": "pending",
    "documents_submitted": 4
  }
}
```

---

### 4.2 Get Courier Status

**GET** `/courier/status`

Get courier verification and availability status.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "status": "active",
    "is_available": true,
    "documents": [
      {"type": "cni", "status": "approved"},
      {"type": "driver_license", "status": "approved"},
      {"type": "vehicle_registration", "status": "approved"},
      {"type": "profile_photo", "status": "approved"}
    ],
    "can_accept_orders": true,
    "rejection_reason": null
  }
}
```

---

### 4.3 Toggle Availability

**POST** `/courier/availability`

Set courier online/offline status.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "is_available": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Vous êtes maintenant en ligne",
  "data": {
    "is_available": true
  }
}
```

---

### 4.4 Update Location

**POST** `/courier/location`

Update courier's current location.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "latitude": 12.3714,
  "longitude": -1.5197,
  "accuracy": 10.5,
  "heading": 45.0,
  "speed": 25.5,
  "order_id": 1
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "recorded_at": "2026-01-20T10:30:00Z"
  }
}
```

---

### 4.5 Get Available Orders

**GET** `/courier/orders/available`

List orders available for the courier to accept.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| latitude | float | required | Courier's current latitude |
| longitude | float | required | Courier's current longitude |
| radius_km | float | 5 | Search radius in km |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "pickup": {
        "address": "Marché Central",
        "latitude": 12.3714,
        "longitude": -1.5197,
        "distance_from_courier_km": 0.8
      },
      "dropoff": {
        "address": "Ouaga 2000",
        "latitude": 12.3456,
        "longitude": -1.4890
      },
      "total_price": 1340,
      "courier_earnings": 1139,
      "distance_km": 4.2,
      "estimated_duration_min": 15,
      "expires_at": "2026-01-20T10:45:00Z",
      "created_at": "2026-01-20T10:30:00Z"
    }
  ]
}
```

---

### 4.6 Accept Order

**POST** `/courier/orders/{uuid}/accept`

Accept an available order.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Commande acceptée",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "accepted",
    "pickup": {
      "address": "Marché Central, Ouagadougou",
      "latitude": 12.3714,
      "longitude": -1.5197,
      "contact_name": "Aminata",
      "contact_phone": "+22670123456",
      "instructions": "Boutique bleue"
    },
    "customer": {
      "name": "Aminata O.",
      "phone": "+22670123456"
    }
  }
}
```

**Errors:**
| Code | Message |
|------|---------|
| 400 | Order already accepted by another courier |
| 400 | Order has expired |
| 403 | You already have an active order |

---

### 4.7 Update Order Status

**POST** `/courier/orders/{uuid}/status`

Update order status (pickup, deliver).

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "status": "picked_up",
  "latitude": 12.3714,
  "longitude": -1.5197,
  "photo": "[base64 or file]"  // Optional, for pickup confirmation
}
```

**Allowed Status Transitions:**
- `accepted` → `picked_up`
- `picked_up` → `delivered`

**Response (200):**
```json
{
  "success": true,
  "message": "Colis récupéré",
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "picked_up",
    "picked_up_at": "2026-01-20T10:40:00Z",
    "dropoff": {
      "address": "Ouaga 2000, Villa 45",
      "latitude": 12.3456,
      "longitude": -1.4890,
      "contact_name": "Fatou",
      "contact_phone": "+22675987654"
    }
  }
}
```

---

### 4.8 Cancel Order (Courier)

**POST** `/courier/orders/{uuid}/cancel`

Cancel an accepted order (with penalty).

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "reason": "Panne de moto"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Commande annulée. La commande sera réattribuée.",
  "data": {
    "cancellation_count": 2,
    "warning": "Attention: 3 annulations entraîneront une suspension temporaire"
  }
}
```

---

### 4.9 Get Active Order

**GET** `/courier/orders/active`

Get the courier's current active order.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "accepted",
    "pickup": { ... },
    "dropoff": { ... },
    "customer": { ... },
    "courier_earnings": 1139,
    "accepted_at": "2026-01-20T10:31:00Z"
  }
}
```

**Response (204 - No active order):**
```json
{
  "success": true,
  "data": null
}
```

---

### 4.10 Get Courier Orders History

**GET** `/courier/orders`

List courier's completed orders.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| status | string | all | Filter by status |
| from_date | date | - | Start date |
| to_date | date | - | End date |
| page | int | 1 | Page number |

**Response (200):**
```json
{
  "success": true,
  "data": [ ... ],
  "meta": { ... }
}
```

---

### 4.11 Get Earnings

**GET** `/courier/earnings`

Get courier earnings summary.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| period | string | today | today, week, month, all |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "period": "week",
    "total_earnings": 45000,
    "total_orders": 35,
    "average_per_order": 1285,
    "wallet_balance": 12000,
    "currency": "XOF",
    "daily_breakdown": [
      {"date": "2026-01-20", "orders": 8, "earnings": 10500},
      {"date": "2026-01-19", "orders": 6, "earnings": 7800},
      ...
    ]
  }
}
```

---

### 4.12 Request Withdrawal

**POST** `/courier/withdrawals`

Request a withdrawal to Mobile Money.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "amount": 10000,
  "provider": "orange_money",
  "phone": "+22670123456"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Demande de retrait enregistrée",
  "data": {
    "id": 1,
    "amount": 10000,
    "provider": "orange_money",
    "phone": "+22670123456",
    "status": "pending",
    "new_wallet_balance": 2000,
    "requested_at": "2026-01-20T10:30:00Z"
  }
}
```

**Errors:**
| Code | Message |
|------|---------|
| 400 | Insufficient balance |
| 400 | Amount below minimum (1000 FCFA) |
| 400 | You already have a pending withdrawal |

---

### 4.13 Get Withdrawal History

**GET** `/courier/withdrawals`

List courier's withdrawal history.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "amount": 10000,
      "provider": "orange_money",
      "phone": "+22670123456",
      "status": "completed",
      "transaction_id": "TXN123456",
      "requested_at": "2026-01-19T10:00:00Z",
      "processed_at": "2026-01-19T10:05:00Z"
    }
  ]
}
```

---

## 5. Payment Endpoints

### 5.1 Initialize Payment

**POST** `/payments`

Initialize a Mobile Money payment for an order.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "order_uuid": "550e8400-e29b-41d4-a716-446655440000",
  "provider": "orange_money",
  "phone": "+22670123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Paiement initié",
  "data": {
    "payment_id": 1,
    "status": "pending",
    "amount": 1340,
    "provider": "orange_money",
    "instructions": "Composez *144*4*6*MONTANT# pour confirmer le paiement",
    "ussd_code": "*144*4*6*1340#",
    "expires_in": 300
  }
}
```

---

### 5.2 Confirm Payment (Mock)

**POST** `/payments/{id}/confirm`

Simulate payment confirmation (Mock only).

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "pin": "1234"  // Mock PIN
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Paiement confirmé",
  "data": {
    "payment_id": 1,
    "status": "completed",
    "transaction_id": "MOCK_TXN_123456",
    "completed_at": "2026-01-20T10:32:00Z"
  }
}
```

---

### 5.3 Get Payment Status

**GET** `/payments/{id}`

Check payment status.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "amount": 1340,
    "provider": "orange_money",
    "status": "completed",
    "transaction_id": "TXN123456",
    "initiated_at": "2026-01-20T10:30:00Z",
    "completed_at": "2026-01-20T10:32:00Z"
  }
}
```

---

## 6. Tracking Endpoints

### 6.1 Get Order Tracking (Public)

**GET** `/tracking/{uuid}`

Get real-time tracking info (no auth required for recipients).

**Response (200):**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "picked_up",
    "status_label": "En route vers la destination",
    
    "pickup": {
      "address": "Marché Central",
      "latitude": 12.3714,
      "longitude": -1.5197
    },
    
    "dropoff": {
      "address": "Ouaga 2000",
      "latitude": 12.3456,
      "longitude": -1.4890
    },
    
    "courier": {
      "name": "Moussa S.",
      "phone": "+22676543210",
      "vehicle": "Moto - BF 1234 A",
      "rating": 4.7,
      "location": {
        "latitude": 12.3600,
        "longitude": -1.5100,
        "updated_at": "2026-01-20T10:45:00Z"
      }
    },
    
    "eta_minutes": 8,
    
    "timeline": [
      {"status": "pending", "label": "Commande créée", "at": "2026-01-20T10:30:00Z"},
      {"status": "accepted", "label": "Coursier assigné", "at": "2026-01-20T10:31:00Z"},
      {"status": "picked_up", "label": "Colis récupéré", "at": "2026-01-20T10:40:00Z"}
    ]
  }
}
```

---

## 7. Utility Endpoints

### 7.1 Geocode Address

**GET** `/geocode`

Convert address to coordinates.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| address | string | Address to geocode |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "formatted_address": "Marché Central, Avenue de la Nation, Ouagadougou",
    "latitude": 12.3714,
    "longitude": -1.5197,
    "place_id": "ChIJ..."
  }
}
```

---

### 7.2 Reverse Geocode

**GET** `/reverse-geocode`

Convert coordinates to address.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| latitude | float | Latitude |
| longitude | float | Longitude |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "formatted_address": "Marché Central, Avenue de la Nation, Ouagadougou",
    "components": {
      "street": "Avenue de la Nation",
      "neighborhood": "Centre-ville",
      "city": "Ouagadougou"
    }
  }
}
```

---

### 7.3 Get App Settings

**GET** `/settings`

Get public app configuration.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "pricing": {
      "base_fare": 500,
      "per_km_rate": 200,
      "min_price": 500,
      "max_price": 10000,
      "currency": "XOF"
    },
    "limits": {
      "max_distance_km": 20
    },
    "operating_hours": {
      "start": "07:00",
      "end": "21:00",
      "timezone": "Africa/Ouagadougou"
    },
    "support": {
      "phone": "+22670000000",
      "whatsapp": "+22670000000"
    },
    "app_version": {
      "min_android": "1.0.0",
      "min_ios": "1.0.0",
      "current": "1.0.0"
    }
  }
}
```

---

### 7.4 Check Service Availability

**GET** `/zones/check`

Check if location is within service area.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| latitude | float | Latitude |
| longitude | float | Longitude |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "available": true,
    "zone": {
      "name": "Centre-ville",
      "slug": "centre-ville"
    }
  }
}
```

---

### 7.5 Health Check

**GET** `/health`

API health check endpoint.

**Response (200):**
```json
{
  "status": "healthy",
  "services": {
    "database": "connected",
    "redis": "connected",
    "queue": "running"
  },
  "timestamp": "2026-01-20T10:00:00Z"
}
```

---

## 8. Notification Endpoints

### 8.1 Get Notifications

**GET** `/notifications`

Get user's notifications.

**Headers:** `Authorization: Bearer {token}`

**Query Parameters:**
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| unread_only | bool | false | Only unread |
| page | int | 1 | Page number |

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "order_accepted",
      "title": "Coursier en route",
      "body": "Moussa arrive pour récupérer votre colis",
      "data": {"order_uuid": "550e8400..."},
      "read_at": null,
      "created_at": "2026-01-20T10:31:00Z"
    }
  ],
  "meta": {
    "unread_count": 3
  }
}
```

---

### 8.2 Mark Notification as Read

**POST** `/notifications/{id}/read`

Mark a notification as read.

**Headers:** `Authorization: Bearer {token}`

**Response (200):**
```json
{
  "success": true,
  "message": "Notification lue"
}
```

---

### 8.3 Update FCM Token

**PUT** `/notifications/token`

Update push notification token.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "fcm_token": "new_firebase_token_here"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Token mis à jour"
}
```

---

## 9. Error Codes Reference

| HTTP Code | Error Code | Description |
|-----------|------------|-------------|
| 400 | BAD_REQUEST | Invalid request |
| 401 | UNAUTHENTICATED | Invalid or missing token |
| 403 | FORBIDDEN | Not authorized for this action |
| 404 | NOT_FOUND | Resource not found |
| 422 | VALIDATION_ERROR | Input validation failed |
| 429 | TOO_MANY_REQUESTS | Rate limit exceeded |
| 500 | SERVER_ERROR | Internal server error |

### Domain-Specific Errors

| Error Code | Description |
|------------|-------------|
| ORDER_EXPIRED | Order has expired |
| ORDER_ALREADY_ACCEPTED | Order accepted by another courier |
| ORDER_NOT_CANCELLABLE | Cannot cancel at this stage |
| INSUFFICIENT_BALANCE | Not enough wallet balance |
| ZONE_NOT_COVERED | Location outside service area |
| COURIER_NOT_APPROVED | Courier account not yet approved |
| COURIER_SUSPENDED | Courier account suspended |
| PAYMENT_FAILED | Payment transaction failed |

---

## 10. Rate Limits

| Endpoint Category | Limit | Window |
|-------------------|-------|--------|
| OTP Request | 3 requests | 15 minutes |
| General API | 60 requests | 1 minute |
| Location Updates | 120 requests | 1 minute |
| Order Creation | 10 requests | 1 minute |

---

## 11. Webhook Endpoints (Internal)

### Payment Callback

**POST** `/webhooks/payment/{provider}`

Callback URL for payment providers.

```json
{
  "transaction_id": "TXN123456",
  "status": "completed",
  "amount": 1340,
  "phone": "+22670123456",
  "reference": "ORDER_UUID",
  "timestamp": "2026-01-20T10:32:00Z",
  "signature": "hmac_signature_here"
}
```

---

*Document maintenu par l'équipe technique - Dernière mise à jour: Janvier 2026*
