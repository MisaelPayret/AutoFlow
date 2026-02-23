<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Encapsula reglas de negocio de alquileres y cálculos derivados.
 */
class RentalModel extends BaseModel
{
    private const STATUS_OPTIONS = ['draft', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    private const BLOCKING_STATUSES = ['confirmed', 'in_progress'];

    /**
     * Expone las opciones permitidas por la BD para mostrar en formularios.
     */
    public function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    /**
     * Recupera todos los alquileres con datos esenciales del vehículo.
     */
    public function listWithVehicles(): array
    {
        $statement = $this->pdo->query(
            'SELECT r.*, v.brand, v.model, v.license_plate
             FROM `rentals` AS r
             INNER JOIN `vehicles` AS v ON v.id = r.vehicle_id
             ORDER BY r.created_at DESC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca alquileres aplicando filtros avanzados.
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql =
            'SELECT r.*, v.brand, v.model, v.license_plate
             FROM `rentals` AS r
             INNER JOIN `vehicles` AS v ON v.id = r.vehicle_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);
        $sql .= ' ORDER BY r.`created_at` DESC LIMIT :limit OFFSET :offset';

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
     * Cuenta alquileres para paginacion con filtros.
     */
    public function count(array $filters = []): int
    {
        $sql =
            'SELECT COUNT(*)
             FROM `rentals` AS r
             INNER JOIN `vehicles` AS v ON v.id = r.vehicle_id
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
     * Construye condiciones WHERE segun filtros.
     */
    private function buildFilterSql(array $filters, array &$params): string
    {
        $sql = '';

        if (!empty($filters['search'])) {
            $sql .= ' AND (
                r.`client_name` LIKE :search
                OR r.`client_document` LIKE :search
                OR v.`license_plate` LIKE :search
                OR v.`brand` LIKE :search
                OR v.`model` LIKE :search
            )';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUS_OPTIONS, true)) {
            $sql .= ' AND r.`status` = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND r.`vehicle_id` = :vehicle_id';
            $params['vehicle_id'] = (int) $filters['vehicle_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND r.`start_date` >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND r.`end_date` <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        return $sql;
    }

    /**
     * Obtiene los alquileres más recientes para el dashboard.
     */
    public function recent(int $limit = 4): array
    {
        $statement = $this->pdo->prepare(
            'SELECT r.id, r.client_name, r.start_date, r.end_date, r.status, v.brand, v.model, v.license_plate
             FROM `rentals` AS r
             INNER JOIN `vehicles` AS v ON v.id = r.vehicle_id
             ORDER BY r.created_at DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca por ID devolviendo null si no existe.
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM `rentals` WHERE `id` = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $rental = $statement->fetch(PDO::FETCH_ASSOC);

        return $rental ?: null;
    }

    /**
     * Inserta un nuevo alquiler y regresa la clave primaria asignada.
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `rentals`
            (vehicle_id, client_name, client_document, client_phone, start_date, end_date,
             daily_rate, total_amount, odometer_start_km, odometer_end_km, status, notes)
            VALUES
            (:vehicle_id, :client_name, :client_document, :client_phone, :start_date, :end_date,
             :daily_rate, :total_amount, :odometer_start_km, :odometer_end_km, :status, :notes)'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'client_name' => $data['client_name'],
            'client_document' => $data['client_document'],
            'client_phone' => $data['client_phone'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'daily_rate' => $data['daily_rate'],
            'total_amount' => $data['total_amount'],
            'odometer_start_km' => $data['odometer_start_km'],
            'odometer_end_km' => $data['odometer_end_km'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un registro existente con datos validados.
     */
    public function update(int $id, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE `rentals`
             SET vehicle_id = :vehicle_id,
                 client_name = :client_name,
                 client_document = :client_document,
                 client_phone = :client_phone,
                 start_date = :start_date,
                 end_date = :end_date,
                 daily_rate = :daily_rate,
                 total_amount = :total_amount,
                 odometer_start_km = :odometer_start_km,
                 odometer_end_km = :odometer_end_km,
                 status = :status,
                 notes = :notes
             WHERE id = :id'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'client_name' => $data['client_name'],
            'client_document' => $data['client_document'],
            'client_phone' => $data['client_phone'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'daily_rate' => $data['daily_rate'],
            'total_amount' => $data['total_amount'],
            'odometer_start_km' => $data['odometer_start_km'],
            'odometer_end_km' => $data['odometer_end_km'],
            'status' => $data['status'],
            'notes' => $data['notes'],
            'id' => $id,
        ]);
    }

    /**
     * Elimina un alquiler por completo.
     */
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM `rentals` WHERE `id` = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * Valores iniciales para poblar formularios.
     */
    public function defaultFormData(): array
    {
        return [
            'vehicle_id' => 0,
            'client_name' => '',
            'client_document' => '',
            'client_phone' => '',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+3 days')),
            'daily_rate' => '0.00',
            'total_amount' => '0.00',
            'odometer_start_km' => null,
            'odometer_end_km' => null,
            'status' => 'draft',
            'notes' => '',
        ];
    }

    /**
     * Limpia y castea el arreglo recibido desde $_POST.
     */
    public function normalizeInput(array $input): array
    {
        return [
            'vehicle_id' => (int) ($input['vehicle_id'] ?? 0),
            'client_name' => trim((string) ($input['client_name'] ?? '')),
            'client_document' => trim((string) ($input['client_document'] ?? '')),
            'client_phone' => trim((string) ($input['client_phone'] ?? '')),
            'start_date' => trim((string) ($input['start_date'] ?? '')),
            'end_date' => trim((string) ($input['end_date'] ?? '')),
            'daily_rate' => $this->normalizeMoney($input['daily_rate'] ?? '0'),
            'total_amount' => '0.00',
            'odometer_start_km' => $this->normalizeOdometer($input['odometer_start_km'] ?? null),
            'odometer_end_km' => $this->normalizeOdometer($input['odometer_end_km'] ?? null),
            'status' => trim((string) ($input['status'] ?? 'draft')),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
    }

    /**
     * Revisa reglas de negocio previo a persistir.
     */
    public function validate(array $data, ?int $currentId = null): array
    {
        $errors = [];

        if ($data['vehicle_id'] <= 0) {
            $errors['vehicle_id'] = 'Seleccioná un vehículo.';
        }

        if ($data['client_name'] === '') {
            $errors['client_name'] = 'El nombre del cliente es obligatorio.';
        }

        if ($data['client_document'] === '') {
            $errors['client_document'] = 'El documento es obligatorio.';
        }

        if (!$this->isValidDate($data['start_date'])) {
            $errors['start_date'] = 'Ingresá una fecha de inicio válida.';
        }

        if (!$this->isValidDate($data['end_date'])) {
            $errors['end_date'] = 'Ingresá una fecha de finalización válida.';
        }

        if (empty($errors['start_date']) && empty($errors['end_date'])) {
            if ($data['end_date'] < $data['start_date']) {
                $errors['end_date'] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
            }
        }

        if (!is_numeric($data['daily_rate']) || (float) $data['daily_rate'] < 0) {
            $errors['daily_rate'] = 'La tarifa diaria debe ser positiva.';
        }

        if (!in_array($data['status'], self::STATUS_OPTIONS, true)) {
            $errors['status'] = 'Seleccioná un estado válido.';
        }

        if ($data['odometer_start_km'] !== null && $data['odometer_start_km'] < 0) {
            $errors['odometer_start_km'] = 'El kilometraje inicial debe ser positivo.';
        }

        if ($data['odometer_end_km'] !== null && $data['odometer_end_km'] < 0) {
            $errors['odometer_end_km'] = 'El kilometraje final debe ser positivo.';
        }

        if ($data['odometer_end_km'] !== null && $data['odometer_start_km'] === null) {
            $errors['odometer_start_km'] = 'Ingresá el kilometraje inicial si cargás el final.';
        }

        if (
            $data['odometer_start_km'] !== null && $data['odometer_end_km'] !== null
            && $data['odometer_end_km'] < $data['odometer_start_km']
        ) {
            $errors['odometer_end_km'] = 'El kilometraje final no puede ser menor al inicial.';
        }

        if ($data['status'] === 'completed' && $data['odometer_end_km'] === null) {
            $errors['odometer_end_km'] = 'Ingresá el kilometraje final al completar el alquiler.';
        }

        if (empty($errors['vehicle_id']) && empty($errors['start_date']) && empty($errors['end_date'])) {
            if ($this->hasOverlappingRental(
                (int) $data['vehicle_id'],
                (string) $data['start_date'],
                (string) $data['end_date'],
                $currentId
            )) {
                $errors['start_date'] = 'El vehículo ya está reservado en ese rango.';
            }
        }

        return $errors;
    }

    /**
     * Verifica si existe un alquiler activo que se solape con el rango indicado.
     */
    private function hasOverlappingRental(int $vehicleId, string $startDate, string $endDate, ?int $excludeId = null): bool
    {
        if ($vehicleId <= 0 || !$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            return false;
        }

        $statusPlaceholders = implode(',', array_fill(0, count(self::BLOCKING_STATUSES), '?'));
        $sql =
            'SELECT COUNT(*) FROM `rentals`
             WHERE `vehicle_id` = ?
               AND `status` IN (' . $statusPlaceholders . ')
               AND NOT (`end_date` < ? OR `start_date` > ?)';

        $params = array_merge([$vehicleId], self::BLOCKING_STATUSES, [$startDate, $endDate]);

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND `id` <> ?';
            $params[] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Calcula duración y monto total para evitar duplicar lógica en el controlador.
     */
    public function applyDerivedValues(array $data): array
    {
        $durationDays = $this->calculateDurationDays($data['start_date'], $data['end_date']);
        $dailyRate = (float) $data['daily_rate'];
        $totalAmount = $durationDays > 0 ? $durationDays * $dailyRate : 0.0;

        $data['duration_days'] = $durationDays;
        $data['total_amount'] = $this->normalizeMoney($totalAmount);

        return $data;
    }

    /**
     * Cuenta alquileres cuyo estado esté dentro del arreglo provisto.
     */
    public function countByStatuses(array $statuses): int
    {
        if (empty($statuses)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `rentals` WHERE `status` IN ($placeholders)"
        );
        $statement->execute($statuses);

        return (int) $statement->fetchColumn();
    }

    /**
     * Suma ingresos confirmados en la ventana de días indicada.
     */
    public function sumRevenueLastDays(int $days): float
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(`total_amount`), 0) FROM `rentals`
             WHERE `start_date` >= DATE_SUB(CURDATE(), INTERVAL :days DAY)'
        );
        $statement->bindValue(':days', $days, PDO::PARAM_INT);
        $statement->execute();

        return (float) $statement->fetchColumn();
    }

    /**
     * Promedia la duración de alquileres activos/completados.
     */
    public function averageDurationDays(): float
    {
        $statement = $this->pdo->query(
            "SELECT COALESCE(AVG(DATEDIFF(`end_date`, `start_date`) + 1), 0)
             FROM `rentals`
             WHERE `status` IN ('confirmed', 'in_progress', 'completed')"
        );

        return (float) $statement->fetchColumn();
    }

    /**
     * Convierte cualquier valor aceptado en string monetario con 2 decimales.
     */
    private function normalizeMoney($value): string
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        return number_format($number, 2, '.', '');
    }

    /**
     * Normaliza un valor de odometro a int o null.
     */
    private function normalizeOdometer($value): ?int
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

    /**
     * Calcula días corridos inclusive para dos fechas válidas.
     */
    private function calculateDurationDays(string $start, string $end): int
    {
        if (!$this->isValidDate($start) || !$this->isValidDate($end)) {
            return 0;
        }

        $startDate = new DateTime($start);
        $endDate = new DateTime($end);

        if ($endDate < $startDate) {
            return 0;
        }

        $difference = $startDate->diff($endDate)->days;
        return max(1, $difference + 1);
    }

    /**
     * Comprueba formato YYYY-MM-DD.
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
