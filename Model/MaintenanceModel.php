<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Acceso a datos para mantenimientos, incluye helpers de formularios.
 */
class MaintenanceModel extends BaseModel
{
    /**
     * Devuelve todos los servicios junto al vehículo relacionado.
     */
    public function listWithVehicles(): array
    {
        $statement = $this->pdo->query(
            'SELECT m.*, v.brand, v.model, v.license_plate
             FROM `maintenance_records` AS m
             INNER JOIN `vehicles` AS v ON v.id = m.vehicle_id
             ORDER BY m.service_date DESC, m.created_at DESC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca un registro puntual por ID.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `maintenance_records` WHERE `id` = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }

    /**
     * Inserta un nuevo mantenimiento y devuelve el ID generado.
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `maintenance_records`
             (vehicle_id, service_type, description, service_date, mileage_km, cost, next_service_date)
             VALUES (:vehicle_id, :service_type, :description, :service_date, :mileage_km, :cost, :next_service_date)'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'description' => $data['description'],
            'service_date' => $data['service_date'],
            'mileage_km' => $data['mileage_km'],
            'cost' => $data['cost'],
            'next_service_date' => $data['next_service_date'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza los campos editables de un mantenimiento.
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `maintenance_records` SET
                vehicle_id = :vehicle_id,
                service_type = :service_type,
                description = :description,
                service_date = :service_date,
                mileage_km = :mileage_km,
                cost = :cost,
                next_service_date = :next_service_date
             WHERE id = :id'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'description' => $data['description'],
            'service_date' => $data['service_date'],
            'mileage_km' => $data['mileage_km'],
            'cost' => $data['cost'],
            'next_service_date' => $data['next_service_date'],
            'id' => $id,
        ]);
    }

    /**
     * Elimina definitivamente el registro indicado.
     */
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM `maintenance_records` WHERE `id` = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * Estructura base para hidratar formularios nuevos.
     */
    public function defaultFormData(): array
    {
        return [
            'vehicle_id' => 0,
            'service_type' => '',
            'description' => '',
            'service_date' => date('Y-m-d'),
            'mileage_km' => null,
            'cost' => '0.00',
            'next_service_date' => null,
        ];
    }

    /**
     * Limpia y castea los valores provenientes de $_POST.
     */
    public function normalizeInput(array $input): array
    {
        $data = [
            'vehicle_id' => (int) ($input['vehicle_id'] ?? 0),
            'service_type' => trim((string) ($input['service_type'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'service_date' => trim((string) ($input['service_date'] ?? '')),
            'mileage_km' => isset($input['mileage_km']) && $input['mileage_km'] !== ''
                ? (int) $input['mileage_km']
                : null,
            'cost' => isset($input['cost']) && $input['cost'] !== ''
                ? number_format((float) $input['cost'], 2, '.', '')
                : '0.00',
            'next_service_date' => trim((string) ($input['next_service_date'] ?? '')) ?: null,
        ];

        if ($data['next_service_date'] === null) {
            $data['next_service_date'] = $this->suggestNextServiceDate($data);
        }

        return $data;
    }

    /**
     * Valida cada campo del formulario devolviendo errores traducidos.
     */
    public function validate(array $data): array
    {
        $errors = [];

        if ($data['vehicle_id'] <= 0) {
            $errors['vehicle_id'] = 'Seleccioná un vehículo válido.';
        }

        if ($data['service_type'] === '') {
            $errors['service_type'] = 'El tipo de servicio es obligatorio.';
        }

        if (!$this->isValidDate($data['service_date'])) {
            $errors['service_date'] = 'Ingresá una fecha de servicio válida.';
        }

        if ($data['mileage_km'] !== null && $data['mileage_km'] < 0) {
            $errors['mileage_km'] = 'El kilometraje no puede ser negativo.';
        }

        if (!is_numeric($data['cost']) || (float) $data['cost'] < 0) {
            $errors['cost'] = 'El costo debe ser un valor positivo.';
        }

        if ($data['next_service_date'] !== null && !$this->isValidDate($data['next_service_date'])) {
            $errors['next_service_date'] = 'Ingresá una fecha válida para el próximo servicio.';
        }

        return $errors;
    }

    /**
     * Cuenta mantenimientos cuyo próximo servicio está vencido.
     */
    public function countOverdue(): int
    {
        $stmt = $this->pdo->query(
            'SELECT COUNT(*) FROM `maintenance_records`
             WHERE `next_service_date` IS NOT NULL AND `next_service_date` < CURDATE()'
        );

        return (int) $stmt->fetchColumn();
    }

    /**
     * Suma gastos en un rango móvil de días.
     */
    public function sumCostLastDays(int $days): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(SUM(`cost`), 0) FROM `maintenance_records`
             WHERE `service_date` >= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
        );
        $stmt->execute(['days' => $days]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Lista mantenimientos futuros ordenados cronológicamente.
     */
    public function upcoming(int $limit = 4): array
    {
        $statement = $this->pdo->prepare(
            'SELECT m.id, m.service_type, m.next_service_date, v.brand, v.model, v.license_plate
             FROM `maintenance_records` AS m
             INNER JOIN `vehicles` AS v ON v.id = m.vehicle_id
             WHERE m.next_service_date IS NOT NULL AND m.next_service_date >= CURDATE()
             ORDER BY m.next_service_date ASC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Sugiere una fecha próxima según el tipo de servicio ingresado.
     */
    public function suggestNextServiceDate(array $data): ?string
    {
        if (!$this->isValidDate($data['service_date'] ?? null)) {
            return null;
        }

        $serviceType = strtolower($data['service_type'] ?? '');
        $intervalSpec = 'P6M';

        if (str_contains($serviceType, 'aceite') || str_contains($serviceType, 'oil')) {
            $intervalSpec = 'P6M';
        } elseif (str_contains($serviceType, 'neum') || str_contains($serviceType, 'tire')) {
            $intervalSpec = 'P3M';
        } elseif (str_contains($serviceType, 'itv') || str_contains($serviceType, 'tecnica')) {
            $intervalSpec = 'P1Y';
        }

        try {
            $date = new DateTime($data['service_date']);
            $date->add(new DateInterval($intervalSpec));
            return $date->format('Y-m-d');
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Comprueba que la cadena siga el formato YYYY-MM-DD.
     */
    private function isValidDate(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTime && $date->format('Y-m-d') === $value;
    }
}
