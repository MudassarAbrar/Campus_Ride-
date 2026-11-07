CREATE DATABASE IF NOT EXISTS campusride_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE campusride_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    university VARCHAR(150) NOT NULL,
    home_area VARCHAR(150) NOT NULL,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_type VARCHAR(30) NOT NULL,
    vehicle_name VARCHAR(100) NOT NULL,
    registration_number VARCHAR(50) NOT NULL,
    passenger_capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vehicle_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_vehicle_capacity CHECK (passenger_capacity > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rider_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    pickup_area VARCHAR(150) NOT NULL,
    destination VARCHAR(150) NOT NULL,
    departure_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    total_seats INT NOT NULL,
    available_seats INT NOT NULL,
    fare DECIMAL(10,2) DEFAULT 0,
    description TEXT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ride_user FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ride_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,
    CONSTRAINT chk_ride_seats CHECK (total_seats > 0 AND available_seats >= 0 AND available_seats <= total_seats),
    CONSTRAINT chk_ride_fare CHECK (fare >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ride_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    passenger_id INT NOT NULL,
    requested_seats INT NOT NULL,
    pickup_point VARCHAR(150) NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_ride FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    CONSTRAINT fk_request_user FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_request_seats CHECK (requested_seats > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_rides_search ON rides (pickup_area, destination, departure_date, status, available_seats);
CREATE INDEX idx_requests_lookup ON ride_requests (ride_id, passenger_id, status);
