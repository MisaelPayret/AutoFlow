<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/Csrf.php';
require_once dirname(__DIR__) . '/Core/AuthGuard.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/AuditLogModel.php';
require_once dirname(__DIR__) . '/Model/MaintenancePlanModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

/**
 * Controlador para planes de mantenimiento por vehículo.
 */
class MaintenancePlanController
{
    private Database $database;
    private MaintenancePlanModel $plans;
    private VehicleModel $vehicles;
    private AuditLogModel $auditLogs;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $connection = $this->database->getConnection();
        $this->plans = new MaintenancePlanModel($connection);
        $this->vehicles = new VehicleModel($connection);
        $this->auditLogs = new AuditLogModel($connection);
    }

    public function index(): void
    {
        AuthGuard::requireRoles(['admin']);

        $filters = [
            'vehicle_id' => trim((string) ($_GET['vehicle_id'] ?? '')),
            'is_active' => trim((string) ($_GET['is_active'] ?? '')),
        ];

        if ($filters['vehicle_id'] !== '' && !ctype_digit($filters['vehicle_id'])) {
            $filters['vehicle_id'] = '';
        }
        if (!in_array($filters['is_active'], ['0', '1', ''], true)) {
            $filters['is_active'] = '';
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $total = $this->plans->count($filters);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $plans = $this->plans->listWithVehicles($filters, $perPage, $offset);
        $flashMessage = $_SESSION['maintenance_plan_flash'] ?? null;
        unset($_SESSION['maintenance_plan_flash']);

        View::render('Maintenance/PlansIndex.php', [
            'plans' => $plans,
            'vehicles' => $this->vehicles->vehicleOptions(),
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'flashMessage' => $flashMessage,
            'pageTitle' => 'Planes de mantenimiento | AutoFlow',
            'bodyClass' => 'dashboard-page maintenance-page maintenance-plans-page',
        ]);
    }

    public function create(): void
    {
        AuthGuard::requireRoles(['admin']);

        [$formData, $formErrors] = $this->pullFormState();
        if (empty($formData)) {
            $formData = $this->plans->defaultFormData();
        }

        View::render('Maintenance/PlansForm.php', [
            'formData' => $formData,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicles->vehicleOptions(),
            'isEdit' => false,
            'pageTitle' => 'Nuevo plan | AutoFlow',
            'bodyClass' => 'dashboard-page maintenance-page maintenance-plans-page',
        ]);
    }

    public function store(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $formData = $this->plans->normalizeInput($_POST);
        $errors = $this->plans->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('maintenance/plans/create');
        }

        $planId = $this->plans->create($formData);
        $this->plans->syncVehicleNextService((int) $formData['vehicle_id']);
        $this->logAudit('create', 'maintenance_plan', $planId, null, $formData, 'Alta de plan de mantenimiento');

        $_SESSION['maintenance_plan_flash'] = 'Plan creado correctamente.';
        $this->redirectToRoute('maintenance/plans');
    }

    public function edit(): void
    {
        AuthGuard::requireRoles(['admin']);

        $planId = (int) ($_GET['id'] ?? 0);
        $plan = $this->plans->find($planId);

        if (!$plan) {
            $_SESSION['maintenance_plan_flash'] = 'Plan no encontrado.';
            $this->redirectToRoute('maintenance/plans');
        }

        [$oldData, $formErrors] = $this->pullFormState();
        if (!empty($oldData)) {
            $plan = array_merge($plan, $oldData);
        }

        View::render('Maintenance/PlansForm.php', [
            'formData' => $plan,
            'formErrors' => $formErrors,
            'vehicles' => $this->vehicles->vehicleOptions(),
            'isEdit' => true,
            'pageTitle' => 'Editar plan | AutoFlow',
            'bodyClass' => 'dashboard-page maintenance-page maintenance-plans-page',
        ]);
    }

    public function update(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $planId = (int) ($_POST['id'] ?? 0);
        $plan = $this->plans->find($planId);

        if (!$plan) {
            $_SESSION['maintenance_plan_flash'] = 'Plan no encontrado.';
            $this->redirectToRoute('maintenance/plans');
        }

        $formData = array_merge($plan, $this->plans->normalizeInput($_POST));
        $errors = $this->plans->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('maintenance/plans/edit', ['id' => $planId]);
        }

        $this->plans->update($planId, $formData);
        $this->plans->syncVehicleNextService((int) $formData['vehicle_id']);
        $this->logAudit('update', 'maintenance_plan', $planId, $plan, $formData, 'Edición de plan de mantenimiento');

        $_SESSION['maintenance_plan_flash'] = 'Plan actualizado.';
        $this->redirectToRoute('maintenance/plans');
    }

    public function delete(): void
    {
        AuthGuard::requireRoles(['admin']);
        $this->assertPostRequest();

        $planId = (int) ($_POST['id'] ?? 0);
        if ($planId <= 0) {
            $this->redirectToRoute('maintenance/plans');
        }

        $plan = $this->plans->find($planId);
        if (!$plan) {
            $_SESSION['maintenance_plan_flash'] = 'Plan no encontrado.';
            $this->redirectToRoute('maintenance/plans');
        }

        $this->plans->delete($planId);
        $this->plans->syncVehicleNextService((int) $plan['vehicle_id']);
        $this->logAudit('delete', 'maintenance_plan', $planId, $plan, null, 'Baja de plan de mantenimiento');

        $_SESSION['maintenance_plan_flash'] = 'Plan eliminado.';
        $this->redirectToRoute('maintenance/plans');
    }

    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['maintenance_plan_form_data'] = $data;
        $_SESSION['maintenance_plan_form_errors'] = $errors;
    }

    private function pullFormState(): array
    {
        $data = $_SESSION['maintenance_plan_form_data'] ?? [];
        $errors = $_SESSION['maintenance_plan_form_errors'] ?? [];
        unset($_SESSION['maintenance_plan_form_data'], $_SESSION['maintenance_plan_form_errors']);
        return [$data, $errors];
    }

    private function assertPostRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('maintenance/plans');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            $_SESSION['maintenance_plan_flash'] = 'La sesión expiró. Volvé a intentarlo.';
            $this->redirectToRoute('maintenance/plans');
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
}
