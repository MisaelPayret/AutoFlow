<?php
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$message = $message ?? null;
$pageTitle = 'Crear cuenta | AutoFlow';
$bodyClass = 'auth-page';
$formAction = 'index.php?route=auth/register.submit';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="auth auth-register">
    <section class="auth-card">
        <h1>Crear cuenta</h1>
        <p class="auth-register__intro">Registrate para acceder al panel del cliente. Podés sumar más roles más adelante.</p>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($formErrors)) : ?>
            <div class="alert alert-error">
                <strong>Revisá los datos:</strong>
                <ul>
                    <?php foreach ($formErrors as $error) : ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" class="auth-form">
            <label for="name">Nombre y apellido</label>
            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($formData['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" minlength="8" required>

            <label for="password_confirmation">Confirmar contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required>

            <button type="submit" class="btn primary">Crear cuenta</button>
            <p class="auth-register__hint">Al registrarte aceptás que el rol asignado será <strong>Cliente</strong>. Más roles llegarán pronto.</p>
        </form>

        <p class="auth-register__switch">¿Ya tenés usuario? <a href="index.php?route=auth/login">Iniciar sesión</a></p>
    </section>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>