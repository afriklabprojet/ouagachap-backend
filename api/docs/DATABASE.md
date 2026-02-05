# Base de Données

## Vue d'ensemble

OUAGA CHAP utilise MySQL 8.0+ en production et SQLite pour le développement rapide.

## Schéma principal

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│    users     │     │    orders    │     │   payments   │
├──────────────┤     ├──────────────┤     ├──────────────┤
│ id           │◄────│ client_id    │     │ id           │
│ name         │◄────│ courier_id   │────►│ order_id     │
│ phone        │     │ status       │     │ amount       │
│ role         │     │ price        │     │ status       │
│ status       │     │ pickup_*     │     │ method       │
│ ...          │     │ delivery_*   │     │ ...          │
└──────────────┘     └──────────────┘     └──────────────┘
       │                    │
       │                    │
       ▼                    ▼
┌──────────────┐     ┌──────────────┐
│   wallets    │     │   ratings    │
├──────────────┤     ├──────────────┤
│ id           │     │ id           │
│ user_id      │     │ order_id     │
│ balance      │     │ rater_id     │
│ ...          │     │ rated_id     │
└──────────────┘     │ rating       │
                     └──────────────┘
```

## Tables principales

### users

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    role ENUM('customer', 'courier', 'admin') DEFAULT 'customer',
    status ENUM('pending', 'active', 'suspended') DEFAULT 'active',
    
    -- Coursier uniquement
    vehicle_type VARCHAR(50) NULL,
    vehicle_plate VARCHAR(20) NULL,
    vehicle_model VARCHAR(100) NULL,
    is_available BOOLEAN DEFAULT false,
    
    -- Localisation
    current_latitude DECIMAL(10, 8) NULL,
    current_longitude DECIMAL(11, 8) NULL,
    
    -- Notifications
    fcm_token TEXT NULL,
    notification_preferences JSON NULL,
    
    -- Rating
    average_rating DECIMAL(2, 1) DEFAULT 0,
    total_ratings INT DEFAULT 0,
    
    -- Timestamps
    last_seen_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### orders

```sql
CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE NOT NULL,  -- ORD-2026-00001
    
    -- Relations
    client_id BIGINT UNSIGNED NOT NULL,
    courier_id BIGINT UNSIGNED NULL,
    
    -- Statut
    status ENUM('pending', 'accepted', 'picked_up', 'delivered', 'cancelled') DEFAULT 'pending',
    
    -- Collecte
    pickup_address TEXT NOT NULL,
    pickup_latitude DECIMAL(10, 8) NOT NULL,
    pickup_longitude DECIMAL(11, 8) NOT NULL,
    
    -- Livraison
    delivery_address TEXT NOT NULL,
    delivery_latitude DECIMAL(10, 8) NOT NULL,
    delivery_longitude DECIMAL(11, 8) NOT NULL,
    
    -- Destinataire
    recipient_name VARCHAR(255) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    
    -- Colis
    package_description TEXT NULL,
    package_size ENUM('small', 'medium', 'large') DEFAULT 'small',
    is_fragile BOOLEAN DEFAULT false,
    notes TEXT NULL,
    
    -- Tarification
    distance DECIMAL(10, 2) NOT NULL,  -- en km
    price INT NOT NULL,  -- en FCFA
    
    -- Notation
    client_rating TINYINT NULL,  -- Note donnée par le client (1-5)
    client_review TEXT NULL,
    courier_rating TINYINT NULL,  -- Note donnée par le coursier (1-5)
    courier_review TEXT NULL,
    
    -- Timestamps
    accepted_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (courier_id) REFERENCES users(id)
);
```

### payments

```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    
    amount INT NOT NULL,
    method ENUM('orange_money', 'moov_money', 'cash', 'wallet') NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    
    -- Référence externe (Jeko, etc.)
    external_reference VARCHAR(255) NULL,
    transaction_id VARCHAR(255) NULL,
    
    -- Métadonnées
    metadata JSON NULL,
    failure_reason TEXT NULL,
    
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### wallets

```sql
CREATE TABLE wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE NOT NULL,
    balance INT DEFAULT 0,
    total_earned INT DEFAULT 0,
    total_withdrawn INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### wallet_transactions

```sql
CREATE TABLE wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    type ENUM('credit', 'debit') NOT NULL,
    amount INT NOT NULL,
    description TEXT NULL,
    reference_type VARCHAR(50) NULL,  -- order, withdrawal, bonus
    reference_id BIGINT NULL,
    balance_after INT NOT NULL,
    created_at TIMESTAMP,
    
    FOREIGN KEY (wallet_id) REFERENCES wallets(id)
);
```

### ratings

```sql
CREATE TABLE ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    rater_id BIGINT UNSIGNED NOT NULL,
    rated_id BIGINT UNSIGNED NOT NULL,
    type ENUM('client_to_courier', 'courier_to_client') NOT NULL,
    rating TINYINT NOT NULL,  -- 1-5
    comment TEXT NULL,
    tags JSON NULL,  -- ['rapide', 'professionnel']
    is_visible BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (rater_id) REFERENCES users(id),
    FOREIGN KEY (rated_id) REFERENCES users(id)
);
```

### zones

```sql
CREATE TABLE zones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    coordinates JSON NOT NULL,  -- Polygon de la zone
    base_price INT DEFAULT 500,
    price_per_km INT DEFAULT 200,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Index pour performance

```sql
-- Recherche de coursiers disponibles par proximité
CREATE INDEX idx_users_courier_available ON users(role, status, is_available, current_latitude, current_longitude);

-- Commandes par client
CREATE INDEX idx_orders_client ON orders(client_id, status, created_at);

-- Commandes par coursier
CREATE INDEX idx_orders_courier ON orders(courier_id, status, created_at);

-- Paiements par commande
CREATE INDEX idx_payments_order ON payments(order_id, status);

-- Transactions wallet
CREATE INDEX idx_wallet_transactions ON wallet_transactions(wallet_id, created_at);
```

## Migrations

Les migrations sont dans `database/migrations/` et suivent la convention:

```
YYYY_MM_DD_HHMMSS_action_table_name.php
```

### Exécuter les migrations

```bash
# Appliquer toutes les migrations
php artisan migrate

# Rollback
php artisan migrate:rollback

# Reset complet
php artisan migrate:fresh --seed
```

## Seeders

### DatabaseSeeder principal

```bash
php artisan db:seed
```

### Seeders individuels

```bash
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=TestUsersSeeder
php artisan db:seed --class=TestOrdersSeeder
php artisan db:seed --class=SiteSettingsSeeder
php artisan db:seed --class=LegalPagesSeeder
```

## Backup et restauration

### Backup MySQL

```bash
mysqldump -u root -p ouagachap > backup_$(date +%Y%m%d).sql
```

### Restauration

```bash
mysql -u root -p ouagachap < backup_20260129.sql
```
