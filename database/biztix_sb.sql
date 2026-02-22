-- BizTix Database Setup
-- File: database/biztix_db.sql

CREATE DATABASE IF NOT EXISTS biztix_db;
USE biztix_db;

-- -----------------------------------------------------
-- Table: admins
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table: categories
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table: events
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    venue VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    capacity INT NOT NULL DEFAULT 100,
    status ENUM('active', 'closed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Table: customers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- Table: bookings
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    event_id INT NOT NULL,
    quantity INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    booking_code VARCHAR(30) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_customer
        FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_bookings_event
        FOREIGN KEY (event_id) REFERENCES events(event_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- -----------------------------------------------------
-- Indexes (performance)
-- -----------------------------------------------------
CREATE INDEX idx_events_status_date_time ON events (status, event_date, event_time);
CREATE INDEX idx_bookings_event_id ON bookings (event_id);
CREATE INDEX idx_bookings_customer_id ON bookings (customer_id);
CREATE INDEX idx_bookings_status_payment ON bookings (booking_status, payment_status);

-- -----------------------------------------------------
-- Seed Data: categories
-- -----------------------------------------------------
INSERT INTO categories (category_name) VALUES
('Business'),
('Workshop'),
('Marketing'),
('Startup')
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- -----------------------------------------------------
-- Seed Data: events
-- -----------------------------------------------------
INSERT INTO events
(category_id, title, description, venue, event_date, event_time, ticket_price, capacity, status)
VALUES
(1, 'Business Growth Summit 2026',
 'A business networking and strategy summit for entrepreneurs and managers.',
 'Kuala Lumpur Convention Centre', '2026-10-15', '09:00:00', 199.00, 200, 'active'),

(4, 'Startup Pitch Workshop',
 'Hands-on workshop on pitch deck preparation, investor communication, and startup presentation skills.',
 'Online Webinar', '2026-10-22', '14:00:00', 49.00, 500, 'active'),

(3, 'Digital Marketing Masterclass',
 'Masterclass covering social media campaigns, performance marketing, and analytics for business growth.',
 'Petaling Jaya', '2026-11-05', '10:00:00', 129.00, 120, 'active')
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- -----------------------------------------------------
-- Seed Data: admin account
-- Password hash below is a placeholder. Replace it with your own generated hash.
-- -----------------------------------------------------
-- Example password label only: Admin123!
-- Generate your own hash using PHP password_hash() and replace the value below.
INSERT INTO admins (name, email, password_hash)
VALUES ('Admin', 'admin@biztix.com', '$2y$10$REPLACE_WITH_YOUR_REAL_HASH')
ON DUPLICATE KEY UPDATE email = VALUES(email);