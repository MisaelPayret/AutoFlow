<?php

declare(strict_types=1);

/**
 * Helpers para validar autenticacion y permisos.
 */
final class AuthGuard
{
    private function __construct() {}

    /**
     * Exige una sesion activa.
     */
    public static function requireLogin(string $redirectRoute = 'auth/login'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['auth_user_id'])) {
            self::redirect($redirectRoute);
        }
    }

    /**
     * Exige que el rol del usuario este permitido.
     */
    public static function requireRoles(array $roles, string $redirectRoute = 'auth/login'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['auth_user_id'])) {
            self::redirect($redirectRoute);
        }

        $role = $_SESSION['auth_user_role'] ?? null;
        if ($role === null || !in_array($role, $roles, true)) {
            $_SESSION['auth_error'] = 'No tenes permisos para acceder a esta seccion.';
            self::redirect('auth/denied');
        }
    }

    private static function redirect(string $route): void
    {
        $location = 'index.php?route=' . rawurlencode($route);
        header('Location: ' . $location);
        exit;
    }
}
