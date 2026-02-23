<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/Csrf.php';
require_once dirname(__DIR__) . '/Core/AuthGuard.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/AuditLogModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';
require_once dirname(__DIR__) . '/Model/VehicleObligationModel.php';

/**
 * Controlador CRUD para obligaciones de vehiculos.
 */
class VehicleObligationController
{
    private Database $database;
    private VehicleObligationModel $obligations;
    private VehicleModel $vehicles;
    private AuditLogModel $auditLogs;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $connection = $this->database->getConnection();
        $this->obligations = new VehicleObligationModel($connection);
        $this->vehicles = new VehicleModel($connection);
        $this->auditLogs = new AuditLogModel($connection);
    }

    public function index(): void
    {
        AuthGuard::requireRoles(['admin']);

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'vehicle_id' => trim((string) ($_GET['vehicle_id'] ?? '')),
            'obligation_type' => trim((string) ($_GET['obligation_type'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'date_from' => $this->sanitizeDateFilter($_GET['date_from'] ?? ''),
            'date_to' => $this->sanitizeDateFilter($_GET['date_to'] ?? ''),
        ];

        if ($filters['vehicle_id'] !== '' && !ctype_digit($filters['vehicle_id'])) {
            $filters['vehicle_id'] = '';
        }

        $typeOptions = $this->obligations->getTypeOptions();
        if (!in_array($filters['obligation_type'], $typeOptions, true)) {
            $filters['obligation_type'] = '';
        }

        $statusOptions = $this->obligations->getStatusOptions();
        if (!in_array($filters['status'], $statusOptions, true)) {
            $filters['status'] = '';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $total = $this->obligations->count($filters);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $records = $this->obligations->listWithVehicles($filters, $perPage, $offset);
        $summary = $this->obligations->summary($filters);
        $flashMessage = $_SESSION['obligation_flash'] ?? null;
        unset($_SESSION['obligation_flash']);

        View::render('Obligations/Index.php', [
            'records' => $records,
            'summary' => $summary,
            'flashMessage' => $flashMessage,
            'vehicles' => $this->vehicles->vehicleOptions(),
            'typeOptions' => $typeOptions,
            'statusOptions' => $statusOptions,
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'pageTitle' => 'Obligaciones | AutoFlow',
            'bodyClass' => 'dashboard-page obligations-page',
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRoles(['admin']);

        [$formData, $formErrors] = $this->pullFormState();
        if (empty($formData)) {
            $formData = $this->obligations->defaultFormData();
        }

        View::render('Obligations/Form.php', [
            'formData' => $formData,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicles->vehicleOptions(),
            'typeOptions' => $this->obligations->getTypeOptions(),
            'statusOptions' => $this->obligations->getStatusOptions(),
            'isEdit' => false,
            'pageTitle' => 'Nueva obligación | AutoFlow',
            'bodyClass' => 'dashboard-page obligations-page',
        ]);
    }

    public function store(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $formData = $this->obligations->normalizeInput($_POST);
        $errors = $this->obligations->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('obligations/create');
        }

        $recordId = $this->obligations->create($formData);
        $this->logAudit('create', 'vehicle_obligation', $recordId, null, $formData, 'Alta de obligación');

        $_SESSION['obligation_flash'] = 'Obligación creada correctamente.';
        $this->redirectToRoute('obligations');
    }

    public function edit(): void
    {
        AuthGuard::requireRoles(['admin']);

        $recordId = (int) ($_GET['id'] ?? 0);
        $record = $this->obligations->find($recordId);

        if (!$record) {
            $_SESSION['obligation_flash'] = 'Obligación no encontrada.';
            $this->redirectToRoute('obligations');
        }

        [$oldData, $formErrors] = $this->pullFormState();
        if (!empty($oldData)) {
            $record = array_merge($record, $oldData);
        }

        View::render('Obligations/Form.php', [
            'formData' => $record,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicles->vehicleOptions(),
            'typeOptions' => $this->obligations->getTypeOptions(),
            'statusOptions' => $this->obligations->getStatusOptions(),
            'isEdit' => true,
            'pageTitle' => 'Editar obligación | AutoFlow',
            'bodyClass' => 'dashboard-page obligations-page',
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $recordId = (int) ($_POST['id'] ?? 0);
        $record = $this->obligations->find($recordId);

        if (!$record) {
            $_SESSION['obligation_flash'] = 'Obligación no encontrada.';
            $this->redirectToRoute('obligations');
        }

        $formData = array_merge($record, $this->obligations->normalizeInput($_POST));
        $errors = $this->obligations->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('obligations/edit', ['id' => $recordId]);
        }

        $this->obligations->update($recordId, $formData);
        $this->logAudit('update', 'vehicle_obligation', $recordId, $record, $formData, 'Edición de obligación');

        $_SESSION['obligation_flash'] = 'Obligación actualizada.';
        $this->redirectToRoute('obligations');
    }

    public function delete(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $recordId = (int) ($_POST['id'] ?? 0);
        if ($recordId <= 0) {
            $this->redirectToRoute('obligations');
        }

        $record = $this->obligations->find($recordId);
        if (!$record) {
            $_SESSION['obligation_flash'] = 'Obligación no encontrada.';
            $this->redirectToRoute('obligations');
        }

        $this->obligations->delete($recordId);
        $this->logAudit('delete', 'vehicle_obligation', $recordId, $record, null, 'Baja de obligación');

        $_SESSION['obligation_flash'] = 'Obligación eliminada.';
        $this->redirectToRoute('obligations');
    }

    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['obligation_form_data'] = $data;
        $_SESSION['obligation_form_errors'] = $errors;
    }

    private function pullFormState(): array
    {
        $data = $_SESSION['obligation_form_data'] ?? [];
        $errors = $_SESSION['obligation_form_errors'] ?? [];
        unset($_SESSION['obligation_form_data'], $_SESSION['obligation_form_errors']);
        return [$data, $errors];
    }

    private function assertPostRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('obligations');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $_SESSION['obligation_flash'] = 'La sesión expiró. Volvé a intentarlo.';
            $this->redirectToRoute('obligations');
        }
    }

    private function redirectToRoute(string $route, array $params = []): void
    {
        $query = 'index.php?route=' . rawurlencode($route);
        if (!empty($params)) {
            $query .= '&' . http_build_query($params);
        }

        header('Location: ' . $query);
        exit;
    }

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
