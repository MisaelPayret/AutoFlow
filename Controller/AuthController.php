<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/UserModel.php';

/**
 * Controlador responsable del ciclo completo de autenticación de usuarios.
 *
 * Centraliza lógica de inicio/cierre de sesión, registro y manejo de mensajes
 * temporales. Mantener todo aquí simplifica la vista pública y ayuda a razonar
 * sobre los flujos seguros de entrada al sistema.
 */
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

    /**
     * Muestra el formulario de inicio de sesión y repuebla mensajes/inputs previos.
     *
     * Si el usuario ya está autenticado lo redirige al dashboard para evitar que
     * vuelva a ingresar credenciales. También consume los mensajes flash que hayan
     * quedado almacenados en la sesión tras un intento previo.
     */
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

    /**
     * Renderiza el registro de nuevos usuarios gestionando datos temporales.
     *
     * Solo se permite el acceso a usuarios invitados. Se rescatan los errores y
     * valores anteriores para mejorar la UX cuando la validación falla.
     */
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

    /**
     * Procesa el envío del formulario de login.
     *
     * Normaliza entradas, valida presencia mínima, consulta el modelo y define
     * las variables de sesión que identifican al usuario autenticado.
     */
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

    /**
     * Registra un nuevo usuario tipo "client" validando duplicados y formato.
     *
     * Delegamos en el modelo la normalización/validación para mantener el
     * controlador enfocado en el flujo y la redirección según el resultado.
     */
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

    /**
     * Cierra la sesión actual limpiando datos y cookies.
     *
     * Se destruye la sesión para eliminar cualquier rastro de autenticación y
     * luego se inicia nuevamente para setear un mensaje informativo.
     */
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

    /**
     * Azúcar sintáctica para redireccionar al formulario de login.
     */
    private function redirectToLogin(): void
    {
        $this->redirectToRoute('auth/login');
    }

    /**
     * Construye la URL interna y envía el header Location correspondiente.
     *
     * @param string $route Ruta registrada en Router/web.php
     */
    private function redirectToRoute(string $route): void
    {
        $location = 'index.php?route=' . rawurlencode($route);
        header('Location: ' . $location);
        exit;
    }
}
