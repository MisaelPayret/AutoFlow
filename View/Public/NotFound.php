<?php
$pageTitle = $pageTitle ?? 'Pagina no encontrada | AutoFlow';
$bodyClass = $bodyClass ?? 'public-page notfound-page';
$requestPath = $requestPath ?? '';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="public-shell">
    <section class="public-card">
        <span class="badge">Error 404</span>
        <h1>Pagina no encontrada</h1>
        <p>No encontramos la ruta que buscas. Podes volver al inicio o iniciar sesion.</p>
        <?php if (!empty($requestPath)) : ?>
            <p class="notfound-meta">Ruta solicitada: <?= htmlspecialchars($requestPath, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="actions">
            <a class="btn primary" href="index.php?route=home">Ir al inicio</a>
            <a class="btn ghost" href="index.php?route=auth/login">Ir al login</a>
        </div>
    </section>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>