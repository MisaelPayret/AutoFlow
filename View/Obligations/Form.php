<?php
$isEdit = $isEdit ?? false;
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$vehicles = $vehicles ?? [];
$typeOptions = $typeOptions ?? [];
$statusOptions = $statusOptions ?? [];
$pageTitle = $pageTitle ?? (($isEdit ? 'Editar' : 'Nueva') . ' obligación | AutoFlow');
$bodyClass = $bodyClass ?? 'dashboard-page obligations-page';
$actionRoute = $isEdit ? 'obligations/update' : 'obligations/store';

$hasFieldError = static function (string $key) use ($formErrors): bool {
    return isset($formErrors[$key]) && $formErrors[$key] !== '';
};

$getFieldError = static function (string $key) use ($formErrors): string {
    return $formErrors[$key] ?? '';
};

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="obligations-form">
    <section class="toolbar">
        <div>
            <h1><?= $isEdit ? 'Editar obligación' : 'Nueva obligación'; ?></h1>
            <p>Registrá vencimientos y controlá su pago.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=obligations">Volver</a>
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

        <div class="form-field">
            <label for="obligation_type">Tipo</label>
            <select id="obligation_type" name="obligation_type">
                <?php foreach ($typeOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= ($formData['obligation_type'] ?? 'registration') === $option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field <?= $hasFieldError('due_date') ? 'has-error' : ''; ?>">
            <label for="due_date">Vencimiento *</label>
            <input type="date" id="due_date" name="due_date" required value="<?= htmlspecialchars((string) ($formData['due_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('due_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('due_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('amount') ? 'has-error' : ''; ?>">
            <label for="amount">Monto (USD)</label>
            <input type="number" id="amount" name="amount" min="0" step="0.01" value="<?= htmlspecialchars((string) ($formData['amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Dejalo en 0 si no aplica.</small>
            <?php if ($hasFieldError('amount')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('amount'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('status') ? 'has-error' : ''; ?>">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= ($formData['status'] ?? 'pending') === $option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($hasFieldError('status')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('status'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('paid_at') ? 'has-error' : ''; ?>">
            <label for="paid_at">Fecha de pago</label>
            <input type="date" id="paid_at" name="paid_at" value="<?= htmlspecialchars((string) ($formData['paid_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Completá solo si ya está paga.</small>
            <?php if ($hasFieldError('paid_at')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('paid_at'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--full">
            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars((string) ($formData['notes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-actions form-field--full">
            <button type="submit" class="btn primary">Guardar obligación</button>
        </div>
    </form>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>