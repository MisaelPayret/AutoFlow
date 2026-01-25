<?php
$isEdit = $isEdit ?? false;
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$vehicles = $vehicles ?? [];
$statusOptions = $statusOptions ?? [];
$pageTitle = $pageTitle ?? (($isEdit ? 'Editar' : 'Nuevo') . ' alquiler | AutoFlow');
$bodyClass = $bodyClass ?? 'dashboard-page rentals-page';
$actionRoute = $isEdit ? 'rentals/update' : 'rentals/store';

$hasFieldError = static function (string $key) use ($formErrors): bool {
    return isset($formErrors[$key]) && $formErrors[$key] !== '';
};

$getFieldError = static function (string $key) use ($formErrors): string {
    return $formErrors[$key] ?? '';
};

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="rentals-form">
    <section class="toolbar">
        <div>
            <h1><?= $isEdit ? 'Editar alquiler' : 'Nuevo alquiler'; ?></h1>
            <p>Definí los datos del cliente y el estado del contrato.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=rentals">Volver</a>
        </div>
    </section>

    <?php if (!empty($formErrors)) : ?>
        <div class="alert alert-error">
            <strong>Revisá los campos:</strong>
            <ul>
                <?php foreach ($formErrors as $error) : ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="index.php?route=<?= $actionRoute; ?>" class="form-grid">
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

        <div class="form-field <?= $hasFieldError('client_name') ? 'has-error' : ''; ?>">
            <label for="client_name">Nombre del cliente *</label>
            <input type="text" id="client_name" name="client_name" required value="<?= htmlspecialchars($formData['client_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('client_name')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('client_name'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('client_document') ? 'has-error' : ''; ?>">
            <label for="client_document">Documento *</label>
            <input type="text" id="client_document" name="client_document" required value="<?= htmlspecialchars($formData['client_document'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('client_document')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('client_document'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="client_phone">Teléfono</label>
            <input type="text" id="client_phone" name="client_phone" value="<?= htmlspecialchars($formData['client_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-field <?= $hasFieldError('start_date') ? 'has-error' : ''; ?>">
            <label for="start_date">Inicio *</label>
            <input type="date" id="start_date" name="start_date" required value="<?= htmlspecialchars($formData['start_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('start_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('start_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('end_date') ? 'has-error' : ''; ?>">
            <label for="end_date">Fin *</label>
            <input type="date" id="end_date" name="end_date" required value="<?= htmlspecialchars($formData['end_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('end_date')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('end_date'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('daily_rate') ? 'has-error' : ''; ?>">
            <label for="daily_rate">Tarifa diaria (USD)</label>
            <input type="number" id="daily_rate" name="daily_rate" min="0" step="0.01" value="<?= htmlspecialchars((string) ($formData['daily_rate'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('daily_rate')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('daily_rate'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="total_amount">Total del contrato (USD)</label>
            <input type="number" id="total_amount" name="total_amount" min="0" step="0.01" value="<?= htmlspecialchars((string) ($formData['total_amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8'); ?>" readonly>
            <small class="form-hint">Se calcula automáticamente según fechas y tarifa.</small>
        </div>

        <div class="form-field form-field--full">
            <div class="form-helper form-helper--muted" data-rental-summary>
                Seleccioná fechas y tarifa para estimar duración y total.
            </div>
        </div>

        <div class="form-field <?= $hasFieldError('status') ? 'has-error' : ''; ?>">
            <label for="status">Estado *</label>
            <select id="status" name="status" required>
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= (($formData['status'] ?? '') === $option) ? 'selected' : ''; ?>><?= ucfirst(str_replace('_', ' ', $option)); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($hasFieldError('status')) : ?>
                <small class="form-error">&middot; <?= htmlspecialchars($getFieldError('status'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field form-field--full">
            <label for="notes">Notas</label>
            <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-actions form-field--full">
            <button type="submit" class="btn primary">Guardar alquiler</button>
        </div>
    </form>
</main>

<?php if (isset($assetBase)) : ?>
    <script src="<?= htmlspecialchars($assetBase . 'Js/rentals-form.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endif; ?>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>