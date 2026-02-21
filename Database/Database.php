<?php

declare(strict_types=1);

/**
 * Lightweight database helper that provisions the schema needed to start AutoFlow.
 */
class Database
{
    private string $host = '127.0.0.1';
    private string $dbName = 'autoflow';
    private string $username = 'root';
    private string $password = '';
    private ?PDO $connection = null;

    /**
     * Permite sobreescribir parámetros de conexión (host, usuario, etc.).
     */
    public function __construct(array $config = [])
    {
        $this->host = $config['host'] ?? $this->host;
        $this->dbName = $config['database'] ?? $this->dbName;
        $this->username = $config['username'] ?? $this->username;
        $this->password = $config['password'] ?? $this->password;

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
            $serverDsn = sprintf('mysql:host=%s;charset=utf8mb4', $this->host);
            $serverConnection = new PDO($serverDsn, $this->username, $this->password, $this->defaultOptions());
            $serverConnection->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $this->dbName
            ));

            $databaseDsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->host, $this->dbName);
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
				`role` ENUM("owner", "staff") NOT NULL DEFAULT "owner",
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
            'role' => 'owner',
        ]);
    }
}
