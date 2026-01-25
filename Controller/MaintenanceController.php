<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/MaintenanceModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

class MaintenanceController
{
    private Database $database;
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
    }

    public function index(): void
    {
        $this->ensureAuthenticated();

        $records = $this->maintenanceModel->listWithVehicles();
        $flashMessage = $_SESSION['maintenance_flash'] ?? null;
        unset($_SESSION['maintenance_flash']);

        View::render('Maintenance/Index.php', [
            'records' => $records,
            'flashMessage' => $flashMessage,
        ]);
    }

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

        $this->maintenanceModel->create($formData);

        $_SESSION['maintenance_flash'] = 'Mantenimiento registrado correctamente.';
        $this->redirectToRoute('maintenance');
    }

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

        $_SESSION['maintenance_flash'] = 'Mantenimiento actualizado.';
        $this->redirectToRoute('maintenance');
    }

    public function delete(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $recordId = (int) ($_POST['id'] ?? 0);
        if ($recordId <= 0) {
            $this->redirectToRoute('maintenance');
        }

        $this->maintenanceModel->delete($recordId);

        $_SESSION['maintenance_flash'] = 'Registro eliminado.';
        $this->redirectToRoute('maintenance');
    }
    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['maintenance_form_data'] = $data;
        $_SESSION['maintenance_form_errors'] = $errors;
    }

    private function pullFormState(): array
    {
        $data = $_SESSION['maintenance_form_data'] ?? [];
        $errors = $_SESSION['maintenance_form_errors'] ?? [];
        unset($_SESSION['maintenance_form_data'], $_SESSION['maintenance_form_errors']);
        return [$data, $errors];
    }

    private function ensureAuthenticated(): void
    {
        if (empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('auth/login');
        }
    }

    private function assertPostRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('maintenance');
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
}
