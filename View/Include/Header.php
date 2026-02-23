<?php
$pageTitle = $pageTitle ?? 'AutoFlow';
$bodyClass = trim(($bodyClass ?? '') . ' has-header');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
$assetBase = $scriptDir === '' ? '' : $scriptDir . '/';
$cssPath = $assetBase . 'Css/Global.css';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAuthenticated = !empty($_SESSION['auth_user_id']);
$userName = $_SESSION['auth_user_name'] ?? 'Perfil';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath, ENT_QUOTES, 'UTF-8'); ?>">
</head>

<body class="<?= htmlspecialchars(trim($bodyClass), ENT_QUOTES, 'UTF-8'); ?>">
    <header class="site-header">
        <div class="brand">
            <span class="logo">AF</span>
            <div class="brand-copy">
                <strong>AutoFlow</strong>
                <small>Flujos simples</small>
            </div>
        </div>

        <nav class="site-nav">
            <?php if ($isAuthenticated) : ?>
                <a href="index.php?route=home">Inicio</a>
                <a href="index.php?route=vehicles">Vehículos</a>
                <a href="index.php?route=maintenance">Mantenimientos</a>
                <a href="index.php?route=rentals">Alquileres</a>
                <a href="index.php?route=audit">Auditoria</a>
                <a href="index.php?route=auth/logout">Cerrar sesión</a>
            <?php else : ?>
                <a href="index.php?route=auth/login">Login</a>
                <a href="index.php?route=auth/register">Registrarme</a>
            <?php endif; ?>
        </nav>

        <?php if ($isAuthenticated) : ?>
            <a class="profile-pill" href="index.php?route=home">
                <span class="status-dot"></span>
                <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endif; ?>
    </header>