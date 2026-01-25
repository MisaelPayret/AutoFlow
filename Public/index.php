<?php
$basePath = dirname(__DIR__);
$routesFile = $basePath . '/Router/web.php';
$routes = is_file($routesFile) ? require $routesFile : [];

if (!is_array($routes)) {
    echo '❌ El archivo de rutas debe devolver un array asociativo.';
    return;
}

$route = trim((string)($_GET['route'] ?? 'auth/login')) ?: 'auth/login';

if (!isset($routes[$route]) || !is_array($routes[$route])) {
    echo "❌ Ruta '$route' no registrada.";
    return;
}

$controllerName = $routes[$route]['controller'] ?? null;
$action = $routes[$route]['action'] ?? null;

if (!$controllerName || !$action) {
    echo "❌ Ruta '$route' está mal configurada.";
    return;
}

$controllerFile = $basePath . "/Controller/{$controllerName}Controller.php";

if (!is_file($controllerFile)) {
    echo "❌ Controlador '$controllerFile' no encontrado.";
    return;
}

require_once $controllerFile;
$className = $controllerName . 'Controller';

if (!class_exists($className)) {
    echo "❌ Clase '$className' no existe.";
    return;
}

$controller = new $className();

if (!method_exists($controller, $action)) {
    echo "❌ Acción '$action' no encontrada.";
    return;
}

$controller->$action();
