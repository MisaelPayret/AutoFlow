<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Gestiona alertas de vencimientos y kilometraje.
 */
class AlertModel extends BaseModel
{
    /**
     * Lista alertas abiertas ordenadas por vencimiento.
     */
    public function listOpen(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM `alerts`
             WHERE `status` = "open"
             ORDER BY `due_date` ASC, `created_at` DESC
             LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Genera alertas por mantenimiento y vencimientos de documentos.
     */
    public function generateDueAlerts(): void
    {
        $this->generateVehicleAlerts();
        $this->generateObligationAlerts();
    }

    /**
     * Crea alertas de vehiculos por kilometraje/fecha y vencimientos.
     */
    private function generateVehicleAlerts(): void
    {
        $statement = $this->pdo->query(
            'SELECT id, brand, model, license_plate, mileage_km, next_service_km, next_service_date,
                    registration_due_date, insurance_due_date
             FROM `vehicles`'
        );

        $vehicles = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $today = date('Y-m-d');

        foreach ($vehicles as $vehicle) {
            $vehicleId = (int) $vehicle['id'];
            $label = trim((string) ($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
            $plate = (string) ($vehicle['license_plate'] ?? '');
            $context = $label !== '' ? $label : $plate;

            $nextServiceDate = $vehicle['next_service_date'] ?? null;
            if ($nextServiceDate && $nextServiceDate <= $today) {
                $this->createIfMissing(
                    'vehicle',
                    $vehicleId,
                    'maintenance_due_date',
                    'Mantenimiento vencido',
                    'Servicio vencido para ' . $context,
                    $nextServiceDate
                );
            }

            $nextServiceKm = $vehicle['next_service_km'] ?? null;
            if ($nextServiceKm !== null && (int) $vehicle['mileage_km'] >= (int) $nextServiceKm) {
                $this->createIfMissing(
                    'vehicle',
                    $vehicleId,
                    'maintenance_due_km',
                    'Mantenimiento por kilometraje',
                    'Supero el kilometraje recomendado (' . $nextServiceKm . ' km) para ' . $context,
                    null
                );
            }

            $registrationDue = $vehicle['registration_due_date'] ?? null;
            if ($registrationDue && $registrationDue <= $today) {
                $this->createIfMissing(
                    'vehicle',
                    $vehicleId,
                    'registration_due',
                    'Patente vencida',
                    'Patente vencida para ' . $context,
                    $registrationDue
                );
            }

            $insuranceDue = $vehicle['insurance_due_date'] ?? null;
            if ($insuranceDue && $insuranceDue <= $today) {
                $this->createIfMissing(
                    'vehicle',
                    $vehicleId,
                    'insurance_due',
                    'Seguro vencido',
                    'Seguro vencido para ' . $context,
                    $insuranceDue
                );
            }
        }
    }

    /**
     * Crea alertas por obligaciones pendientes (patente, seguro, impuestos).
     */
    private function generateObligationAlerts(): void
    {
        $statement = $this->pdo->query(
            'SELECT id, vehicle_id, obligation_type, due_date
             FROM `vehicle_obligations`
             WHERE `status` IN ("pending", "overdue")'
        );
        $items = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $today = date('Y-m-d');

        foreach ($items as $item) {
            $dueDate = $item['due_date'] ?? null;
            if (!$dueDate || $dueDate > $today) {
                continue;
            }

            $type = (string) ($item['obligation_type'] ?? 'other');
            $title = 'Obligacion vencida';
            $message = 'Obligacion pendiente (' . $type . ')';

            $this->createIfMissing(
                'vehicle_obligation',
                (int) $item['id'],
                'obligation_due',
                $title,
                $message,
                $dueDate
            );
        }
    }

    /**
     * Inserta alerta solo si no existe una abierta igual.
     */
    private function createIfMissing(string $entityType, int $entityId, string $alertType, string $title, string $message, ?string $dueDate): void
    {
        $statement = $this->pdo->prepare(
            'SELECT id FROM `alerts`
             WHERE `entity_type` = :entity_type
               AND `entity_id` = :entity_id
               AND `alert_type` = :alert_type
               AND `status` = "open"
             LIMIT 1'
        );
        $statement->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'alert_type' => $alertType,
        ]);

        if ($statement->fetchColumn()) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO `alerts` (entity_type, entity_id, alert_type, title, message, due_date)
             VALUES (:entity_type, :entity_id, :alert_type, :title, :message, :due_date)'
        );
        $insert->execute([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'alert_type' => $alertType,
            'title' => $title,
            'message' => $message,
            'due_date' => $dueDate,
        ]);
    }
}
