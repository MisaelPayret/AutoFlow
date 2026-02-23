<?php

declare(strict_types=1);

/**
 * Helper simple para tokens CSRF en formularios.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    private function __construct() {}

    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if ($sessionToken === '' || $token === null || $token === '') {
            return false;
        }

        return hash_equals((string) $sessionToken, (string) $token);
    }
}
