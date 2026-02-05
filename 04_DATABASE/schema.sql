-- ============================================================================
-- OUAGA CHAP - Database Schema
-- Urban Delivery Application
-- MySQL 8.x
-- ============================================================================
-- Version: 1.0 MVP
-- Date: January 2026
-- ============================================================================

-- ----------------------------------------------------------------------------
-- DATABASE CREATION
-- ----------------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS ouagachap
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ouagachap;

-- ----------------------------------------------------------------------------
-- TABLE: users
-- Description: Stores all users (customers, couriers, admins)
-- ----------------------------------------------------------------------------

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE COMMENT 'Phone number in E.164 format (+226XXXXXXXX)',
    name VARCHAR(100) NULL COMMENT 'User display name',
    email VARCHAR(255) NULL UNIQUE COMMENT 'Optional email for admins',
    role ENUM('customer', 'courier', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('pending', 'active', 'suspended', 'rejected') NOT NULL DEFAULT 'pending',
    
    -- Courier-specific fields
    vehicle_type VARCHAR(50) NULL COMMENT 'moto, bicycle, car',
    vehicle_plate VARCHAR(20) NULL COMMENT 'License plate number',
    vehicle_model VARCHAR(100) NULL COMMENT 'Vehicle model/brand',
    is_available BOOLEAN DEFAULT FALSE COMMENT 'Courier availability toggle',
    current_latitude DECIMAL(10, 8) NULL COMMENT 'Last known latitude',
    current_longitude DECIMAL(11, 8) NULL COMMENT 'Last known longitude',
    location_updated_at TIMESTAMP NULL COMMENT 'When location was last updated',
    
    -- Stats
    total_orders INT UNSIGNED DEFAULT 0 COMMENT 'Total completed orders',
    average_rating DECIMAL(2, 1) DEFAULT 0.0 COMMENT 'Average rating (1-5)',
    total_ratings INT UNSIGNED DEFAULT 0 COMMENT 'Number of ratings received',
    
    -- Balance (for couriers)
    wallet_balance DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'Available balance for withdrawal',
    
    -- FCM Token for push notifications
    fcm_token TEXT NULL COMMENT 'Firebase Cloud Messaging token',
    
    -- Timestamps
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL COMMENT 'When courier was approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_phone (phone),
    INDEX idx_role_status (role, status),
    INDEX idx_courier_available (role, status, is_available),
    INDEX idx_location (current_latitude, current_longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: courier_documents
-- Description: Documents uploaded by couriers for verification
-- ----------------------------------------------------------------------------

CREATE TABLE courier_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    courier_id BIGINT UNSIGNED NOT NULL,
    type ENUM('cni', 'driver_license', 'vehicle_registration', 'profile_photo', 'vehicle_photo') NOT NULL,
    file_path VARCHAR(500) NOT NULL COMMENT 'Path to file in storage',
    file_name VARCHAR(255) NOT NULL COMMENT 'Original file name',
    file_size INT UNSIGNED NOT NULL COMMENT 'File size in bytes',
    mime_type VARCHAR(100) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL COMMENT 'Reason if rejected',
    reviewed_by BIGINT UNSIGNED NULL COMMENT 'Admin who reviewed',
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_courier (courier_id),
    INDEX idx_status (status),
    UNIQUE INDEX idx_courier_type (courier_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: orders
-- Description: Delivery orders
-- ----------------------------------------------------------------------------

CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE COMMENT 'Public UUID for order tracking',
    
    -- Users
    customer_id BIGINT UNSIGNED NOT NULL,
    courier_id BIGINT UNSIGNED NULL COMMENT 'Assigned courier',
    
    -- Status
    status ENUM(
        'pending',      -- Created, waiting for courier
        'accepted',     -- Courier accepted
        'picked_up',    -- Courier has the package
        'delivered',    -- Successfully delivered
        'cancelled',    -- Cancelled by customer/courier/admin
        'expired'       -- No courier accepted in time
    ) NOT NULL DEFAULT 'pending',
    
    -- Pickup details
    pickup_address TEXT NOT NULL,
    pickup_latitude DECIMAL(10, 8) NOT NULL,
    pickup_longitude DECIMAL(11, 8) NOT NULL,
    pickup_contact_name VARCHAR(100) NOT NULL,
    pickup_contact_phone VARCHAR(20) NOT NULL,
    pickup_instructions TEXT NULL COMMENT 'Special instructions for pickup',
    
    -- Dropoff details
    dropoff_address TEXT NOT NULL,
    dropoff_latitude DECIMAL(10, 8) NOT NULL,
    dropoff_longitude DECIMAL(11, 8) NOT NULL,
    dropoff_contact_name VARCHAR(100) NOT NULL,
    dropoff_contact_phone VARCHAR(20) NOT NULL,
    dropoff_instructions TEXT NULL COMMENT 'Special instructions for delivery',
    
    -- Package details
    package_description TEXT NULL,
    package_size ENUM('small', 'medium', 'large') DEFAULT 'small',
    package_photo_path VARCHAR(500) NULL COMMENT 'Photo taken at pickup',
    
    -- Pricing
    distance_km DECIMAL(6, 2) NOT NULL COMMENT 'Calculated distance',
    estimated_duration_min INT UNSIGNED NOT NULL COMMENT 'Estimated delivery time',
    base_price DECIMAL(10, 2) NOT NULL COMMENT 'Base fare',
    distance_price DECIMAL(10, 2) NOT NULL COMMENT 'Distance-based fare',
    total_price DECIMAL(10, 2) NOT NULL COMMENT 'Total order price',
    platform_fee DECIMAL(10, 2) NOT NULL COMMENT 'Platform commission (15%)',
    courier_earnings DECIMAL(10, 2) NOT NULL COMMENT 'Courier payout (85%)',
    
    -- Cancellation
    cancelled_by BIGINT UNSIGNED NULL COMMENT 'User who cancelled',
    cancellation_reason TEXT NULL,
    cancellation_fee DECIMAL(10, 2) DEFAULT 0.00,
    
    -- Timestamps
    accepted_at TIMESTAMP NULL,
    picked_up_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL COMMENT 'When order will auto-expire if not accepted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_uuid (uuid),
    INDEX idx_customer (customer_id),
    INDEX idx_courier (courier_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at),
    INDEX idx_status_created (status, created_at),
    INDEX idx_pending_location (status, pickup_latitude, pickup_longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: order_status_history
-- Description: Audit trail of order status changes
-- ----------------------------------------------------------------------------

CREATE TABLE order_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by BIGINT UNSIGNED NULL COMMENT 'User who made the change',
    latitude DECIMAL(10, 8) NULL COMMENT 'Location when status changed',
    longitude DECIMAL(11, 8) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_order (order_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: payments
-- Description: Payment transactions
-- ----------------------------------------------------------------------------

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    
    -- Payment details
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'XOF' COMMENT 'FCFA currency code',
    provider ENUM('orange_money', 'moov_money', 'cash') NOT NULL,
    phone VARCHAR(20) NOT NULL COMMENT 'Payment phone number',
    
    -- Transaction tracking
    transaction_id VARCHAR(100) NULL UNIQUE COMMENT 'Provider transaction ID',
    provider_reference VARCHAR(100) NULL COMMENT 'Provider-specific reference',
    
    -- Status
    status ENUM(
        'pending',      -- Initiated
        'processing',   -- Waiting for user confirmation
        'completed',    -- Successfully paid
        'failed',       -- Payment failed
        'refunded',     -- Refund processed
        'cancelled'     -- Cancelled before completion
    ) NOT NULL DEFAULT 'pending',
    
    failure_reason TEXT NULL,
    
    -- Timestamps
    initiated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
    
    INDEX idx_order (order_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_status (status),
    INDEX idx_provider (provider),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: withdrawals
-- Description: Courier withdrawal requests
-- ----------------------------------------------------------------------------

CREATE TABLE withdrawals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    courier_id BIGINT UNSIGNED NOT NULL,
    
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
    provider ENUM('orange_money', 'moov_money') NOT NULL,
    phone VARCHAR(20) NOT NULL COMMENT 'Withdrawal destination phone',
    
    -- Transaction tracking
    transaction_id VARCHAR(100) NULL UNIQUE,
    provider_reference VARCHAR(100) NULL,
    
    status ENUM(
        'pending',      -- Requested
        'processing',   -- Being processed
        'completed',    -- Successfully sent
        'failed',       -- Failed
        'cancelled'     -- Cancelled
    ) NOT NULL DEFAULT 'pending',
    
    failure_reason TEXT NULL,
    processed_by BIGINT UNSIGNED NULL COMMENT 'Admin who processed',
    
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_courier (courier_id),
    INDEX idx_status (status),
    INDEX idx_requested (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: ratings
-- Description: Ratings between users after order completion
-- ----------------------------------------------------------------------------

CREATE TABLE ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    from_user_id BIGINT UNSIGNED NOT NULL COMMENT 'User giving the rating',
    to_user_id BIGINT UNSIGNED NOT NULL COMMENT 'User receiving the rating',
    
    score TINYINT UNSIGNED NOT NULL COMMENT 'Rating 1-5',
    comment TEXT NULL COMMENT 'Optional review text',
    
    -- Specific feedback (optional)
    was_on_time BOOLEAN NULL,
    was_professional BOOLEAN NULL,
    package_condition_ok BOOLEAN NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE INDEX idx_order_from (order_id, from_user_id),
    INDEX idx_to_user (to_user_id),
    INDEX idx_score (score),
    
    CONSTRAINT chk_score CHECK (score >= 1 AND score <= 5),
    CONSTRAINT chk_different_users CHECK (from_user_id != to_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: courier_locations
-- Description: Real-time location tracking for couriers
-- ----------------------------------------------------------------------------

CREATE TABLE courier_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    courier_id BIGINT UNSIGNED NOT NULL,
    order_id BIGINT UNSIGNED NULL COMMENT 'Associated order if on delivery',
    
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(6, 2) NULL COMMENT 'GPS accuracy in meters',
    heading DECIMAL(5, 2) NULL COMMENT 'Direction in degrees',
    speed DECIMAL(5, 2) NULL COMMENT 'Speed in km/h',
    
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (courier_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    
    INDEX idx_courier_time (courier_id, recorded_at DESC),
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: otp_codes
-- Description: OTP codes for phone verification
-- ----------------------------------------------------------------------------

CREATE TABLE otp_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    code VARCHAR(10) NOT NULL COMMENT 'Hashed OTP code',
    purpose ENUM('login', 'register', 'reset') NOT NULL DEFAULT 'login',
    attempts INT UNSIGNED DEFAULT 0 COMMENT 'Failed verification attempts',
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_phone (phone),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: notifications
-- Description: In-app and push notification history
-- ----------------------------------------------------------------------------

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    
    type VARCHAR(100) NOT NULL COMMENT 'Notification type/class',
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSON NULL COMMENT 'Additional payload data',
    
    channel ENUM('push', 'sms', 'in_app') NOT NULL DEFAULT 'push',
    status ENUM('pending', 'sent', 'failed', 'read') NOT NULL DEFAULT 'pending',
    
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_user (user_id),
    INDEX idx_user_read (user_id, read_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: zones
-- Description: Geographic zones for service availability
-- ----------------------------------------------------------------------------

CREATE TABLE zones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Zone name (e.g., Centre-ville)',
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    
    -- Bounding box (simplified for MVP)
    min_latitude DECIMAL(10, 8) NOT NULL,
    max_latitude DECIMAL(10, 8) NOT NULL,
    min_longitude DECIMAL(11, 8) NOT NULL,
    max_longitude DECIMAL(11, 8) NOT NULL,
    
    -- Polygon (for complex zones - Phase 2)
    polygon JSON NULL COMMENT 'GeoJSON polygon coordinates',
    
    is_active BOOLEAN DEFAULT TRUE,
    base_price_modifier DECIMAL(4, 2) DEFAULT 1.00 COMMENT 'Price multiplier for zone',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_bounds (min_latitude, max_latitude, min_longitude, max_longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: settings
-- Description: Application configuration settings
-- ----------------------------------------------------------------------------

CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    type ENUM('string', 'integer', 'float', 'boolean', 'json') NOT NULL DEFAULT 'string',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE COMMENT 'If true, can be fetched by mobile app',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (`key`),
    INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: personal_access_tokens (Laravel Sanctum)
-- Description: API tokens for authentication
-- ----------------------------------------------------------------------------

CREATE TABLE personal_access_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    abilities TEXT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- TABLE: jobs (Laravel Queue)
-- Description: Queue jobs table
-- ----------------------------------------------------------------------------

CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload LONGTEXT NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL,
    reserved_at INT UNSIGNED NULL,
    available_at INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    
    INDEX idx_queue_reserved (queue, reserved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) NOT NULL UNIQUE,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- DEFAULT DATA
-- ----------------------------------------------------------------------------

-- Insert default settings
INSERT INTO settings (`key`, value, type, description, is_public) VALUES
('pricing.base_fare', '500', 'integer', 'Base fare in FCFA', TRUE),
('pricing.per_km_rate', '200', 'integer', 'Price per kilometer in FCFA', TRUE),
('pricing.min_price', '500', 'integer', 'Minimum order price in FCFA', TRUE),
('pricing.max_price', '10000', 'integer', 'Maximum order price in FCFA', TRUE),
('pricing.platform_fee_percent', '15', 'integer', 'Platform commission percentage', FALSE),
('order.expiry_minutes', '15', 'integer', 'Minutes before unaccepted order expires', FALSE),
('order.max_distance_km', '20', 'integer', 'Maximum delivery distance in km', TRUE),
('courier.min_rating', '3.5', 'float', 'Minimum rating to remain active', FALSE),
('courier.min_withdrawal', '1000', 'integer', 'Minimum withdrawal amount in FCFA', TRUE),
('app.operating_hours_start', '07:00', 'string', 'Service start time', TRUE),
('app.operating_hours_end', '21:00', 'string', 'Service end time', TRUE),
('otp.expiry_minutes', '5', 'integer', 'OTP validity in minutes', FALSE),
('otp.max_attempts', '3', 'integer', 'Max OTP verification attempts', FALSE);

-- Insert default zone (Ouagadougou center)
INSERT INTO zones (name, slug, description, min_latitude, max_latitude, min_longitude, max_longitude, is_active) VALUES
('Centre-ville', 'centre-ville', 'Zone centre de Ouagadougou', 12.3400, 12.3800, -1.5400, -1.4900, TRUE),
('Ouaga 2000', 'ouaga-2000', 'Zone Ouaga 2000', 12.3200, 12.3500, -1.5200, -1.4800, TRUE),
('Tampouy', 'tampouy', 'Quartier Tampouy', 12.3900, 12.4200, -1.5600, -1.5200, TRUE),
('Pissy', 'pissy', 'Quartier Pissy', 12.3600, 12.3900, -1.5800, -1.5400, TRUE);

-- Create default admin user (password will be set via seeder)
INSERT INTO users (phone, name, email, role, status, phone_verified_at, created_at, updated_at) VALUES
('+22600000000', 'Admin OUAGA CHAP', 'admin@ouagachap.com', 'admin', 'active', NOW(), NOW(), NOW());

-- ----------------------------------------------------------------------------
-- TRIGGERS
-- ----------------------------------------------------------------------------

-- Trigger: Update user stats after rating
DELIMITER //
CREATE TRIGGER after_rating_insert
AFTER INSERT ON ratings
FOR EACH ROW
BEGIN
    UPDATE users 
    SET 
        total_ratings = total_ratings + 1,
        average_rating = (
            SELECT AVG(score) FROM ratings WHERE to_user_id = NEW.to_user_id
        )
    WHERE id = NEW.to_user_id;
END//
DELIMITER ;

-- Trigger: Update user order count after delivery
DELIMITER //
CREATE TRIGGER after_order_delivered
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        -- Update customer order count
        UPDATE users SET total_orders = total_orders + 1 WHERE id = NEW.customer_id;
        -- Update courier order count
        IF NEW.courier_id IS NOT NULL THEN
            UPDATE users SET total_orders = total_orders + 1 WHERE id = NEW.courier_id;
        END IF;
    END IF;
END//
DELIMITER ;

-- Trigger: Update courier wallet after order completion
DELIMITER //
CREATE TRIGGER after_order_completed
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' AND NEW.courier_id IS NOT NULL THEN
        UPDATE users 
        SET wallet_balance = wallet_balance + NEW.courier_earnings 
        WHERE id = NEW.courier_id;
    END IF;
END//
DELIMITER ;

-- ----------------------------------------------------------------------------
-- VIEWS
-- ----------------------------------------------------------------------------

-- View: Active orders with details
CREATE OR REPLACE VIEW v_active_orders AS
SELECT 
    o.id,
    o.uuid,
    o.status,
    o.pickup_address,
    o.dropoff_address,
    o.total_price,
    o.created_at,
    c.name AS customer_name,
    c.phone AS customer_phone,
    cr.name AS courier_name,
    cr.phone AS courier_phone,
    cr.current_latitude AS courier_lat,
    cr.current_longitude AS courier_lng
FROM orders o
JOIN users c ON o.customer_id = c.id
LEFT JOIN users cr ON o.courier_id = cr.id
WHERE o.status IN ('pending', 'accepted', 'picked_up');

-- View: Courier performance stats
CREATE OR REPLACE VIEW v_courier_stats AS
SELECT 
    u.id,
    u.name,
    u.phone,
    u.average_rating,
    u.total_orders,
    u.wallet_balance,
    COUNT(CASE WHEN o.status = 'delivered' AND o.delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS orders_last_7_days,
    SUM(CASE WHEN o.status = 'delivered' AND o.delivered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN o.courier_earnings ELSE 0 END) AS earnings_last_7_days
FROM users u
LEFT JOIN orders o ON u.id = o.courier_id
WHERE u.role = 'courier' AND u.status = 'active'
GROUP BY u.id;

-- View: Daily revenue summary
CREATE OR REPLACE VIEW v_daily_revenue AS
SELECT 
    DATE(o.created_at) AS date,
    COUNT(*) AS total_orders,
    SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) AS completed_orders,
    SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_orders,
    SUM(CASE WHEN o.status = 'delivered' THEN o.total_price ELSE 0 END) AS gross_revenue,
    SUM(CASE WHEN o.status = 'delivered' THEN o.platform_fee ELSE 0 END) AS platform_revenue,
    SUM(CASE WHEN o.status = 'delivered' THEN o.courier_earnings ELSE 0 END) AS courier_payouts
FROM orders o
GROUP BY DATE(o.created_at)
ORDER BY date DESC;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
