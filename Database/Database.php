<?php

declare(strict_types=1);

/**
 * Lightweight database helper that provisions the schema needed to start AutoFlow.
 */
class Database
{
    private string $host = '127.0.0.1';
    private int $port = 3306;
    private string $dbName = 'autoflow';
    private string $username = 'root';
    private string $password = '';
    private bool $autoCreate = true;
    private ?PDO $connection = null;

    /**
     * Permite sobreescribir parámetros de conexión (host, usuario, etc.).
     */
    public function __construct(array $config = [])
    {
        $envConfig = $this->loadEnvConfig();
        $merged = array_merge($envConfig, $config);

        $this->host = $merged['host'] ?? $this->host;
        $this->port = isset($merged['port']) ? (int) $merged['port'] : $this->port;
        $this->dbName = $merged['database'] ?? $this->dbName;
        $this->username = $merged['username'] ?? $this->username;
        $this->password = $merged['password'] ?? $this->password;
        if (array_key_exists('auto_create', $merged)) {
            $this->autoCreate = (bool) $merged['auto_create'];
        }

        $this->initialize();
    }

    /**
     * Devuelve una instancia viva de PDO, reusándola cuando ya existe.
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->initialize();
        }

        return $this->connection;
    }

    /**
     * Crea la base si no existe, abre la conexión y ejecuta migraciones.
     */
    private function initialize(): void
    {
        try {
            if ($this->autoCreate) {
                $serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $this->host, $this->port);
                $serverConnection = new PDO($serverDsn, $this->username, $this->password, $this->defaultOptions());
                $serverConnection->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    $this->dbName
                ));
            }

            $databaseDsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $this->host, $this->port, $this->dbName);
            $this->connection = new PDO($databaseDsn, $this->username, $this->password, $this->defaultOptions());
            $this->connection->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

            $this->runMigrations();
        } catch (PDOException $exception) {
            throw new PDOException('Database initialization failed: ' . $exception->getMessage(), (int) $exception->getCode());
        }
    }

    /**
     * Configuración base usada en cada nueva conexión PDO.
     */
    private function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }

    /**
     * Lee la configuracion de base desde variables de entorno.
     */
    private function loadEnvConfig(): array
    {
        $host = getenv('AUTOFLOW_DB_HOST') ?: null;
        $port = getenv('AUTOFLOW_DB_PORT') ?: null;
        $dbName = getenv('AUTOFLOW_DB_NAME') ?: null;
        $username = getenv('AUTOFLOW_DB_USER') ?: null;
        $password = getenv('AUTOFLOW_DB_PASS') ?: null;
        $autoCreate = getenv('AUTOFLOW_DB_AUTO_CREATE');

        $config = [];
        if ($host) {
            $config['host'] = $host;
        }
        if ($port !== null && $port !== '') {
            $config['port'] = (int) $port;
        }
        if ($dbName) {
            $config['database'] = $dbName;
        }
        if ($username) {
            $config['username'] = $username;
        }
        if ($password !== null) {
            $config['password'] = $password;
        }
        if ($autoCreate !== false && $autoCreate !== null) {
            $config['auto_create'] = filter_var($autoCreate, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return $config;
    }

    /**
     * Ejecuta el SQL mínimo para que la app funcione en entornos limpios.
     */
    private function runMigrations(): void
    {
        foreach ($this->schemaStatements() as $statement) {
            $this->connection?->exec($statement);
        }

        $this->applySchemaUpgrades();
        $this->seedDefaultAdmin();
    }

    /**
     * Colección de sentencias CREATE TABLE ordenadas según dependencias.
     */
    private function schemaStatements(): array
    {
        return [
            // Core user that manages the fleet.
            'CREATE TABLE IF NOT EXISTS `users` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(120) NOT NULL,
                `email` VARCHAR(190) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL,
                `role` ENUM("admin", "client") NOT NULL DEFAULT "client",
                `last_login_at` DATETIME NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Vehicles available in the fleet.
            'CREATE TABLE IF NOT EXISTS `vehicles` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Image gallery per vehicle.
            'CREATE TABLE IF NOT EXISTS `vehicle_images` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Maintenance tracking per vehicle.
            'CREATE TABLE IF NOT EXISTS `maintenance_records` (
				`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				`vehicle_id` BIGINT UNSIGNED NOT NULL,
				`service_type` VARCHAR(120) NOT NULL,
				`description` TEXT NULL,
				`service_date` DATE NOT NULL,
				`mileage_km` INT UNSIGNED NULL,
				`cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `status` ENUM("pending", "in_progress", "completed") NOT NULL DEFAULT "pending",
				`next_service_date` DATE NULL,
				`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				CONSTRAINT `fk_maintenance_vehicle`
					FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
					ON DELETE CASCADE
					ON UPDATE CASCADE,
				INDEX `idx_maintenance_service_date`(`service_date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Reservations and rental contracts.
            'CREATE TABLE IF NOT EXISTS `rentals` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Quick log for vehicle status changes or incidents.
            'CREATE TABLE IF NOT EXISTS `vehicle_events` (
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
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Odometer log per vehicle (manual update or rental close).
            'CREATE TABLE IF NOT EXISTS `vehicle_odometer_logs` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Maintenance plans and due thresholds per vehicle.
            'CREATE TABLE IF NOT EXISTS `maintenance_plans` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Vehicle obligations such as registration, insurance, taxes.
            'CREATE TABLE IF NOT EXISTS `vehicle_obligations` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Alerts generated for due dates, mileage thresholds, etc.
            'CREATE TABLE IF NOT EXISTS `alerts` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

            // Audit log for all relevant actions.
            'CREATE TABLE IF NOT EXISTS `audit_logs` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        ];
    }

    /**
     * Agrega columnas nuevas cuando el proyecto evoluciona.
     */
    private function applySchemaUpgrades(): void
    {
        if ($this->connection === null) {
            return;
        }

        $this->ensureVehicleColumn('capacity_kg', 'INT UNSIGNED NOT NULL DEFAULT 0', 'mileage_km');
        $this->ensureVehicleColumn('passenger_capacity', 'TINYINT UNSIGNED NOT NULL DEFAULT 1', 'capacity_kg');
        $this->ensureVehicleColumn('next_service_km', 'INT UNSIGNED NULL', 'mileage_km');
        $this->ensureVehicleColumn('next_service_date', 'DATE NULL', 'next_service_km');
        $this->ensureVehicleColumn('registration_due_date', 'DATE NULL', 'next_service_date');
        $this->ensureVehicleColumn('insurance_due_date', 'DATE NULL', 'registration_due_date');

        $this->ensureRentalColumn('odometer_start_km', 'INT UNSIGNED NULL', 'end_date');
        $this->ensureRentalColumn('odometer_end_km', 'INT UNSIGNED NULL', 'odometer_start_km');

        $this->ensureMaintenanceColumn('status', 'ENUM("pending", "in_progress", "completed") NOT NULL DEFAULT "pending"', 'cost');

        $this->ensureUserRoleEnum();
    }

    /**
     * Ajusta el enum de roles para soportar los valores usados por la app.
     */
    private function ensureUserRoleEnum(): void
    {
        if ($this->connection === null) {
            return;
        }

        try {
            $this->connection->exec(
                "UPDATE `users` SET `role` = 'admin' WHERE `role` IN ('owner', 'staff')"
            );
            $this->connection->exec(
                'ALTER TABLE `users` MODIFY `role` ENUM("admin", "client") NOT NULL DEFAULT "client"'
            );
        } catch (PDOException $exception) {
            // Ignore enum update failures for older MySQL versions.
        }
    }

    /**
     * Añade una columna a rentals si aún no existe.
     */
    private function ensureRentalColumn(string $column, string $definition, string $afterColumn): void
    {
        if ($this->connection === null) {
            return;
        }

        if ($this->columnExists('rentals', $column)) {
            return;
        }

        $this->connection->exec(
            sprintf('ALTER TABLE `rentals` ADD `%s` %s AFTER `%s`', $column, $definition, $afterColumn)
        );
    }

    /**
     * Añade una columna a vehicles si aún no existe (opera idempotentemente).
     */
    private function ensureVehicleColumn(string $column, string $definition, string $afterColumn): void
    {
        if ($this->columnExists('vehicles', $column)) {
            return;
        }

        $this->connection->exec(
            sprintf('ALTER TABLE `vehicles` ADD `%s` %s AFTER `%s`', $column, $definition, $afterColumn)
        );
    }

    /**
     * Añade una columna a maintenance_records si aún no existe.
     */
    private function ensureMaintenanceColumn(string $column, string $definition, string $afterColumn): void
    {
        if ($this->columnExists('maintenance_records', $column)) {
            return;
        }

        $this->connection->exec(
            sprintf('ALTER TABLE `maintenance_records` ADD `%s` %s AFTER `%s`', $column, $definition, $afterColumn)
        );
    }

    /**
     * Helper genérico para consultar INFORMATION_SCHEMA.
     */
    private function columnExists(string $table, string $column): bool
    {
        if ($this->connection === null) {
            return false;
        }

        $statement = $this->connection->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column'
        );
        $statement->execute([
            'schema' => $this->dbName,
            'table' => $table,
            'column' => $column,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Inserta un usuario administrador por defecto si la tabla está vacía.
     */
    private function seedDefaultAdmin(): void
    {
        if ($this->connection === null) {
            return;
        }

        $email = 'admin@autoflow.local';
        $name = 'admin';
        $password = '1234';

        $stmt = $this->connection->prepare('SELECT COUNT(*) FROM `users` WHERE `email` = :email');
        $stmt->execute(['email' => $email]);

        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $insert = $this->connection->prepare('INSERT INTO `users` (`name`, `email`, `password_hash`, `role`) VALUES (:name, :email, :password_hash, :role)');
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);
    }
}
