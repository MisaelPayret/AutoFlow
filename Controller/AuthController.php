<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/UserModel.php';

class AuthController
{
    private Database $database;
    private UserModel $users;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $this->users = new UserModel($this->database->getConnection());
    }

    public function showLogin(): void
    {
        if (!empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('home');
            return;
        }

        $errorMessage = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);

        $infoMessage = $_SESSION['auth_message'] ?? null;
        unset($_SESSION['auth_message']);

        $oldIdentifier = $_SESSION['auth_identifier'] ?? '';
        unset($_SESSION['auth_identifier']);
        View::render('Auth/Login.php', [
            'identifierValue' => $oldIdentifier,
            'message' => $infoMessage,
            'errorMessage' => $errorMessage,
        ]);
    }

    public function showRegister(): void
    {
        if (!empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('home');
            return;
        }

        $errors = $_SESSION['auth_register_errors'] ?? [];
        $old = $_SESSION['auth_register_old'] ?? [];
        $infoMessage = $_SESSION['auth_register_message'] ?? null;

        unset($_SESSION['auth_register_errors'], $_SESSION['auth_register_old'], $_SESSION['auth_register_message']);

        View::render('Auth/Register.php', [
            'formErrors' => $errors,
            'formData' => $old,
            'message' => $infoMessage,
        ]);
    }

    public function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToLogin();
            return;
        }

        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($identifier === '' || $password === '') {
            $_SESSION['auth_error'] = 'Todos los campos son obligatorios.';
            $_SESSION['auth_identifier'] = $identifier;
            $this->redirectToLogin();
            return;
        }

        $user = $this->users->attemptLogin($identifier, $password);

        if (!$user) {
            $_SESSION['auth_error'] = 'Credenciales inválidas.';
            $_SESSION['auth_identifier'] = $identifier;
            $this->redirectToLogin();
            return;
        }

        $_SESSION['auth_user_id'] = $user['id'];
        $_SESSION['auth_user_name'] = $user['name'];
        $_SESSION['auth_user_role'] = $user['role'];

        unset($_SESSION['auth_identifier']);

        $this->redirectToRoute('home');
    }

    public function register(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirectToRoute('auth/register');
            return;
        }

        $formData = $this->users->normalizeRegistrationInput($_POST);
        $errors = $this->users->validateRegistration($formData);

        if (empty($errors) && $this->users->emailExists($formData['email'])) {
            $errors['email'] = 'Ese correo ya está registrado.';
        }

        if (!empty($errors)) {
            $_SESSION['auth_register_errors'] = $errors;
            $_SESSION['auth_register_old'] = ['name' => $formData['name'], 'email' => $formData['email']];
            $this->redirectToRoute('auth/register');
            return;
        }

        $this->users->create($formData, 'client');

        $_SESSION['auth_message'] = 'Cuenta creada correctamente. Ya podés iniciar sesión.';
        $this->redirectToRoute('auth/login');
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }

        session_start();
        $_SESSION['auth_message'] = 'Sesión cerrada correctamente.';
        $this->redirectToRoute('auth/login');
    }

    private function redirectToLogin(): void
    {
        $this->redirectToRoute('auth/login');
    }

    private function redirectToRoute(string $route): void
    {
        $location = 'index.php?route=' . rawurlencode($route);
        header('Location: ' . $location);
        exit;
    }
}
