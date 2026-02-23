<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Gestiona planes de mantenimiento por km/fecha.
 */
class MaintenancePlanModel extends BaseModel
{
    /**
     * Lista planes de mantenimiento por vehiculo.
     */
    public function listByVehicle(int $vehicleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM `maintenance_plans`
             WHERE `vehicle_id` = :vehicle_id
             ORDER BY `created_at` DESC'
        );
        $statement->execute(['vehicle_id' => $vehicleId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca un plan por id.
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM `maintenance_plans` WHERE `id` = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $plan = $statement->fetch(PDO::FETCH_ASSOC);

        return $plan ?: null;
    }

    /**
     * Inserta un nuevo plan de mantenimiento.
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `maintenance_plans`
            (vehicle_id, service_type, interval_km, interval_months, last_service_date, last_service_km,
             next_service_date, next_service_km, is_active, notes)
            VALUES
            (:vehicle_id, :service_type, :interval_km, :interval_months, :last_service_date, :last_service_km,
             :next_service_date, :next_service_km, :is_active, :notes)'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'interval_km' => $data['interval_km'],
            'interval_months' => $data['interval_months'],
            'last_service_date' => $data['last_service_date'],
            'last_service_km' => $data['last_service_km'],
            'next_service_date' => $data['next_service_date'],
            'next_service_km' => $data['next_service_km'],
            'is_active' => $data['is_active'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un plan existente.
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `maintenance_plans` SET
                vehicle_id = :vehicle_id,
                service_type = :service_type,
                interval_km = :interval_km,
                interval_months = :interval_months,
                last_service_date = :last_service_date,
                last_service_km = :last_service_km,
                next_service_date = :next_service_date,
                next_service_km = :next_service_km,
                is_active = :is_active,
                notes = :notes
             WHERE id = :id'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'interval_km' => $data['interval_km'],
            'interval_months' => $data['interval_months'],
            'last_service_date' => $data['last_service_date'],
            'last_service_km' => $data['last_service_km'],
            'next_service_date' => $data['next_service_date'],
            'next_service_km' => $data['next_service_km'],
            'is_active' => $data['is_active'],
            'notes' => $data['notes'],
            'id' => $id,
        ]);
    }

    /**
     * Elimina el plan seleccionado.
     */
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM `maintenance_plans` WHERE `id` = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * Datos base para un plan nuevo.
     */
    public function defaultFormData(): array
    {
        return [
            'vehicle_id' => 0,
            'service_type' => '',
            'interval_km' => null,
            'interval_months' => null,
            'last_service_date' => null,
            'last_service_km' => null,
            'next_service_date' => null,
            'next_service_km' => null,
            'is_active' => 1,
            'notes' => '',
        ];
    }

    /**
     * Normaliza los datos de entrada.
     */
    public function normalizeInput(array $input): array
    {
        return [
            'vehicle_id' => (int) ($input['vehicle_id'] ?? 0),
            'service_type' => trim((string) ($input['service_type'] ?? '')),
            'interval_km' => $this->normalizeNullableInt($input['interval_km'] ?? null),
            'interval_months' => $this->normalizeNullableInt($input['interval_months'] ?? null),
            'last_service_date' => $this->normalizeNullableDate($input['last_service_date'] ?? null),
            'last_service_km' => $this->normalizeNullableInt($input['last_service_km'] ?? null),
            'next_service_date' => $this->normalizeNullableDate($input['next_service_date'] ?? null),
            'next_service_km' => $this->normalizeNullableInt($input['next_service_km'] ?? null),
            'is_active' => !empty($input['is_active']) ? 1 : 0,
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
    }

    /**
     * Valida campos esenciales del plan.
     */
    public function validate(array $data): array
    {
        $errors = [];

        if ($data['vehicle_id'] <= 0) {
            $errors['vehicle_id'] = 'Selecciona un vehiculo valido.';
        }

        if ($data['service_type'] === '') {
            $errors['service_type'] = 'El tipo de servicio es obligatorio.';
        }

        if (
            $data['interval_km'] === null && $data['interval_months'] === null
            && $data['next_service_date'] === null && $data['next_service_km'] === null
        ) {
            $errors['interval_km'] = 'Defini un intervalo o una proxima fecha/km.';
        }

        return $errors;
    }

    private function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $intValue = (int) $value;
        return $intValue >= 0 ? $intValue : null;
    }

    private function normalizeNullableDate($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTime && $date->format('Y-m-d') === $value ? $value : null;
    }
}
