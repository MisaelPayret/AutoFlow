<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Gestiona planes de mantenimiento por km/fecha.
 */
class MaintenancePlanModel extends BaseModel
{
    /**
     * Lista planes con datos de vehiculo.
     */
    public function listWithVehicles(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql =
            'SELECT p.*, v.brand, v.model, v.license_plate
             FROM `maintenance_plans` AS p
             INNER JOIN `vehicles` AS v ON v.id = p.vehicle_id
             WHERE 1=1';
        $params = [];

        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND p.`vehicle_id` = :vehicle_id';
            $params['vehicle_id'] = (int) $filters['vehicle_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= ' AND p.`is_active` = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        $sql .= ' ORDER BY p.`created_at` DESC LIMIT :limit OFFSET :offset';

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cuenta planes segun filtros.
     */
    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM `maintenance_plans` WHERE 1=1';
        $params = [];

        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND `vehicle_id` = :vehicle_id';
            $params['vehicle_id'] = (int) $filters['vehicle_id'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= ' AND `is_active` = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();

        return (int) $statement->fetchColumn();
    }
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
        $data = $this->applyDerivedValues($data);
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
        $data = $this->applyDerivedValues($data);
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
     * Aplica calculos automaticos de proximo servicio si falta.
     */
    public function applyDerivedValues(array $data): array
    {
        if ($data['next_service_date'] === null && $data['interval_months'] !== null && $data['last_service_date'] !== null) {
            $data['next_service_date'] = $this->addMonths($data['last_service_date'], $data['interval_months']);
        }

        if ($data['next_service_km'] === null && $data['interval_km'] !== null && $data['last_service_km'] !== null) {
            $data['next_service_km'] = $data['last_service_km'] + $data['interval_km'];
        }

        return $data;
    }

    /**
     * Actualiza un plan activo con el ultimo mantenimiento.
     */
    public function updateFromMaintenance(int $vehicleId, string $serviceType, ?string $serviceDate, ?int $serviceKm): void
    {
        if ($vehicleId <= 0 || $serviceType === '') {
            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT * FROM `maintenance_plans`
             WHERE `vehicle_id` = :vehicle_id
               AND `service_type` = :service_type
               AND `is_active` = 1
             ORDER BY `created_at` DESC
             LIMIT 1'
        );
        $statement->execute([
            'vehicle_id' => $vehicleId,
            'service_type' => $serviceType,
        ]);
        $plan = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            return;
        }

        $plan['last_service_date'] = $serviceDate ?: $plan['last_service_date'];
        $plan['last_service_km'] = $serviceKm ?? $plan['last_service_km'];
        $plan = $this->applyDerivedValues($plan);

        $this->update((int) $plan['id'], $plan);
        $this->syncVehicleNextService((int) $plan['vehicle_id']);
    }

    /**
     * Sincroniza el proximo servicio del vehiculo con el plan mas cercano.
     */
    public function syncVehicleNextService(int $vehicleId): void
    {
        if ($vehicleId <= 0) {
            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT
                MIN(`next_service_date`) AS next_date,
                MIN(`next_service_km`) AS next_km
             FROM `maintenance_plans`
             WHERE `vehicle_id` = :vehicle_id AND `is_active` = 1'
        );
        $statement->execute(['vehicle_id' => $vehicleId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        $update = $this->pdo->prepare(
            'UPDATE `vehicles`
             SET `next_service_date` = :next_service_date,
                 `next_service_km` = :next_service_km
             WHERE `id` = :id'
        );
        $update->execute([
            'next_service_date' => $row['next_date'] ?? null,
            'next_service_km' => $row['next_km'] ?? null,
            'id' => $vehicleId,
        ]);
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

        if ($data['interval_km'] !== null && $data['interval_km'] <= 0) {
            $errors['interval_km'] = 'El intervalo en km debe ser mayor a 0.';
        }

        if ($data['interval_months'] !== null && $data['interval_months'] <= 0) {
            $errors['interval_months'] = 'El intervalo en meses debe ser mayor a 0.';
        }

        return $errors;
    }

    private function addMonths(string $dateValue, int $months): ?string
    {
        try {
            $date = new DateTime($dateValue);
            $date->add(new DateInterval('P' . max(1, $months) . 'M'));
            return $date->format('Y-m-d');
        } catch (Exception $exception) {
            return null;
        }
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
