<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/AuditLogModel.php';
require_once dirname(__DIR__) . '/Model/MaintenanceModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

/**
 * Controlador CRUD para los registros de mantenimiento.
 *
 * Se encarga de validar autenticación, preparar formularios y manejar el
 * estado temporal entre solicitudes.
 */
class MaintenanceController
{
    private Database $database;
    private AuditLogModel $auditLogs;
    private MaintenanceModel $maintenanceModel;
    private VehicleModel $vehicleModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $connection = $this->database->getConnection();
        $this->maintenanceModel = new MaintenanceModel($connection);
        $this->vehicleModel = new VehicleModel($connection);
        $this->auditLogs = new AuditLogModel($connection);
    }

    /**
     * Lista todos los registros junto con la información básica del vehículo.
     */
    public function index(): void
    {
        $this->ensureAuthenticated();

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'vehicle_id' => trim((string) ($_GET['vehicle_id'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $total = $this->maintenanceModel->count($filters);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $records = $this->maintenanceModel->search($filters, $perPage, $offset);
        $flashMessage = $_SESSION['maintenance_flash'] ?? null;
        unset($_SESSION['maintenance_flash']);

        View::render('Maintenance/Index.php', [
            'records' => $records,
            'flashMessage' => $flashMessage,
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
     * Prepara el formulario de alta aplicando datos por defecto o previos.
     */
    public function create(): void
    {
        $this->ensureAuthenticated();

        [$formData, $formErrors] = $this->pullFormState();
        if (empty($formData)) {
            $formData = $this->maintenanceModel->defaultFormData();
        }

        View::render('Maintenance/Form.php', [
            'formData' => $formData,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicleModel->vehicleOptions(),
            'isEdit' => false,
            'pageTitle' => 'Registrar mantenimiento | AutoFlow',
            'bodyClass' => 'dashboard-page maintenance-page',
        ]);
    }

    /**
     * Persiste un nuevo mantenimiento después de validar los datos recibidos.
     */
    public function store(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $formData = $this->maintenanceModel->normalizeInput($_POST);
        $errors = $this->maintenanceModel->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('maintenance/create');
        }

        $recordId = $this->maintenanceModel->create($formData);
        $this->logAudit('create', 'maintenance', $recordId, null, $formData, 'Alta de mantenimiento');

        $_SESSION['maintenance_flash'] = 'Mantenimiento registrado correctamente.';
        $this->redirectToRoute('maintenance');
    }

    /**
     * Carga un registro existente para edición y reinyecta datos antiguos.
     */
    public function edit(): void
    {
        $this->ensureAuthenticated();

        $recordId = (int) ($_GET['id'] ?? 0);
        $record = $this->maintenanceModel->find($recordId);

        if (!$record) {
            $_SESSION['maintenance_flash'] = 'Registro no encontrado.';
            $this->redirectToRoute('maintenance');
        }

        [$oldData, $formErrors] = $this->pullFormState();
        if (!empty($oldData)) {
            $record = array_merge($record, $oldData);
        }

        View::render('Maintenance/Form.php', [
            'formData' => $record,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicleModel->vehicleOptions(),
            'isEdit' => true,
            'pageTitle' => 'Editar mantenimiento | AutoFlow',
            'bodyClass' => 'dashboard-page maintenance-page',
        ]);
    }

    /**
     * Actualiza un mantenimiento existente asegurando integridad de datos.
     */
    public function update(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $recordId = (int) ($_POST['id'] ?? 0);
        $record = $this->maintenanceModel->find($recordId);
        if (!$record) {
            $_SESSION['maintenance_flash'] = 'Registro no encontrado.';
            $this->redirectToRoute('maintenance');
        }

        $formData = array_merge($record, $this->maintenanceModel->normalizeInput($_POST));
        $errors = $this->maintenanceModel->validate($formData);
        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('maintenance/edit', ['id' => $recordId]);
        }

        $this->maintenanceModel->update($recordId, $formData);
        $this->logAudit('update', 'maintenance', $recordId, $record, $formData, 'Edición de mantenimiento');

        $_SESSION['maintenance_flash'] = 'Mantenimiento actualizado.';
        $this->redirectToRoute('maintenance');
    }

    /**
     * Elimina el mantenimiento indicado mediante una solicitud POST.
     */
    public function delete(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $recordId = (int) ($_POST['id'] ?? 0);
        if ($recordId <= 0) {
            $this->redirectToRoute('maintenance');
        }

        $record = $this->maintenanceModel->find($recordId);
        $this->maintenanceModel->delete($recordId);
        $this->logAudit('delete', 'maintenance', $recordId, $record, null, 'Baja de mantenimiento');

        $_SESSION['maintenance_flash'] = 'Registro eliminado.';
        $this->redirectToRoute('maintenance');
    }
    /**
     * Guarda en sesión los datos de formulario para mostrarlos tras redirect.
     */
    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['maintenance_form_data'] = $data;
        $_SESSION['maintenance_form_errors'] = $errors;
    }

    /**
     * Recupera y consume los datos/errores almacenados en la sesión.
     */
    private function pullFormState(): array
    {
        $data = $_SESSION['maintenance_form_data'] ?? [];
        $errors = $_SESSION['maintenance_form_errors'] ?? [];
        unset($_SESSION['maintenance_form_data'], $_SESSION['maintenance_form_errors']);
        return [$data, $errors];
    }

    /**
     * Garantiza que solo usuarios autenticados accedan a estas acciones.
     */
    private function ensureAuthenticated(): void
    {
        if (empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('auth/login');
        }
    }

    /**
     * Protección básica para que ciertas rutas solo acepten POST.
     */
    private function assertPostRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('maintenance');
        }
    }

    /**
     * Redirige construyendo una query con parámetros opcionales.
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
}
