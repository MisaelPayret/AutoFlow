<?php
$isEdit = $isEdit ?? false;
$formData = $formData ?? [];
$formErrors = $formErrors ?? [];
$statusOptions = $statusOptions ?? [];
$transmissionOptions = $transmissionOptions ?? [];
$fuelOptions = $fuelOptions ?? [];
$vehicleImages = $vehicleImages ?? [];
$uploadWarnings = $uploadWarnings ?? [];
$uploadSummary = $uploadSummary ?? [];
$pageTitle = $pageTitle ?? (($isEdit ? 'Editar' : 'Nuevo') . ' vehículo | AutoFlow');
$bodyClass = $bodyClass ?? 'dashboard-page vehicles-page';
$actionRoute = $isEdit ? 'vehicles/update' : 'vehicles/store';

$hasFieldError = static function (string $key) use ($formErrors): bool {
    return isset($formErrors[$key]) && $formErrors[$key] !== '';
};

$getFieldError = static function (string $key) use ($formErrors): string {
    return $formErrors[$key] ?? '';
};

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="vehicle-form">
    <section class="toolbar">
        <div>
            <h1><?= $isEdit ? 'Editar vehículo' : 'Nuevo vehículo'; ?></h1>
            <p>Completá los datos clave para llevar el control de la flota.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=vehicles">Volver</a>
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

    <form method="POST" action="index.php?route=<?= $actionRoute; ?>" class="form-grid" enctype="multipart/form-data" data-gallery-form="true">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($isEdit) : ?>
            <input type="hidden" name="id" value="<?= (int)($formData['id'] ?? 0); ?>">
        <?php endif; ?>

        <div class="form-section form-field--full">
            <h2>Datos básicos</h2>
            <p>Completá la identidad del vehículo y los datos principales.</p>
        </div>

        <div class="form-field <?= $hasFieldError('internal_code') ? 'has-error' : ''; ?>">
            <label for="internal_code">Padron *</label>
            <input type="text" id="internal_code" name="internal_code" value="<?= htmlspecialchars($formData['internal_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Si lo dejás vacío se genera automáticamente.</small>
            <?php if ($hasFieldError('internal_code')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('internal_code'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('license_plate') ? 'has-error' : ''; ?>">
            <label for="license_plate">Patente *</label>
            <input type="text" id="license_plate" name="license_plate" required value="<?= htmlspecialchars($formData['license_plate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Ingresá la patente tal como figura en el vehículo.</small>
            <?php if ($hasFieldError('license_plate')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('license_plate'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('brand') ? 'has-error' : ''; ?>">
            <label for="brand">Marca *</label>
            <input type="text" id="brand" name="brand" required value="<?= htmlspecialchars($formData['brand'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('brand')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('brand'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('model') ? 'has-error' : ''; ?>">
            <label for="model">Modelo *</label>
            <input type="text" id="model" name="model" required value="<?= htmlspecialchars($formData['model'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('model')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('model'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('year') ? 'has-error' : ''; ?>">
            <label for="year">Año *</label>
            <input type="number" id="year" name="year" min="1980" max="<?= (int) date('Y') + 1; ?>" required value="<?= htmlspecialchars($formData['year'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Año del modelo o fabricación.</small>
            <?php if ($hasFieldError('year')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('year'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('color') ? 'has-error' : ''; ?>">
            <label for="color">Color</label>
            <input type="text" id="color" name="color" value="<?= htmlspecialchars($formData['color'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($hasFieldError('color')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('color'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-section form-field--full">
            <h2>Operación</h2>
            <p>Definí el estado operativo, capacidades y tarifa.</p>
        </div>

        <div class="form-field <?= $hasFieldError('status') ? 'has-error' : ''; ?>">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= ($formData['status'] ?? '') === $option ? 'selected' : ''; ?>><?= ucfirst($option); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($hasFieldError('status')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('status'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('daily_rate') ? 'has-error' : ''; ?>">
            <label for="daily_rate">Tarifa diaria (USD)</label>
            <input type="number" step="0.01" min="0" id="daily_rate" name="daily_rate" value="<?= htmlspecialchars($formData['daily_rate'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Tarifa por día sin impuestos.</small>
            <?php if ($hasFieldError('daily_rate')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('daily_rate'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('mileage_km') ? 'has-error' : ''; ?>">
            <label for="mileage_km">Kilometraje</label>
            <input type="number" id="mileage_km" name="mileage_km" min="0" value="<?= htmlspecialchars($formData['mileage_km'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Kilometraje actual del vehículo.</small>
            <?php if ($hasFieldError('mileage_km')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('mileage_km'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('capacity_kg') ? 'has-error' : ''; ?>">
            <label for="capacity_kg">Capacidad aprox. de carga (kg)</label>
            <input type="number" id="capacity_kg" name="capacity_kg" min="0" value="<?= htmlspecialchars($formData['capacity_kg'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Usá 0 si no aplica.</small>
            <?php if ($hasFieldError('capacity_kg')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('capacity_kg'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('passenger_capacity') ? 'has-error' : ''; ?>">
            <label for="passenger_capacity">Cantidad de pasajeros</label>
            <input type="number" id="passenger_capacity" name="passenger_capacity" min="1" value="<?= htmlspecialchars($formData['passenger_capacity'] ?? '1', ENT_QUOTES, 'UTF-8'); ?>">
            <small class="form-hint">Incluye conductor.</small>
            <?php if ($hasFieldError('passenger_capacity')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('passenger_capacity'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('transmission') ? 'has-error' : ''; ?>">
            <label for="transmission">Transmisión</label>
            <select id="transmission" name="transmission">
                <?php foreach ($transmissionOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= ($formData['transmission'] ?? '') === $option ? 'selected' : ''; ?>><?= ucfirst($option); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-hint">Manual o automática.</small>
            <?php if ($hasFieldError('transmission')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('transmission'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <div class="form-field <?= $hasFieldError('fuel_type') ? 'has-error' : ''; ?>">
            <label for="fuel_type">Combustible</label>
            <select id="fuel_type" name="fuel_type">
                <?php foreach ($fuelOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= ($formData['fuel_type'] ?? '') === $option ? 'selected' : ''; ?>><?= ucfirst($option); ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-hint">Nafta, diesel, híbrido o eléctrico.</small>
            <?php if ($hasFieldError('fuel_type')) : ?>
                <small class="form-error"><?= htmlspecialchars($getFieldError('fuel_type'), ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
        </div>

        <details class="form-advanced form-field--full">
            <summary>Avanzado</summary>
            <div class="form-advanced__grid">
                <div class="form-field <?= $hasFieldError('vin') ? 'has-error' : ''; ?>">
                    <label for="vin">VIN</label>
                    <input type="text" id="vin" name="vin" value="<?= htmlspecialchars($formData['vin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <small class="form-hint">17 caracteres sin espacios.</small>
                    <?php if ($hasFieldError('vin')) : ?>
                        <small class="form-error"><?= htmlspecialchars($getFieldError('vin'), ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field <?= $hasFieldError('purchased_at') ? 'has-error' : ''; ?>">
                    <label for="purchased_at">Fecha de compra</label>
                    <input type="date" id="purchased_at" name="purchased_at" value="<?= htmlspecialchars($formData['purchased_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <small class="form-hint">Fecha de compra o alta en la flota.</small>
                    <?php if ($hasFieldError('purchased_at')) : ?>
                        <small class="form-error"><?= htmlspecialchars($getFieldError('purchased_at'), ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-field form-field--full <?= $hasFieldError('notes') ? 'has-error' : ''; ?>">
                    <label for="notes">Notas</label>
                    <textarea id="notes" name="notes" rows="3"><?= htmlspecialchars($formData['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <small class="form-hint">Uso interno. No visible para clientes.</small>
                    <?php if ($hasFieldError('notes')) : ?>
                        <small class="form-error"><?= htmlspecialchars($getFieldError('notes'), ENT_QUOTES, 'UTF-8'); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </details>

        <?php if ($isEdit && !empty($vehicleImages)) : ?>
            <?php $currentCoverId = (int) ($vehicleImages[0]['id'] ?? 0); ?>
            <div class="form-field form-field--full gallery-manager">
                <label>Galería actual</label>
                <p class="gallery-manager__hint">Seleccioná la portada, reordená con los números o marcá las fotos a eliminar.</p>
                <div class="gallery-manager__grid">
                    <?php foreach ($vehicleImages as $image) :
                        $imageId = (int) ($image['id'] ?? 0);
                    ?>
                        <article class="gallery-manager__item" data-image-id="<?= $imageId; ?>">
                            <img src="<?= htmlspecialchars($image['storage_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto del vehículo">
                            <div class="gallery-manager__controls">
                                <label class="gallery-manager__control">
                                    <input type="radio" name="cover_image_id" value="<?= $imageId; ?>" <?= $imageId === $currentCoverId ? 'checked' : ''; ?> data-gallery-cover>
                                    Portada
                                </label>
                                <label class="gallery-manager__control">
                                    Orden
                                    <input type="number" name="image_positions[<?= $imageId; ?>]" value="<?= (int) ($image['position'] ?? 0); ?>" min="1" data-gallery-order>
                                </label>
                                <label class="gallery-manager__control gallery-manager__control--danger">
                                    <input type="checkbox" name="delete_image_ids[]" value="<?= $imageId; ?>" data-gallery-delete>
                                    Eliminar
                                </label>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="gallery_state" value="" data-gallery-state>
            </div>
        <?php endif; ?>

        <?php
        $uploadHelperClasses = ['form-helper'];
        if (!empty($uploadWarnings)) {
            $uploadHelperClasses[] = 'form-helper--error';
        } elseif (!empty($uploadSummary)) {
            $uploadHelperClasses[] = 'form-helper--success';
        } else {
            $uploadHelperClasses[] = 'form-helper--muted';
        }
        ?>
        <div class="form-field form-field--full <?= !empty($uploadWarnings) ? 'has-error' : ''; ?>">
            <label for="vehicle_photos">Fotos del vehículo</label>
            <input type="file" id="vehicle_photos" name="vehicle_photos[]" accept="image/jpeg,image/png" multiple data-file-input>
            <small class="form-hint">JPG/JPEG o PNG · máx. 5 archivos por envío · 2 MB cada uno.</small>
            <div class="<?= implode(' ', $uploadHelperClasses); ?>" data-upload-feedback>
                <?php if (!empty($uploadSummary)) : ?>
                    <p><strong>Última subida:</strong> <?= (int) ($uploadSummary['uploaded'] ?? 0); ?> ok · <?= (int) ($uploadSummary['skipped'] ?? 0); ?> omitidas.</p>
                    <?php if (!empty($uploadSummary['notes'])) : ?>
                        <ul>
                            <?php foreach ($uploadSummary['notes'] as $note) : ?>
                                <li><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else : ?>
                    <p>Seleccioná imágenes para validar formato y tamaño.</p>
                <?php endif; ?>

                <?php if (!empty($uploadWarnings)) : ?>
                    <ul>
                        <?php foreach ($uploadWarnings as $warning) : ?>
                            <li><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions form-field--full">
            <button type="submit" class="btn primary">Guardar vehículo</button>
        </div>
    </form>
</main>

<?php if (isset($assetBase)) : ?>
    <script src="<?= htmlspecialchars($assetBase . 'Js/vehicle-form.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endif; ?>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>