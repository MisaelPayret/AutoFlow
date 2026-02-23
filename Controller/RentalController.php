<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/Csrf.php';
require_once dirname(__DIR__) . '/Core/AuthGuard.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/AuditLogModel.php';
require_once dirname(__DIR__) . '/Model/RentalModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

/**
 * Administración de reservas y contratos de alquiler.
 *
 * Expone operaciones CRUD y utilidades de formulario para conservar el estado
 * entre redirecciones.
 */
class RentalController
{
    private Database $database;
    private AuditLogModel $auditLogs;
    private RentalModel $rentalModel;
    private VehicleModel $vehicleModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $connection = $this->database->getConnection();
        $this->rentalModel = new RentalModel($connection);
        $this->vehicleModel = new VehicleModel($connection);
        $this->auditLogs = new AuditLogModel($connection);
    }

    /**
     * Muestra el listado completo con sus estados y vehículos asociados.
     */
    public function index(): void
    {
        AuthGuard::requireRoles(['admin']);

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'vehicle_id' => trim((string) ($_GET['vehicle_id'] ?? '')),
            'date_from' => $this->sanitizeDateFilter($_GET['date_from'] ?? ''),
            'date_to' => $this->sanitizeDateFilter($_GET['date_to'] ?? ''),
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $total = $this->rentalModel->count($filters);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $rentals = $this->rentalModel->search($filters, $perPage, $offset);
        $flashMessage = $_SESSION['rental_flash'] ?? null;
        unset($_SESSION['rental_flash']);

        View::render('Rentals/Index.php', [
            'rentals' => $rentals,
            'flashMessage' => $flashMessage,
            'statusOptions' => $this->rentalModel->getStatusOptions(),
            'vehicles' => $this->vehicleModel->vehicleOptions(),
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    /**
     * Muestra el historial de alquileres de un cliente.
     */
    public function history(): void
    {
        AuthGuard::requireRoles(['admin']);

        $identifier = trim((string) ($_GET['client'] ?? ''));
        if ($identifier === '') {
            $_SESSION['rental_flash'] = 'Seleccioná un cliente para ver el historial.';
            $this->redirectToRoute('rentals');
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $total = $this->rentalModel->countByClient($identifier);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $history = $this->rentalModel->listByClient($identifier, $perPage, $offset);
        $summary = $this->rentalModel->clientSummary($identifier);

        View::render('Rentals/History.php', [
            'history' => $history,
            'summary' => $summary,
            'clientIdentifier' => $identifier,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'pageTitle' => 'Historial de alquileres | AutoFlow',
            'bodyClass' => 'dashboard-page rentals-page rentals-history-page',
        ]);
    }

    /**
     * Prepara el formulario de alta reutilizando datos por defecto o previos.
     */
    public function create(): void
    {
        AuthGuard::requireRoles(['admin']);

        [$formData, $formErrors] = $this->pullFormState();
        if (empty($formData)) {
            $formData = $this->rentalModel->defaultFormData();
        }

        $prefillVehicleId = (int) ($_GET['vehicle_id'] ?? 0);
        if ($prefillVehicleId > 0) {
            $vehicle = $this->vehicleModel->find($prefillVehicleId);
            if ($vehicle) {
                $formData['vehicle_id'] = $prefillVehicleId;
            }
        }

        View::render('Rentals/Form.php', [
            'formData' => $formData,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicleModel->vehicleOptions(),
            'statusOptions' => $this->rentalModel->getStatusOptions(),
            'isEdit' => false,
            'pageTitle' => 'Nuevo alquiler | AutoFlow',
            'bodyClass' => 'dashboard-page rentals-page',
        ]);
    }

    /**
     * Persiste un nuevo alquiler tras normalizar y validar los datos.
     */
    public function store(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $formData = $this->rentalModel->applyDerivedValues(
            $this->rentalModel->normalizeInput($_POST)
        );
        $errors = $this->rentalModel->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('rentals/create');
        }

        $rentalId = $this->rentalModel->create($formData);
        $this->applyOdometerUpdate($rentalId, $formData);
        $this->vehicleModel->updateStatusFromRental((int) $formData['vehicle_id'], (string) $formData['status']);
        $this->logAudit('create', 'rental', $rentalId, null, $formData, 'Alta de alquiler');

        $_SESSION['rental_flash'] = 'Alquiler registrado correctamente.';
        $this->redirectToRoute('rentals');
    }

    /**
     * Carga un alquiler existente junto a las opciones auxiliares del formulario.
     */
    public function edit(): void
    {
        AuthGuard::requireRoles(['admin']);

        $rentalId = (int) ($_GET['id'] ?? 0);
        $rental = $this->rentalModel->find($rentalId);

        if (!$rental) {
            $_SESSION['rental_flash'] = 'Registro de alquiler no encontrado.';
            $this->redirectToRoute('rentals');
        }

        [$oldData, $formErrors] = $this->pullFormState();
        if (!empty($oldData)) {
            $rental = array_merge($rental, $oldData);
        }

        View::render('Rentals/Form.php', [
            'formData' => $rental,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicleModel->vehicleOptions(),
            'statusOptions' => $this->rentalModel->getStatusOptions(),
            'isEdit' => true,
            'pageTitle' => 'Editar alquiler | AutoFlow',
            'bodyClass' => 'dashboard-page rentals-page',
        ]);
    }

    /**
     * Actualiza un alquiler existente recalculando valores derivados.
     */
    public function update(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $rentalId = (int) ($_POST['id'] ?? 0);
        $rental = $this->rentalModel->find($rentalId);
        if (!$rental) {
            $_SESSION['rental_flash'] = 'Registro de alquiler no encontrado.';
            $this->redirectToRoute('rentals');
        }

        $formData = array_merge(
            $rental,
            $this->rentalModel->applyDerivedValues($this->rentalModel->normalizeInput($_POST))
        );

        $errors = $this->rentalModel->validate($formData, $rentalId);
        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('rentals/edit', ['id' => $rentalId]);
        }

        $this->rentalModel->update($rentalId, $formData);
        $this->applyOdometerUpdate($rentalId, $formData);
        $this->vehicleModel->updateStatusFromRental((int) $formData['vehicle_id'], (string) $formData['status']);
        $this->logAudit('update', 'rental', $rentalId, $rental, $formData, 'Edición de alquiler');

        $_SESSION['rental_flash'] = 'Alquiler actualizado.';
        $this->redirectToRoute('rentals');
    }

    /**
     * Elimina el registro indicado siempre que se envíe por POST.
     */
    public function delete(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $rentalId = (int) ($_POST['id'] ?? 0);
        if ($rentalId <= 0) {
            $this->redirectToRoute('rentals');
        }

        $rental = $this->rentalModel->find($rentalId);
        $this->rentalModel->delete($rentalId);
        if (!empty($rental['vehicle_id'])) {
            $this->vehicleModel->updateStatusFromRental((int) $rental['vehicle_id'], 'cancelled');
        }
        $this->logAudit('delete', 'rental', $rentalId, $rental, null, 'Baja de alquiler');

        $_SESSION['rental_flash'] = 'Alquiler eliminado.';
        $this->redirectToRoute('rentals');
    }
    /**
     * Guarda en sesión los datos/errores para mostrarlos tras un redirect.
     */
    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['rental_form_data'] = $data;
        $_SESSION['rental_form_errors'] = $errors;
    }

    /**
     * Recupera y limpia los valores almacenados temporalmente en sesión.
     */
    private function pullFormState(): array
    {
        $data = $_SESSION['rental_form_data'] ?? [];
        $errors = $_SESSION['rental_form_errors'] ?? [];
        unset($_SESSION['rental_form_data'], $_SESSION['rental_form_errors']);
        return [$data, $errors];
    }

    /**
     * Bloquea el acceso a usuarios sin sesión activa.
     */
    /**
     * Se asegura de que la ruta haya sido invocada mediante POST.
     */
    private function assertPostRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('rentals');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $_SESSION['rental_flash'] = 'La sesión expiró. Volvé a intentarlo.';
            $this->redirectToRoute('rentals');
        }
    }

    /**
     * Helper para unificar la lógica de redirecciones internas.
     */
    private function redirectToRoute(string $route, array $params = []): void
    {
        $query = 'index.php?route=' . rawurlencode($route);
        if (!empty($params)) {
            $query .= '&' . http_build_query($params);
        }

        header('Location: ' . $query);
        exit;
    }

    /**
     * Actualiza el kilometraje del vehiculo al cerrar el alquiler.
     */
    private function applyOdometerUpdate(int $rentalId, array $formData): void
    {
        if (($formData['status'] ?? '') !== 'completed') {
            return;
        }

        $vehicleId = (int) ($formData['vehicle_id'] ?? 0);
        $endKm = $formData['odometer_end_km'] ?? null;

        if ($vehicleId <= 0 || $endKm === null) {
            return;
        }

        $userId = $_SESSION['auth_user_id'] ?? null;

        $this->vehicleModel->updateMileage($vehicleId, (int) $endKm);
        $this->vehicleModel->logOdometer($vehicleId, $rentalId, (int) $endKm, $userId, 'Cierre de alquiler');
    }

    /**
     * Registra eventos de auditoria del modulo.
     */
    private function logAudit(string $action, string $entityType, ?int $entityId, ?array $before, ?array $after, ?string $summary): void
    {
        try {
            $this->auditLogs->create([
                'user_id' => $_SESSION['auth_user_id'] ?? null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'summary' => $summary,
                'before_data' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
                'after_data' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $exception) {
            // No-op: no bloquear flujos por auditoria.
        }
    }

    private function sanitizeDateFilter($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        return $date instanceof DateTime && $date->format('Y-m-d') === $value ? $value : '';
    }
}
