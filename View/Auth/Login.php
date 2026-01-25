<?php
$errorMessage = $errorMessage ?? null;
$identifierValue = $identifierValue ?? '';
$formAction = 'index.php?route=auth/login.submit';
$pageTitle = 'Login | AutoFlow';
$bodyClass = 'auth-page';
$message = $message ?? null;

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="auth auth-login">
    <section class="auth-card">
        <h1>Iniciar sesión</h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)) : ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" class="auth-form">
            <label for="identifier">Usuario o correo</label>
            <input
                type="text"
                id="identifier"
                name="identifier"
                value="<?= htmlspecialchars($identifierValue, ENT_QUOTES, 'UTF-8'); ?>"
                required>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn primary">Entrar</button>
        </form>
    </section>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>