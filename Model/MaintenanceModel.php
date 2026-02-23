<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Acceso a datos para mantenimientos, incluye helpers de formularios.
 */
class MaintenanceModel extends BaseModel
{
    private const STATUS_OPTIONS = ['pending', 'in_progress', 'completed'];

    /**
     * Opciones válidas para el estado del mantenimiento.
     */
    public function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }
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
     * Busca mantenimientos con filtros avanzados.
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql =
            'SELECT m.*, v.brand, v.model, v.license_plate
             FROM `maintenance_records` AS m
             INNER JOIN `vehicles` AS v ON v.id = m.vehicle_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);
        $sql .= ' ORDER BY m.`service_date` DESC, m.`created_at` DESC LIMIT :limit OFFSET :offset';

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
     * Cuenta mantenimientos para paginacion con filtros.
     */
    public function count(array $filters = []): int
    {
        $sql =
            'SELECT COUNT(*)
             FROM `maintenance_records` AS m
             INNER JOIN `vehicles` AS v ON v.id = m.vehicle_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Resume costos y fechas segun filtros.
     */
    public function summary(array $filters = []): array
    {
        $sql =
            'SELECT
                COUNT(*) AS total_records,
                COALESCE(SUM(m.`cost`), 0) AS total_cost,
                COALESCE(AVG(m.`cost`), 0) AS avg_cost,
                MAX(m.`service_date`) AS last_service_date
             FROM `maintenance_records` AS m
             INNER JOIN `vehicles` AS v ON v.id = m.vehicle_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'totalRecords' => (int) ($row['total_records'] ?? 0),
            'totalCost' => (float) ($row['total_cost'] ?? 0),
            'avgCost' => (float) ($row['avg_cost'] ?? 0),
            'lastServiceDate' => $row['last_service_date'] ?? null,
        ];
    }

    /**
     * Construye condiciones WHERE segun filtros.
     */
    private function buildFilterSql(array $filters, array &$params): string
    {
        $sql = '';

        if (!empty($filters['search'])) {
            $sql .= ' AND (
                m.`service_type` LIKE :search
                OR v.`license_plate` LIKE :search
                OR v.`brand` LIKE :search
                OR v.`model` LIKE :search
            )';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND m.`vehicle_id` = :vehicle_id';
            $params['vehicle_id'] = (int) $filters['vehicle_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND m.`service_date` >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND m.`service_date` <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUS_OPTIONS, true)) {
            $sql .= ' AND m.`status` = :status';
            $params['status'] = $filters['status'];
        }

        return $sql;
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
             (vehicle_id, service_type, description, service_date, mileage_km, cost, status, next_service_date)
             VALUES (:vehicle_id, :service_type, :description, :service_date, :mileage_km, :cost, :status, :next_service_date)'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'service_type' => $data['service_type'],
            'description' => $data['description'],
            'service_date' => $data['service_date'],
            'mileage_km' => $data['mileage_km'],
            'cost' => $data['cost'],
            'status' => $data['status'],
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
                status = :status,
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
            'status' => $data['status'],
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
            'status' => 'pending',
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
            'status' => trim((string) ($input['status'] ?? 'pending')),
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

        if (!in_array($data['status'], self::STATUS_OPTIONS, true)) {
            $errors['status'] = 'Seleccioná un estado válido.';
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
