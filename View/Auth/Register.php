<?php
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$message = $message ?? null;
$pageTitle = 'Crear cuenta | AutoFlow';
$bodyClass = 'auth-page';
$formAction = 'index.php?route=auth/register.submit';

$hasFieldError = static function (string $key) use ($formErrors): bool {
    return isset($formErrors[$key]) && $formErrors[$key] !== '';
};

$getFieldError = static function (string $key) use ($formErrors): string {
    return $formErrors[$key] ?? '';
};

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
                <strong>Revisa los Campos:</strong>
                <ul>
                    <?php foreach ($formErrors as $error) : ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>" class="auth-form">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-field <?= $hasFieldError('name') ? 'has-error' : ''; ?>">
                <label for="name">Nombre y apellido</label>
                <input type="text" id="name" name="name" autocomplete="name" required value="<?= htmlspecialchars($formData['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($hasFieldError('name')) : ?>
                    <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('name'), ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-field <?= $hasFieldError('email') ? 'has-error' : ''; ?>">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" autocomplete="email" required value="<?= htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <small class="form-hint">Usá un correo que revises con frecuencia.</small>
                <?php if ($hasFieldError('email')) : ?>
                    <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('email'), ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-field <?= $hasFieldError('password') ? 'has-error' : ''; ?>">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
                <small class="form-hint">Mínimo 8 caracteres; una mayúscula ayuda.</small>
                <?php if ($hasFieldError('password')) : ?>
                    <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('password'), ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>

            <div class="form-field <?= $hasFieldError('password_confirmation') ? 'has-error' : ''; ?>">
                <label for="password_confirmation">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                <small class="form-hint">Debe coincidir con la contraseña.</small>
                <?php if ($hasFieldError('password_confirmation')) : ?>
                    <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('password_confirmation'), ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn primary">Crear cuenta</button>
            <p class="auth-register__hint">Al registrarte aceptás que el rol asignado será <strong>Cliente</strong>. Más roles llegarán pronto.</p>
        </form>

        <p class="auth-register__switch">¿Ya tenés usuario? <a href="index.php?route=auth/login">Iniciar sesión</a></p>
    </section>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>