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

        return [
            'vehicle_id' => (int) ($input['vehicle_id'] ?? 0),
            'obligation_type' => $type,
            'due_date' => $this->normalizeNullableDate($input['due_date'] ?? null),
            'amount' => $this->normalizeMoney($input['amount'] ?? '0'),
            'status' => $status,
            'paid_at' => $this->normalizeNullableDate($input['paid_at'] ?? null),
            'notes' => trim((string) ($input['notes'] ?? '')),
        ];
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

        return $errors;
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
