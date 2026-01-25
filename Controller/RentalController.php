<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/RentalModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

class RentalController
{
    private Database $database;
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
    }

    public function index(): void
    {
        $this->ensureAuthenticated();

        $rentals = $this->rentalModel->listWithVehicles();
        $flashMessage = $_SESSION['rental_flash'] ?? null;
        unset($_SESSION['rental_flash']);

        View::render('Rentals/Index.php', [
            'rentals' => $rentals,
            'flashMessage' => $flashMessage,
            'statusOptions' => $this->rentalModel->getStatusOptions(),
        ]);
    }

    public function create(): void
    {
        $this->ensureAuthenticated();

        [$formData, $formErrors] = $this->pullFormState();
        if (empty($formData)) {
            $formData = $this->rentalModel->defaultFormData();
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

    public function store(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $formData = $this->rentalModel->applyDerivedValues(
            $this->rentalModel->normalizeInput($_POST)
        );
        $errors = $this->rentalModel->validate($formData);

        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('rentals/create');
        }

        $this->rentalModel->create($formData);

        $_SESSION['rental_flash'] = 'Alquiler registrado correctamente.';
        $this->redirectToRoute('rentals');
    }

    public function edit(): void
    {
        $this->ensureAuthenticated();

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

    public function update(): void
    {
        $this->ensureAuthenticated();
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

        $errors = $this->rentalModel->validate($formData);
        if (!empty($errors)) {
            $this->rememberFormState($formData, $errors);
            $this->redirectToRoute('rentals/edit', ['id' => $rentalId]);
        }

        $this->rentalModel->update($rentalId, $formData);

        $_SESSION['rental_flash'] = 'Alquiler actualizado.';
        $this->redirectToRoute('rentals');
    }

    public function delete(): void
    {
        $this->ensureAuthenticated();
        $this->assertPostRequest();

        $rentalId = (int) ($_POST['id'] ?? 0);
        if ($rentalId <= 0) {
            $this->redirectToRoute('rentals');
        }

        $this->rentalModel->delete($rentalId);

        $_SESSION['rental_flash'] = 'Alquiler eliminado.';
        $this->redirectToRoute('rentals');
    }
    private function rememberFormState(array $data, array $errors): void
    {
        $_SESSION['rental_form_data'] = $data;
        $_SESSION['rental_form_errors'] = $errors;
    }

    private function pullFormState(): array
    {
        $data = $_SESSION['rental_form_data'] ?? [];
        $errors = $_SESSION['rental_form_errors'] ?? [];
        unset($_SESSION['rental_form_data'], $_SESSION['rental_form_errors']);
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
            $this->redirectToRoute('rentals');
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
