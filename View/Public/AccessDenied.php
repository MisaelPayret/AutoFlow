<?php
$pageTitle = $pageTitle ?? 'Acceso denegado | AutoFlow';
$bodyClass = $bodyClass ?? 'public-page denied-page';
$message = $message ?? 'No tenés permisos para acceder a esta sección.';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="public-shell">
    <section class="public-card">
        <span class="badge">Acceso denegado</span>
        <h1>Ups, no puedes entrar aquí</h1>
        <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="actions">
            <a class="btn primary" href="index.php?route=auth/logout">Cerrar sesión</a>
            <a class="btn ghost" href="index.php?route=auth/login">Ir al login</a>
        </div>
    </section>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>