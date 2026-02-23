<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Maneja obligaciones del vehiculo (patentes, seguros, impuestos).
 */
class VehicleObligationModel extends BaseModel
{
    private const TYPE_OPTIONS = ['registration', 'insurance', 'tax', 'other'];
    private const STATUS_OPTIONS = ['pending', 'paid', 'overdue'];

    public function getTypeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }

    public function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    /**
     * Lista obligaciones con datos del vehiculo.
     */
    public function listWithVehicles(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql =
            'SELECT o.*, v.brand, v.model, v.license_plate
             FROM `vehicle_obligations` AS o
             INNER JOIN `vehicles` AS v ON v.id = o.vehicle_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);
        $sql .= ' ORDER BY o.`due_date` ASC, o.`created_at` DESC LIMIT :limit OFFSET :offset';

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
     * Cuenta obligaciones segun filtros.
     */
    public function count(array $filters = []): int
    {
        $sql =
            'SELECT COUNT(*)
             FROM `vehicle_obligations` AS o
             INNER JOIN `vehicles` AS v ON v.id = o.vehicle_id
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
     * Resumen rápido de estados y montos.
     */
    public function summary(array $filters = []): array
    {
        $sql =
            'SELECT
                COUNT(*) AS total_records,
                COALESCE(SUM(o.`amount`), 0) AS total_amount,
                SUM(CASE WHEN o.`status` = "pending" THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN o.`status` = "overdue" THEN 1 ELSE 0 END) AS overdue_count,
                SUM(CASE WHEN o.`status` = "paid" THEN 1 ELSE 0 END) AS paid_count
             FROM `vehicle_obligations` AS o
             INNER JOIN `vehicles` AS v ON v.id = o.vehicle_id
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
            'totalAmount' => (float) ($row['total_amount'] ?? 0),
            'pendingCount' => (int) ($row['pending_count'] ?? 0),
            'overdueCount' => (int) ($row['overdue_count'] ?? 0),
            'paidCount' => (int) ($row['paid_count'] ?? 0),
        ];
    }

    /**
     * Lista obligaciones por vehiculo.
     */
    public function listByVehicle(int $vehicleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM `vehicle_obligations`
             WHERE `vehicle_id` = :vehicle_id
             ORDER BY `due_date` ASC'
        );
        $statement->execute(['vehicle_id' => $vehicleId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Busca una obligacion por id.
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM `vehicle_obligations` WHERE `id` = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $record = $statement->fetch(PDO::FETCH_ASSOC);

        return $record ?: null;
    }

    /**
     * Crea una obligacion.
     */
    public function create(array $data): int
    {
        $data = $this->applyStatusRules($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO `vehicle_obligations`
            (vehicle_id, obligation_type, due_date, amount, status, paid_at, notes)
            VALUES
            (:vehicle_id, :obligation_type, :due_date, :amount, :status, :paid_at, :notes)'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'obligation_type' => $data['obligation_type'],
            'due_date' => $data['due_date'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'paid_at' => $data['paid_at'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza una obligacion existente.
     */
    public function update(int $id, array $data): void
    {
        $data = $this->applyStatusRules($data);
        $statement = $this->pdo->prepare(
            'UPDATE `vehicle_obligations` SET
                vehicle_id = :vehicle_id,
                obligation_type = :obligation_type,
                due_date = :due_date,
                amount = :amount,
                status = :status,
                paid_at = :paid_at,
                notes = :notes
             WHERE id = :id'
        );

        $statement->execute([
            'vehicle_id' => $data['vehicle_id'],
            'obligation_type' => $data['obligation_type'],
            'due_date' => $data['due_date'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'paid_at' => $data['paid_at'],
            'notes' => $data['notes'],
            'id' => $id,
        ]);
    }

    /**
     * Elimina la obligacion seleccionada.
     */
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM `vehicle_obligations` WHERE `id` = :id');
        $statement->execute(['id' => $id]);
    }

    public function defaultFormData(): array
    {
        return [
            'vehicle_id' => 0,
            'obligation_type' => 'registration',
            'due_date' => date('Y-m-d'),
            'amount' => '0.00',
            'status' => 'pending',
            'paid_at' => null,
            'notes' => '',
        ];
    }

    public function normalizeInput(array $input): array
    {
        $type = trim((string) ($input['obligation_type'] ?? 'registration'));
        if (!in_array($type, self::TYPE_OPTIONS, true)) {
            $type = 'registration';
        }

        $status = trim((string) ($input['status'] ?? 'pending'));
        if (!in_array($status, self::STATUS_OPTIONS, true)) {
            $status = 'pending';
        }

        $data = [
            'vehicle_id' => (int) ($input['vehicle_id'] ?? 0),
            'obligation_type' => $type,
            'due_date' => $this->normalizeNullableDate($input['due_date'] ?? null),
            'amount' => $this->normalizeMoney($input['amount'] ?? '0'),
            'status' => $status,
            'paid_at' => $this->normalizeNullableDate($input['paid_at'] ?? null),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];

        return $this->applyStatusRules($data);
    }

    public function validate(array $data): array
    {
        $errors = [];

        if ($data['vehicle_id'] <= 0) {
            $errors['vehicle_id'] = 'Selecciona un vehiculo valido.';
        }

        if ($data['due_date'] === null) {
            $errors['due_date'] = 'Ingresa una fecha de vencimiento valida.';
        }

        if (!is_numeric($data['amount']) || (float) $data['amount'] < 0) {
            $errors['amount'] = 'El monto debe ser positivo.';
        }

        if ($data['status'] === 'paid' && $data['paid_at'] === null) {
            $errors['paid_at'] = 'Ingresá la fecha de pago.';
        }

        return $errors;
    }

    private function buildFilterSql(array $filters, array &$params): string
    {
        $sql = '';

        if (!empty($filters['search'])) {
            $sql .= ' AND (
                v.`brand` LIKE :search
                OR v.`model` LIKE :search
                OR v.`license_plate` LIKE :search
            )';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['vehicle_id'])) {
            $sql .= ' AND o.`vehicle_id` = :vehicle_id';
            $params['vehicle_id'] = (int) $filters['vehicle_id'];
        }

        if (!empty($filters['obligation_type']) && in_array($filters['obligation_type'], self::TYPE_OPTIONS, true)) {
            $sql .= ' AND o.`obligation_type` = :obligation_type';
            $params['obligation_type'] = $filters['obligation_type'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUS_OPTIONS, true)) {
            $sql .= ' AND o.`status` = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND o.`due_date` >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND o.`due_date` <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        return $sql;
    }

    private function applyStatusRules(array $data): array
    {
        $today = date('Y-m-d');

        if ($data['status'] !== 'paid') {
            $data['paid_at'] = null;
        }

        if ($data['status'] === 'paid' && $data['paid_at'] === null) {
            $data['paid_at'] = $today;
        }

        if ($data['status'] === 'pending' && $data['due_date'] !== null && $data['due_date'] < $today) {
            $data['status'] = 'overdue';
        }

        if ($data['status'] === 'overdue' && $data['due_date'] !== null && $data['due_date'] >= $today) {
            $data['status'] = 'pending';
        }

        return $data;
    }

    private function normalizeMoney($value): string
    {
        $number = is_numeric($value) ? (float) $value : 0.0;
        return number_format($number, 2, '.', '');
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
