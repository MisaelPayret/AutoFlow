<?php

declare(strict_types=1);

/**
 * Tiny helper to render PHP views with a prepared data payload.
 */
final class View
{
    private function __construct() {}

    /**
     * Carga la vista indicada y la alimenta con el arreglo $data.
     */
    public static function render(string $view, array $data = []): void
    {
        $normalized = ltrim($view, '/');
        $viewPath = dirname(__DIR__) . '/View/' . $normalized;

        if (!is_file($viewPath)) {
            echo '❌ Vista no encontrada: ' . htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');
            return;
        }

        extract($data, EXTR_OVERWRITE, 'view');
        include $viewPath;
    }
}
