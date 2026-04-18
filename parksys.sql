-- ============================================================
-- ParkSys Pro — Database Schema v1.0
-- ============================================================

CREATE DATABASE parksys_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parksys_db;

-- Users & Roles
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('superadmin','admin','customer') NOT NULL DEFAULT 'customer',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Parking Zones (e.g. Zone A - Ground Floor)
CREATE TABLE zones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL,
    floor       VARCHAR(20),
    description TEXT,
    is_active   TINYINT(1) DEFAULT 1
);

-- Parking Slots
CREATE TABLE slots (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    zone_id     INT UNSIGNED NOT NULL,
    slot_code   VARCHAR(10) NOT NULL UNIQUE,  -- e.g. A01, B12
    slot_type   ENUM('standard','handicap','vip','motorcycle') DEFAULT 'standard',
    status      ENUM('available','occupied','reserved','maintenance') DEFAULT 'available',
    FOREIGN KEY (zone_id) REFERENCES zones(id)
);

-- Vehicle Types & Rates (set by Super Admin)
CREATE TABLE rates (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vehicle_type    ENUM('motorcycle','car','van','truck') NOT NULL,
    first_hour_fee  DECIMAL(8,2) NOT NULL,   -- e.g. ₱50.00
    excess_hour_fee DECIMAL(8,2) NOT NULL,   -- fee per hour after 1st
    flat_day_rate   DECIMAL(8,2),            -- optional 24hr flat cap
    grace_minutes   INT UNSIGNED DEFAULT 15, -- free grace period
    effective_from  DATE NOT NULL,
    is_current      TINYINT(1) DEFAULT 1,
    created_by      INT UNSIGNED,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Parking Sessions (Entry/Exit)
CREATE TABLE sessions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_id    VARCHAR(30) NOT NULL UNIQUE,  -- e.g. TXN-20240418-001
    slot_id         INT UNSIGNED NOT NULL,
    plate_number    VARCHAR(15) NOT NULL,
    vehicle_type    ENUM('motorcycle','car','van','truck') NOT NULL,
    entry_time      DATETIME NOT NULL,
    exit_time       DATETIME,
    duration_mins   INT UNSIGNED,
    status          ENUM('active','completed','cancelled') DEFAULT 'active',
    processed_by    INT UNSIGNED,                 -- admin user ID
    FOREIGN KEY (slot_id) REFERENCES slots(id),
    FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Billing / Transactions
CREATE TABLE transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      INT UNSIGNED NOT NULL UNIQUE,
    rate_id         INT UNSIGNED NOT NULL,
    base_fee        DECIMAL(8,2) NOT NULL,
    excess_fee      DECIMAL(8,2) DEFAULT 0.00,
    discount        DECIMAL(8,2) DEFAULT 0.00,
    total_fee       DECIMAL(8,2) NOT NULL,
    payment_method  ENUM('cash','gcash','card','maya') DEFAULT 'cash',
    paid_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receipt_no      VARCHAR(30),
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (rate_id)    REFERENCES rates(id)
);

-- Audit Log (for AI analytics & accountability)
CREATE TABLE audit_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED,
    action      VARCHAR(100),
    table_name  VARCHAR(50),
    record_id   INT UNSIGNED,
    details     JSON,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Indexes for performance
CREATE INDEX idx_sessions_plate  ON sessions(plate_number);
CREATE INDEX idx_sessions_status ON sessions(status);
CREATE INDEX idx_slots_status    ON slots(status);