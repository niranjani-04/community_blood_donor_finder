DROP DATABASE IF EXISTS blood_sos_system;
CREATE DATABASE blood_sos_system;
USE blood_sos_system;

-- Preloaded Students (Registry for Verification)
CREATE TABLE preloaded_students (
    register_number VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    blood_group VARCHAR(5) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table (Donors & Requesters & Admin)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    register_number VARCHAR(20) UNIQUE NULL, -- NULL for Admin
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('donor', 'requester', 'admin') DEFAULT 'donor',
    blood_group VARCHAR(5),
    is_activated TINYINT(1) DEFAULT 0,
    points INT DEFAULT 0,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    availability_status VARCHAR(20) DEFAULT 'Available',
    last_donation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hospitals Table
CREATE TABLE hospitals (
    hospital_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    contact_phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SOS Alerts Table
CREATE TABLE sos_alerts (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    requester_id INT,
    blood_group VARCHAR(5) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    status ENUM('active', 'accepted', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- SOS Responses (Tracking Accepted Donors)
CREATE TABLE sos_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT,
    donor_id INT,
    status ENUM('accepted', 'rejected', 'completed') DEFAULT 'accepted',
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES sos_alerts(alert_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Live Tracking Table (History of movement users)
CREATE TABLE tracking (
    track_id INT AUTO_INCREMENT PRIMARY KEY,
    donor_id INT,
    alert_id INT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (alert_id) REFERENCES sos_alerts(alert_id) ON DELETE SET NULL
);

-- Insert Default Admin (Password: admin123)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (register_number, name, email, phone, password, role, blood_group, is_activated, availability_status) 
VALUES (NULL, 'System Administrator', 'admin@heber.edu', '0000000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'O+', 1, 'Available');
