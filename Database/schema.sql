-- AutoFlow schema (initial)
-- Date: 2026-02-22

CREATE DATABASE IF NOT EXISTS `autoflow`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `autoflow`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM("owner", "staff", "admin", "client") NOT NULL DEFAULT "owner",
    `last_login_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `internal_code` VARCHAR(50) NOT NULL UNIQUE,
    `vin` VARCHAR(50) NULL UNIQUE,
    `license_plate` VARCHAR(25) NOT NULL UNIQUE,
    `brand` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `year` SMALLINT NOT NULL,
    `color` VARCHAR(50) NULL,
    `transmission` ENUM("manual", "automatic") NOT NULL DEFAULT "manual",
    `fuel_type` VARCHAR(50) NULL,
    `mileage_km` INT UNSIGNED NOT NULL DEFAULT 0,
    `capacity_kg` INT UNSIGNED NOT NULL DEFAULT 0,
    `passenger_capacity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `next_service_km` INT UNSIGNED NULL,
    `next_service_date` DATE NULL,
    `registration_due_date` DATE NULL,
    `insurance_due_date` DATE NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM("available", "reserved", "rented", "maintenance", "retired") NOT NULL DEFAULT "available",
    `purchased_at` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_vehicle_status`(`status`),
    INDEX `idx_vehicle_brand_model`(`brand`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_images` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `storage_path` VARCHAR(255) NOT NULL,
    `position` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vehicle_images_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX `idx_vehicle_images_vehicle`(`vehicle_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance_records` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `service_type` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `service_date` DATE NOT NULL,
    `mileage_km` INT UNSIGNED NULL,
    `cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `next_service_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_maintenance_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX `idx_maintenance_service_date`(`service_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rentals` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `client_name` VARCHAR(150) NOT NULL,
    `client_document` VARCHAR(60) NOT NULL,
    `client_phone` VARCHAR(40) NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `odometer_start_km` INT UNSIGNED NULL,
    `odometer_end_km` INT UNSIGNED NULL,
    `status` ENUM("draft", "confirmed", "in_progress", "completed", "cancelled") NOT NULL DEFAULT "draft",
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rentals_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    INDEX `idx_rental_period`(`start_date`, `end_date`),
    INDEX `idx_rental_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `recorded_by` BIGINT UNSIGNED NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vehicle_events_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_vehicle_events_user`
        FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX `idx_vehicle_events_type`(`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_odometer_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `rental_id` BIGINT UNSIGNED NULL,
    `recorded_by` BIGINT UNSIGNED NULL,
    `mileage_km` INT UNSIGNED NOT NULL,
    `note` VARCHAR(255) NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_odometer_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_odometer_rental`
        FOREIGN KEY (`rental_id`) REFERENCES `rentals`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT `fk_odometer_user`
        FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX `idx_odometer_vehicle`(`vehicle_id`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance_plans` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `service_type` VARCHAR(120) NOT NULL,
    `interval_km` INT UNSIGNED NULL,
    `interval_months` SMALLINT UNSIGNED NULL,
    `last_service_date` DATE NULL,
    `last_service_km` INT UNSIGNED NULL,
    `next_service_date` DATE NULL,
    `next_service_km` INT UNSIGNED NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_maintenance_plans_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX `idx_maintenance_plans_vehicle`(`vehicle_id`),
    INDEX `idx_maintenance_plans_next_date`(`next_service_date`),
    INDEX `idx_maintenance_plans_next_km`(`next_service_km`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_obligations` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` BIGINT UNSIGNED NOT NULL,
    `obligation_type` ENUM("registration", "insurance", "tax", "other") NOT NULL,
    `due_date` DATE NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM("pending", "paid", "overdue") NOT NULL DEFAULT "pending",
    `paid_at` DATE NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_vehicle_obligations_vehicle`
        FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX `idx_vehicle_obligations_vehicle`(`vehicle_id`),
    INDEX `idx_vehicle_obligations_due`(`due_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `alerts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `alert_type` VARCHAR(60) NOT NULL,
    `title` VARCHAR(120) NOT NULL,
    `message` TEXT NULL,
    `due_date` DATE NULL,
    `status` ENUM("open", "dismissed", "resolved") NOT NULL DEFAULT "open",
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME NULL,
    INDEX `idx_alerts_entity`(`entity_type`, `entity_id`),
    INDEX `idx_alerts_due`(`due_date`),
    INDEX `idx_alerts_status`(`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(60) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `summary` VARCHAR(255) NULL,
    `before_data` LONGTEXT NULL,
    `after_data` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_logs_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    INDEX `idx_audit_logs_user`(`user_id`),
    INDEX `idx_audit_logs_entity`(`entity_type`, `entity_id`),
    INDEX `idx_audit_logs_action`(`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
