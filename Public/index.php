<?php

// Puerta de entrada del proyecto: carga las rutas y delega a los controladores.
$basePath = dirname(__DIR__);
require_once $basePath . '/Database/Database.php';
require_once $basePath . '/Model/AlertModel.php';
require_once $basePath . '/Core/View.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$routesFile = $basePath . '/Router/web.php';
$routes = is_file($routesFile) ? require $routesFile : [];

if (!is_array($routes)) {
    echo '❌ El archivo de rutas debe devolver un array asociativo.';
    return;
}

$renderNotFound = static function (string $route): void {
    http_response_code(404);
    View::render('Public/NotFound.php', [
        'pageTitle' => 'Pagina no encontrada | AutoFlow',
        'bodyClass' => 'public-page notfound-page',
        'requestPath' => $route,
    ]);
};

// Si no llega parámetro usamos auth/login para forzar autenticación.
$route = trim((string)($_GET['route'] ?? 'auth/login')) ?: 'auth/login';

if (!isset($routes[$route]) || !is_array($routes[$route])) {
    $renderNotFound($route);
    return;
}

$controllerName = $routes[$route]['controller'] ?? null;
$action = $routes[$route]['action'] ?? null;

if (!$controllerName || !$action) {
    $renderNotFound($route);
    return;
}

// Resolvemos el archivo físico del controlador y realizamos validaciones básicas.
$controllerFile = $basePath . "/Controller/{$controllerName}Controller.php";

if (!is_file($controllerFile)) {
    $renderNotFound($route);
    return;
}

require_once $controllerFile;
$className = $controllerName . 'Controller';

if (!class_exists($className)) {
    $renderNotFound($route);
    return;
}

$lastAlertRun = $_SESSION['alerts_last_run'] ?? 0;
if (!empty($_SESSION['auth_user_id']) && (time() - (int) $lastAlertRun) > 3600) {
    try {
        $database = new Database();
        $alertModel = new AlertModel($database->getConnection());
        $alertModel->generateDueAlerts();
        $_SESSION['alerts_last_run'] = time();
    } catch (Throwable $exception) {
        // No-op: evitar que fallas de alertas bloqueen la navegación.
    }
}

$controller = new $className();

if (!method_exists($controller, $action)) {
    $renderNotFound($route);
    return;
}

$controller->$action();
