# API Reference

Base URL: `http://your-domain.com/api/v1`

## Authentification

Toutes les requêtes authentifiées nécessitent le header:
```
Authorization: Bearer {token}
```

---

## 🔐 Auth Endpoints

### Envoyer OTP

```http
POST /auth/otp/send
```

**Body:**
```json
{
    "phone": "+22670123456"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Code OTP envoyé",
    "data": {
        "phone": "+22670123456",
        "expires_in": 300
    }
}
```

### Vérifier OTP

```http
POST /auth/otp/verify
```

**Body:**
```json
{
    "phone": "+22670123456",
    "otp": "123456",
    "app_type": "client"  // ou "courier"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Connexion réussie",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "phone": "+22670123456",
            "role": "customer"
        },
        "token": "1|abc123...",
        "is_new_user": false
    }
}
```

### Inscription Coursier

```http
POST /auth/register/courier
```

**Body (multipart/form-data):**
```
name: "Koné Ibrahim"
phone: "+22670123456"
vehicle_type: "motorcycle"  // motorcycle, bicycle, car
vehicle_plate: "AB-1234-BF"
vehicle_model: "Honda CG 125"
id_card_front: [file]
id_card_back: [file]
driver_license: [file]
```

### Profil utilisateur

```http
GET /profile
```

### Mettre à jour le profil

```http
PUT /profile
```

**Body:**
```json
{
    "name": "Nouveau Nom",
    "email": "email@example.com"
}
```

### Mettre à jour la photo de profil

```http
POST /profile/avatar
```

**Body (multipart/form-data):**
```
avatar: [file]
```

### Mettre à jour le token FCM

```http
PUT /profile/fcm-token
```

**Body:**
```json
{
    "fcm_token": "firebase_token_here"
}
```

### Déconnexion

```http
POST /auth/logout
```

---

## 📦 Orders Endpoints (Client)

### Créer une commande

```http
POST /orders
```

**Body:**
```json
{
    "pickup_address": "123 Rue de la Paix, Ouaga 2000",
    "pickup_latitude": 12.3714,
    "pickup_longitude": -1.5197,
    "delivery_address": "456 Avenue Kwame, Secteur 4",
    "delivery_latitude": 12.3650,
    "delivery_longitude": -1.5300,
    "recipient_name": "Amadou Traoré",
    "recipient_phone": "+22676543210",
    "package_description": "Documents importants",
    "package_size": "small",  // small, medium, large
    "is_fragile": false,
    "notes": "Sonner 2 fois"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Commande créée avec succès",
    "data": {
        "id": "ORD-2026-00001",
        "status": "pending",
        "price": 1500,
        "distance": 3.2,
        "estimated_duration": 15,
        "created_at": "2026-01-29T10:30:00Z"
    }
}
```

### Liste des commandes

```http
GET /orders?status=delivered&page=1&per_page=10
```

### Détails d'une commande

```http
GET /orders/{id}
```

### Annuler une commande

```http
POST /orders/{id}/cancel
```

**Body:**
```json
{
    "reason": "Changement de plan"
}
```

### Noter le coursier

```http
POST /orders/{id}/rate-courier
```

**Body:**
```json
{
    "rating": 5,
    "review": "Excellent service, très rapide!",
    "tags": ["rapide", "professionnel"]
}
```

### Suivi en temps réel

```http
GET /orders/{id}/tracking
```

---

## 🏍️ Courier Endpoints

### Activer/Désactiver disponibilité

```http
PUT /courier/availability
```

**Body:**
```json
{
    "is_available": true
}
```

### Mettre à jour la position

```http
POST /courier/location/update
```

**Body:**
```json
{
    "latitude": 12.3714,
    "longitude": -1.5197
}
```

### Commandes disponibles

```http
GET /courier/available-orders
```

### Accepter une commande

```http
POST /courier/orders/{id}/accept
```

### Livraison active

```http
GET /courier/active-delivery
```

### Mettre à jour le statut

```http
PUT /courier/orders/{id}/status
```

**Body:**
```json
{
    "status": "picked_up"  // accepted, picked_up, delivered
}
```

### Historique des livraisons

```http
GET /courier/deliveries?page=1&per_page=10
```

### Statistiques de gains

```http
GET /courier/earnings/stats
```

**Response:**
```json
{
    "success": true,
    "data": {
        "today": 15000,
        "this_week": 75000,
        "this_month": 280000,
        "total": 1500000,
        "deliveries_count": {
            "today": 8,
            "this_week": 42,
            "this_month": 156
        }
    }
}
```

### Noter le client

```http
POST /courier/orders/{id}/rate-client
```

---

## 💰 Payment Endpoints

### Initier un paiement

```http
POST /payments/initiate
```

**Body:**
```json
{
    "order_id": "ORD-2026-00001",
    "payment_method": "orange_money",  // orange_money, moov_money
    "phone": "+22670123456"
}
```

### Vérifier le statut

```http
GET /payments/{id}/status
```

### Historique des paiements

```http
GET /payments?page=1
```

---

## 👛 Wallet Endpoints (Coursier)

### Solde du portefeuille

```http
GET /wallet/balance
```

### Historique des transactions

```http
GET /wallet/transactions?page=1
```

### Demander un retrait

```http
POST /wallet/withdraw
```

**Body:**
```json
{
    "amount": 50000,
    "payment_method": "orange_money",
    "phone": "+22670123456"
}
```

---

## 🔔 Notifications Endpoints

### Liste des notifications

```http
GET /notifications?page=1
```

### Marquer comme lue

```http
PUT /notifications/{id}/read
```

### Marquer toutes comme lues

```http
PUT /notifications/read-all
```

### Compter les non lues

```http
GET /notifications/unread-count
```

---

## ❓ Support Endpoints

### FAQs

```http
GET /support/faqs
```

### Créer une réclamation

```http
POST /support/complaints
```

**Body:**
```json
{
    "order_id": "ORD-2026-00001",
    "subject": "Colis endommagé",
    "message": "Mon colis est arrivé abîmé..."
}
```

### Mes réclamations

```http
GET /support/complaints
```

### Informations de contact

```http
GET /support/contact
```

---

## 🗺️ Config Endpoints

### Zones de livraison

```http
GET /config/zones
```

### Tarifs

```http
GET /config/pricing
```

### Bannières promotionnelles

```http
GET /config/banners
```

---

## Codes d'erreur HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Non autorisé |
| 404 | Ressource non trouvée |
| 422 | Erreur de validation |
| 429 | Trop de requêtes |
| 500 | Erreur serveur |
