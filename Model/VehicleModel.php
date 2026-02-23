<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Repositorio central de vehículos incluyendo operaciones de galería.
 */
class VehicleModel extends BaseModel
{
    private const STATUS_OPTIONS = ['available', 'reserved', 'rented', 'maintenance', 'retired'];
    private const TRANSMISSION_OPTIONS = ['manual', 'automatic'];
    private const FUEL_OPTIONS = ['nafta', 'diesel', 'hibrido', 'electrico', 'otro'];

    /**
     * Opciones válidas para el campo `status` según la base de datos.
     */
    public function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    /**
     * Transmisiones soportadas por los formularios.
     */
    public function getTransmissionOptions(): array
    {
        return self::TRANSMISSION_OPTIONS;
    }

    /**
     * Tipos de combustible admitidos.
     */
    public function getFuelOptions(): array
    {
        return self::FUEL_OPTIONS;
    }

    /**
     * Busca vehículos aplicando filtros de texto y estado.
     */
    public function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT v.*, (
                    SELECT `storage_path`
                    FROM `vehicle_images`
                    WHERE `vehicle_id` = v.`id`
                    ORDER BY `position` ASC, `id` ASC
                    LIMIT 1
                ) AS `cover_image`
                FROM `vehicles` v WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);
        $sql .= ' ORDER BY v.`created_at` DESC LIMIT :limit OFFSET :offset';

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
     * Cuenta vehiculos para paginacion con filtros.
     */
    public function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) FROM `vehicles` v WHERE 1=1';
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
                v.`brand` LIKE :search
                OR v.`model` LIKE :search
                OR v.`license_plate` LIKE :search
                OR v.`internal_code` LIKE :search
            )';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUS_OPTIONS, true)) {
            $sql .= ' AND v.`status` = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['year'])) {
            $sql .= ' AND v.`year` = :year';
            $params['year'] = (int) $filters['year'];
        }

        if (!empty($filters['availability'])) {
            if ($filters['availability'] === 'available') {
                $sql .= ' AND v.`status` = :availability_status';
                $params['availability_status'] = 'available';
            } elseif ($filters['availability'] === 'unavailable') {
                $sql .= ' AND v.`status` <> :availability_status';
                $params['availability_status'] = 'available';
            }
        }

        return $sql;
    }

    /**
     * Agrupa la cantidad de vehículos por estado.
     */
    public function countByStatus(): array
    {
        $counts = array_fill_keys(self::STATUS_OPTIONS, 0);
        $statement = $this->pdo->query('SELECT `status`, COUNT(*) AS total FROM `vehicles` GROUP BY `status`');

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $status = $row['status'] ?? '';
            if ($status !== '' && isset($counts[$status])) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /**
     * Minimiza columnas para selects (id + descripción).
     */
    public function vehicleOptions(): array
    {
        $statement = $this->pdo->query('SELECT id, brand, model, license_plate FROM `vehicles` ORDER BY brand ASC, model ASC');
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Valores iniciales de un vehículo nuevo.
     */
    public function defaultData(): array
    {
        return [
            'internal_code' => '',
            'vin' => '',
            'license_plate' => '',
            'brand' => '',
            'model' => '',
            'year' => date('Y'),
            'color' => '',
            'transmission' => 'manual',
            'fuel_type' => 'nafta',
            'mileage_km' => '0',
            'capacity_kg' => '0',
            'passenger_capacity' => '1',
            'daily_rate' => '0',
            'status' => 'available',
            'purchased_at' => '',
            'notes' => '',
        ];
    }

    /**
     * Limpia strings y aplica mayúsculas donde corresponde.
     */
    public function normalizeInput(array $input, array $defaults = []): array
    {
        $base = $defaults ?: $this->defaultData();

        return [
            'internal_code' => trim((string) ($input['internal_code'] ?? $base['internal_code'])),
            'vin' => trim((string) ($input['vin'] ?? $base['vin'])),
            'license_plate' => strtoupper(trim((string) ($input['license_plate'] ?? $base['license_plate']))),
            'brand' => trim((string) ($input['brand'] ?? $base['brand'])),
            'model' => trim((string) ($input['model'] ?? $base['model'])),
            'year' => trim((string) ($input['year'] ?? $base['year'])),
            'color' => trim((string) ($input['color'] ?? $base['color'])),
            'transmission' => trim((string) ($input['transmission'] ?? $base['transmission'])),
            'fuel_type' => trim((string) ($input['fuel_type'] ?? $base['fuel_type'])),
            'mileage_km' => trim((string) ($input['mileage_km'] ?? $base['mileage_km'])),
            'capacity_kg' => trim((string) ($input['capacity_kg'] ?? $base['capacity_kg'])),
            'passenger_capacity' => trim((string) ($input['passenger_capacity'] ?? $base['passenger_capacity'])),
            'daily_rate' => trim((string) ($input['daily_rate'] ?? $base['daily_rate'])),
            'status' => trim((string) ($input['status'] ?? $base['status'])),
            'purchased_at' => trim((string) ($input['purchased_at'] ?? $base['purchased_at'])),
            'notes' => trim((string) ($input['notes'] ?? $base['notes'])),
        ];
    }

    /**
     * Reglas de validación compartidas por create/update.
     */
    public function validate(array $data): array
    {
        $errors = [];

        if ($data['internal_code'] === '') {
            $errors['internal_code'] = 'El código interno es obligatorio.';
        }

        if ($data['license_plate'] === '') {
            $errors['license_plate'] = 'La patente es obligatoria.';
        }

        if ($data['brand'] === '') {
            $errors['brand'] = 'La marca es obligatoria.';
        }

        if ($data['model'] === '') {
            $errors['model'] = 'El modelo es obligatorio.';
        }

        $year = (int) $data['year'];
        if ($year < 1980 || $year > (int) date('Y') + 1) {
            $errors['year'] = 'El año es inválido.';
        }

        if (!in_array($data['transmission'], self::TRANSMISSION_OPTIONS, true)) {
            $errors['transmission'] = 'Transmisión inválida.';
        }

        if (!in_array($data['fuel_type'], self::FUEL_OPTIONS, true)) {
            $errors['fuel_type'] = 'Combustible inválido.';
        }

        if (!in_array($data['status'], self::STATUS_OPTIONS, true)) {
            $errors['status'] = 'Estado inválido.';
        }

        if ($data['daily_rate'] !== '' && (!is_numeric($data['daily_rate']) || (float) $data['daily_rate'] < 0)) {
            $errors['daily_rate'] = 'La tarifa diaria debe ser un valor positivo.';
        }

        if ($data['mileage_km'] !== '' && (!ctype_digit((string) $data['mileage_km']) || (int) $data['mileage_km'] < 0)) {
            $errors['mileage_km'] = 'El kilometraje debe ser un número entero positivo.';
        }

        if ($data['capacity_kg'] !== '' && (!ctype_digit((string) $data['capacity_kg']) || (int) $data['capacity_kg'] < 0)) {
            $errors['capacity_kg'] = 'La capacidad debe ser un número entero positivo.';
        }

        if ($data['passenger_capacity'] !== '' && (!ctype_digit((string) $data['passenger_capacity']) || (int) $data['passenger_capacity'] < 1)) {
            $errors['passenger_capacity'] = 'La capacidad de pasajeros debe ser al menos 1.';
        }

        if ($data['purchased_at'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['purchased_at'])) {
            $errors['purchased_at'] = 'La fecha de compra debe tener formato YYYY-MM-DD.';
        }

        return $errors;
    }

    /**
     * Inserta un vehículo y retorna su ID.
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `vehicles` (
                internal_code, vin, license_plate, brand, model, year, color,
                transmission, fuel_type, mileage_km, capacity_kg, passenger_capacity,
                daily_rate, status, purchased_at, notes
            ) VALUES (
                :internal_code, :vin, :license_plate, :brand, :model, :year, :color,
                :transmission, :fuel_type, :mileage_km, :capacity_kg, :passenger_capacity,
                :daily_rate, :status, :purchased_at, :notes
            )'
        );

        $statement->execute($this->mapToDbParams($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza un vehículo existente.
     */
    public function update(int $id, array $data): void
    {
        $params = $this->mapToDbParams($data);
        $params['id'] = $id;

        $statement = $this->pdo->prepare(
            'UPDATE `vehicles` SET
                `internal_code` = :internal_code,
                `vin` = :vin,
                `license_plate` = :license_plate,
                `brand` = :brand,
                `model` = :model,
                `year` = :year,
                `color` = :color,
                `transmission` = :transmission,
                `fuel_type` = :fuel_type,
                `mileage_km` = :mileage_km,
                `capacity_kg` = :capacity_kg,
                `passenger_capacity` = :passenger_capacity,
                `daily_rate` = :daily_rate,
                `status` = :status,
                `purchased_at` = :purchased_at,
                `notes` = :notes
             WHERE `id` = :id'
        );

        $statement->execute($params);
    }

    /**
     * Cambia solo el estado del vehiculo.
     */
    public function updateStatus(int $vehicleId, string $status): void
    {
        if ($vehicleId <= 0 || !in_array($status, self::STATUS_OPTIONS, true)) {
            return;
        }

        $statement = $this->pdo->prepare('UPDATE `vehicles` SET `status` = :status WHERE `id` = :id');
        $statement->execute([
            'status' => $status,
            'id' => $vehicleId,
        ]);
    }

    /**
     * Elimina definitivamente el registro.
     */
    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM `vehicles` WHERE `id` = :id');
        $statement->execute(['id' => $id]);
    }

    /**
     * Cuenta alquileres asociados al vehiculo (bloquea la eliminación).
     */
    public function countRentalsForVehicle(int $vehicleId): int
    {
        if ($vehicleId <= 0) {
            return 0;
        }

        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM `rentals` WHERE `vehicle_id` = :vehicle_id');
        $statement->execute(['vehicle_id' => $vehicleId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Genera un padrón automático cuando no se ingresa manualmente.
     */
    public function generateInternalCode(): string
    {
        $nextId = (int) $this->pdo->query('SELECT COALESCE(MAX(`id`), 0) + 1 FROM `vehicles`')->fetchColumn();

        for ($i = 0; $i < 5; $i++) {
            $candidate = 'AF-' . str_pad((string) ($nextId + $i), 6, '0', STR_PAD_LEFT);
            if (!$this->internalCodeExists($candidate)) {
                return $candidate;
            }
        }

        return 'AF-' . date('ymdHis');
    }

    /**
     * Actualiza el estado segun el estado del alquiler cuando aplica.
     */
    public function updateStatusFromRental(int $vehicleId, string $rentalStatus): void
    {
        if ($vehicleId <= 0) {
            return;
        }

        $statement = $this->pdo->prepare('SELECT `status` FROM `vehicles` WHERE `id` = :id');
        $statement->execute(['id' => $vehicleId]);
        $currentStatus = (string) $statement->fetchColumn();

        if (in_array($currentStatus, ['maintenance', 'retired'], true)) {
            return;
        }

        $mapping = [
            'confirmed' => 'reserved',
            'in_progress' => 'rented',
            'completed' => 'available',
            'cancelled' => 'available',
            'draft' => 'available',
        ];

        if (!isset($mapping[$rentalStatus])) {
            return;
        }

        $nextStatus = $mapping[$rentalStatus];
        if ($nextStatus === $currentStatus) {
            return;
        }

        $update = $this->pdo->prepare('UPDATE `vehicles` SET `status` = :status WHERE `id` = :id');
        $update->execute([
            'status' => $nextStatus,
            'id' => $vehicleId,
        ]);
    }

    /**
     * Busca un vehículo por ID.
     */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM `vehicles` WHERE `id` = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $vehicle = $statement->fetch(PDO::FETCH_ASSOC);

        return $vehicle ?: null;
    }

    /**
     * Actualiza el kilometraje del vehiculo sin permitir retrocesos.
     */
    public function updateMileage(int $vehicleId, int $mileageKm): void
    {
        if ($vehicleId <= 0 || $mileageKm < 0) {
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE `vehicles` SET `mileage_km` = GREATEST(`mileage_km`, :mileage_km) WHERE `id` = :id'
        );
        $statement->execute([
            'mileage_km' => $mileageKm,
            'id' => $vehicleId,
        ]);
    }

    /**
     * Actualiza las fechas/km estimados del proximo servicio.
     */
    public function updateNextService(int $vehicleId, ?string $nextDate, ?int $nextKm): void
    {
        if ($vehicleId <= 0) {
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE `vehicles`
             SET `next_service_date` = :next_service_date,
                 `next_service_km` = :next_service_km
             WHERE `id` = :id'
        );
        $statement->execute([
            'next_service_date' => $nextDate ?: null,
            'next_service_km' => $nextKm,
            'id' => $vehicleId,
        ]);
    }

    /**
     * Guarda un registro de odometro asociado a un alquiler.
     */
    public function logOdometer(int $vehicleId, ?int $rentalId, int $mileageKm, $userId = null, ?string $note = null): void
    {
        if ($vehicleId <= 0 || $mileageKm < 0) {
            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO `vehicle_odometer_logs` (vehicle_id, rental_id, recorded_by, mileage_km, note)
             VALUES (:vehicle_id, :rental_id, :recorded_by, :mileage_km, :note)'
        );
        $statement->execute([
            'vehicle_id' => $vehicleId,
            'rental_id' => $rentalId ?: null,
            'recorded_by' => $userId ?: null,
            'mileage_km' => $mileageKm,
            'note' => $note,
        ]);
    }

    /**
     * Obtiene la galería completa ordenada.
     */
    public function getImages(int $vehicleId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, file_name, storage_path, position, created_at
             FROM vehicle_images
             WHERE vehicle_id = :vehicle_id
             ORDER BY position ASC, id ASC'
        );
        $statement->execute(['vehicle_id' => $vehicleId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Devuelve el siguiente índice de posición para nuevas fotos.
     */
    public function nextImagePosition(int $vehicleId): int
    {
        $statement = $this->pdo->prepare('SELECT COALESCE(MAX(position), 0) FROM vehicle_images WHERE vehicle_id = :vehicle_id');
        $statement->execute(['vehicle_id' => $vehicleId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Persiste metadatos de una imagen subida.
     */
    public function insertImage(int $vehicleId, string $fileName, string $storagePath, int $position): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO vehicle_images (vehicle_id, file_name, storage_path, position)
             VALUES (:vehicle_id, :file_name, :storage_path, :position)'
        );

        $statement->execute([
            'vehicle_id' => $vehicleId,
            'file_name' => $fileName,
            'storage_path' => $storagePath,
            'position' => $position,
        ]);
    }

    /**
     * Borra una imagen y devuelve su ruta relativa para limpiar el disco.
     */
    public function deleteImage(int $vehicleId, int $imageId): ?string
    {
        $statement = $this->pdo->prepare('SELECT storage_path FROM vehicle_images WHERE vehicle_id = :vehicle_id AND id = :id');
        $statement->execute([
            'vehicle_id' => $vehicleId,
            'id' => $imageId,
        ]);
        $path = $statement->fetchColumn();

        if ($path === false) {
            return null;
        }

        $delete = $this->pdo->prepare('DELETE FROM vehicle_images WHERE vehicle_id = :vehicle_id AND id = :id');
        $delete->execute([
            'vehicle_id' => $vehicleId,
            'id' => $imageId,
        ]);

        return (string) $path;
    }

    /**
     * Reasigna posiciones según el orden enviado desde la UI.
     */
    public function reorderImages(int $vehicleId, array $orderedIds): void
    {
        if (empty($orderedIds)) {
            return;
        }

        $statement = $this->pdo->prepare('UPDATE vehicle_images SET position = :position WHERE vehicle_id = :vehicle_id AND id = :id');
        $position = 1;
        foreach ($orderedIds as $id) {
            $statement->execute([
                'position' => $position++,
                'vehicle_id' => $vehicleId,
                'id' => $id,
            ]);
        }
    }

    /**
     * Genera tarjetas informativas para el detalle/overview.
     */
    public function buildVehicleInsights(int $vehicleId): array
    {
        $insights = [
            'lastMaintenance' => [
                'key' => 'last-maintenance',
                'title' => 'Último mantenimiento',
                'value' => 'Sin registros',
                'description' => 'Agendá el primer servicio.',
                'status' => 'idle',
            ],
            'nextService' => [
                'key' => 'next-service',
                'title' => 'Próximo servicio',
                'value' => 'Sin fecha',
                'description' => 'Cargá un mantenimiento para generar recordatorios.',
                'status' => 'idle',
            ],
            'rentalStatus' => [
                'key' => 'rental-status',
                'title' => 'Alquiler / reserva',
                'value' => 'Disponible',
                'description' => 'Sin alquileres ni reservas activas.',
                'status' => 'ok',
            ],
        ];

        if ($vehicleId <= 0) {
            return array_values($insights);
        }

        $maintenanceStmt = $this->pdo->prepare(
            'SELECT service_type, service_date, next_service_date, mileage_km
             FROM maintenance_records
             WHERE vehicle_id = :vehicle_id
             ORDER BY service_date DESC
             LIMIT 1'
        );
        $maintenanceStmt->execute(['vehicle_id' => $vehicleId]);
        $lastMaintenance = $maintenanceStmt->fetch(PDO::FETCH_ASSOC);

        if ($lastMaintenance) {
            $insights['lastMaintenance']['value'] = $this->formatDateHuman($lastMaintenance['service_date']);
            $insights['lastMaintenance']['description'] = !empty($lastMaintenance['mileage_km'])
                ? sprintf('Registrado a %s km.', number_format((int) $lastMaintenance['mileage_km']))
                : 'Kilometraje no informado.';
            $insights['lastMaintenance']['status'] = 'ok';

            $nextDueAt = $lastMaintenance['next_service_date'] ?? null;
            if ($nextDueAt) {
                $insights['nextService']['value'] = $this->formatDateHuman($nextDueAt, 'Sin fecha');
                $delta = $this->calculateDayDelta($nextDueAt);

                if ($delta !== null) {
                    if ($delta < 0) {
                        $days = abs($delta);
                        $insights['nextService']['description'] = sprintf('Vencido hace %d día%s.', $days, $days === 1 ? '' : 's');
                        $insights['nextService']['status'] = 'alert';
                    } elseif ($delta <= 15) {
                        $insights['nextService']['description'] = sprintf('Faltan %d día%s.', $delta, $delta === 1 ? '' : 's');
                        $insights['nextService']['status'] = 'warning';
                    } else {
                        $insights['nextService']['description'] = sprintf('Servicio previsto en %d día%s.', $delta, $delta === 1 ? '' : 's');
                        $insights['nextService']['status'] = 'ok';
                    }
                } else {
                    $insights['nextService']['description'] = 'Próxima fecha registrada.';
                    $insights['nextService']['status'] = 'info';
                }
            }
        }

        $rentalStmt = $this->pdo->prepare(
            'SELECT client_name, start_date, end_date, status
             FROM rentals
             WHERE vehicle_id = :vehicle_id AND status IN ("confirmed", "in_progress")
             ORDER BY (status = "in_progress") DESC, start_date ASC
             LIMIT 1'
        );
        $rentalStmt->execute(['vehicle_id' => $vehicleId]);
        $rental = $rentalStmt->fetch(PDO::FETCH_ASSOC);

        if ($rental) {
            $status = strtolower((string) ($rental['status'] ?? ''));
            $insights['rentalStatus']['value'] = ucfirst($status);
            $details = [];

            if (!empty($rental['client_name'])) {
                $details[] = 'Cliente: ' . $rental['client_name'];
            }

            $windowParts = [];
            if (!empty($rental['start_date'])) {
                $windowParts[] = 'Inicio ' . $this->formatDateHuman($rental['start_date']);
            }
            if (!empty($rental['end_date'])) {
                $windowParts[] = 'Fin ' . $this->formatDateHuman($rental['end_date']);
            }
            if (!empty($windowParts)) {
                $details[] = implode(' · ', $windowParts);
            }

            $insights['rentalStatus']['description'] = !empty($details) ? implode(' · ', $details) : 'Sin datos del contrato.';
            $insights['rentalStatus']['status'] = $status === 'in_progress' ? 'alert' : 'warning';
        }

        return array_values($insights);
    }

    /**
     * Mapea los datos limpios al formato esperado por PDO.
     */
    private function mapToDbParams(array $data): array
    {
        return [
            'internal_code' => $data['internal_code'],
            'vin' => $data['vin'] ?: null,
            'license_plate' => $data['license_plate'],
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => (int) $data['year'],
            'color' => $data['color'] ?: null,
            'transmission' => $data['transmission'],
            'fuel_type' => $data['fuel_type'],
            'mileage_km' => (int) $data['mileage_km'],
            'capacity_kg' => (int) $data['capacity_kg'],
            'passenger_capacity' => (int) $data['passenger_capacity'],
            'daily_rate' => number_format((float) $data['daily_rate'], 2, '.', ''),
            'status' => $data['status'],
            'purchased_at' => $data['purchased_at'] !== '' ? $data['purchased_at'] : null,
            'notes' => $data['notes'] ?: null,
        ];
    }

    /**
     * Verifica si ya existe un padrón en la base.
     */
    private function internalCodeExists(string $code): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM `vehicles` WHERE `internal_code` = :code');
        $statement->execute(['code' => $code]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Diferencia en días entre hoy y una fecha dada (positivo/futuro).
     */
    private function calculateDayDelta(?string $dateValue): ?int
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            $target = new DateTimeImmutable($dateValue);
            $today = new DateTimeImmutable('today');
            return (int) $today->diff($target)->format('%r%a');
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Formatea fechas a dd/mm/YYYY para mostrarlas en la UI.
     */
    private function formatDateHuman(?string $dateValue, string $fallback = 'N/D'): string
    {
        if (empty($dateValue)) {
            return $fallback;
        }

        try {
            return (new DateTimeImmutable($dateValue))->format('d/m/Y');
        } catch (\Exception $exception) {
            return $fallback;
        }
    }
}
