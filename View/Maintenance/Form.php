<?php
$isEdit = $isEdit ?? false;
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$vehicles = $vehicles ?? [];
$statusOptions = $statusOptions ?? [];
$pageTitle = $pageTitle ?? (($isEdit ? 'Editar' : 'Nuevo') . ' mantenimiento | AutoFlow');
$bodyClass = $bodyClass ?? 'dashboard-page maintenance-page';
$actionRoute = $isEdit ? 'maintenance/update' : 'maintenance/store';

$hasFieldError = static function (string $key) use ($formErrors): bool {
    return isset($formErrors[$key]) && $formErrors[$key] !== '';
};

$getFieldError = static function (string $key) use ($formErrors): string {
    return $formErrors[$key] ?? '';
};

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="maintenance-form">
    <section class="toolbar">
        <div>
            <h1><?= $isEdit ? 'Editar mantenimiento' : 'Nuevo mantenimiento'; ?></h1>
            <p>Registrá los servicios para mantener la flota al día.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=maintenance">Volver</a>
        </div>
    </section>

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

    <form method="POST" action="index.php?route=<?= $actionRoute; ?>" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($isEdit) : ?>
            <input type="hidden" name="id" value="<?= (int) ($formData['id'] ?? 0); ?>">
        <?php endif; ?>

        <div class="form-field <?= $hasFieldError('vehicle_id') ? 'has-error' : ''; ?>">
            <label for="vehicle_id">Vehículo *</label>
            <select name="vehicle_id" id="vehicle_id" required>
                <option value="">Seleccioná una opción</option>
                <?php foreach ($vehicles as $vehicle) :
                    $label = $vehicle['brand'] . ' ' . $vehicle['model'] . ' · ' . $vehicle['license_plate'];
                ?>
                    <option value="<?= (int) $vehicle['id']; ?>" <?= ((int) ($formData['vehicle_id'] ?? 0) === (int) $vehicle['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($hasFieldError('vehicle_id')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('vehicle_id'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('service_type') ? 'has-error' : ''; ?>">
            <label for="service_type">Tipo de servicio *</label>
            <input type="text" id="service_type" name="service_type" required value="<?= htmlspecialchars($formData['service_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Ej: Cambio de aceite, frenos, revisión.</small>
            <?php if ($hasFieldError('service_type')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('service_type'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('service_date') ? 'has-error' : ''; ?>">
            <label for="service_date">Fecha del servicio *</label>
            <input type="date" id="service_date" name="service_date" required value="<?= htmlspecialchars($formData['service_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Usá la fecha real del taller.</small>
            <?php if ($hasFieldError('service_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('service_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('mileage_km') ? 'has-error' : ''; ?>">
            <label for="mileage_km">Kilometraje</label>
            <input type="number" id="mileage_km" name="mileage_km" min="0" value="<?= htmlspecialchars((string) ($formData['mileage_km'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('mileage_km')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('mileage_km'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('cost') ? 'has-error' : ''; ?>">
            <label for="cost">Costo del servicio (USD)</label>
            <input type="number" id="cost" name="cost" min="0" step="0.01" value="<?= htmlspecialchars((string) ($formData['cost'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('cost')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('cost'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('status') ? 'has-error' : ''; ?>">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= ($formData['status'] ?? 'pending') === $option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $option)), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($hasFieldError('status')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('status'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('next_service_date') ? 'has-error' : ''; ?>">
            <label for="next_service_date">Próximo servicio</label>
            <input type="date" id="next_service_date" name="next_service_date" value="<?= htmlspecialchars($formData['next_service_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Dejalo vacío si no aplica.</small>
            <?php if ($hasFieldError('next_service_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('next_service_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--full <?= $hasFieldError('description') ? 'has-error' : ''; ?>">
            <label for="description">Detalle del trabajo</label>
            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($formData['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <?php if ($hasFieldError('description')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('description'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-actions form-field--full">
            <button type="submit" class="btn primary">Guardar mantenimiento</button>
        </div>
    </form>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>