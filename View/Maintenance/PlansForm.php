<?php
$isEdit = $isEdit ?? false;
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$vehicles = $vehicles ?? [];
$pageTitle = $pageTitle ?? (($isEdit ? 'Editar' : 'Nuevo') . ' plan | AutoFlow');
$bodyClass = $bodyClass ?? 'dashboard-page maintenance-page maintenance-plans-page';
$actionRoute = $isEdit ? 'maintenance/plans/update' : 'maintenance/plans/store';

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
            <h1><?= $isEdit ? 'Editar plan' : 'Nuevo plan'; ?></h1>
            <p>Definí intervalos para anticipar servicios y activar alertas.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=maintenance/plans">Volver</a>
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
            <small class="form-hint">Ej: Cambio de aceite, filtros, alineación.</small>
            <?php if ($hasFieldError('service_type')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('service_type'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('interval_km') ? 'has-error' : ''; ?>">
            <label for="interval_km">Intervalo (km)</label>
            <input type="number" id="interval_km" name="interval_km" min="0" value="<?= htmlspecialchars((string) ($formData['interval_km'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Usá 0 si solo controlás por tiempo.</small>
            <?php if ($hasFieldError('interval_km')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('interval_km'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('interval_months') ? 'has-error' : ''; ?>">
            <label for="interval_months">Intervalo (meses)</label>
            <input type="number" id="interval_months" name="interval_months" min="0" value="<?= htmlspecialchars((string) ($formData['interval_months'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Usá 0 si solo controlás por kilometraje.</small>
            <?php if ($hasFieldError('interval_months')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('interval_months'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('last_service_date') ? 'has-error' : ''; ?>">
            <label for="last_service_date">Último servicio (fecha)</label>
            <input type="date" id="last_service_date" name="last_service_date" value="<?= htmlspecialchars((string) ($formData['last_service_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Opcional si no hay registro.</small>
            <?php if ($hasFieldError('last_service_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('last_service_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('last_service_km') ? 'has-error' : ''; ?>">
            <label for="last_service_km">Último servicio (km)</label>
            <input type="number" id="last_service_km" name="last_service_km" min="0" value="<?= htmlspecialchars((string) ($formData['last_service_km'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Opcional si no hay registro.</small>
            <?php if ($hasFieldError('last_service_km')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('last_service_km'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('next_service_date') ? 'has-error' : ''; ?>">
            <label for="next_service_date">Próximo servicio (fecha)</label>
            <input type="date" id="next_service_date" name="next_service_date" value="<?= htmlspecialchars((string) ($formData['next_service_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Si lo dejás vacío se calcula según intervalo.</small>
            <?php if ($hasFieldError('next_service_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('next_service_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('next_service_km') ? 'has-error' : ''; ?>">
            <label for="next_service_km">Próximo servicio (km)</label>
            <input type="number" id="next_service_km" name="next_service_km" min="0" value="<?= htmlspecialchars((string) ($formData['next_service_km'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Si lo dejás vacío se calcula según intervalo.</small>
            <?php if ($hasFieldError('next_service_km')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('next_service_km'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label>
                <input type="checkbox" name="is_active" value="1" <?= !empty($formData['is_active']) ? 'checked' : ''; ?>>
                Plan activo
            </label>
            <small class="form-hint">Activa alertas y sincronización automática.</small>
        </div>

        <div class="form-field form-field--full <?= $hasFieldError('notes') ? 'has-error' : ''; ?>">
            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars((string) ($formData['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
            <?php if ($hasFieldError('notes')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('notes'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-actions form-field--full">
            <button type="submit" class="btn primary">Guardar plan</button>
        </div>
    </form>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>