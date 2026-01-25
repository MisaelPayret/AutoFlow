<?php
$vehicleImages = $vehicleImages ?? [];
$coverImage = $coverImage ?? null;
$pageTitle = $pageTitle ?? ('Detalle de ' . htmlspecialchars(($vehicle['brand'] ?? 'Vehículo') . ' ' . ($vehicle['model'] ?? ''), ENT_QUOTES, 'UTF-8') . ' | AutoFlow');
$bodyClass = $bodyClass ?? 'dashboard-page vehicles-page vehicle-detail-page';
$imageCount = $imageCount ?? count($vehicleImages);
$carouselMode = $carouselMode ?? ($imageCount >= 4);
$insights = $insights ?? [];

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="vehicle-detail">
    <section class="vehicle-detail__hero">
        <div class="vehicle-detail__cover">
            <?php if (!empty($coverImage)) : ?>
                <img src="<?= htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php else : ?>
                <div>
                    <strong><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?= htmlspecialchars($vehicle['internal_code'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <div class="vehicle-detail__copy">
            <p class="tag">Padrón <?= htmlspecialchars($vehicle['internal_code'], ENT_QUOTES, 'UTF-8'); ?></p>
            <h1><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?= htmlspecialchars($vehicle['license_plate'], ENT_QUOTES, 'UTF-8'); ?> · Año <?= (int) $vehicle['year']; ?></p>
            <div class="vehicle-detail__tags">
                <span class="tag tag-status">Estado: <?= htmlspecialchars($vehicle['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="tag">Transmisión: <?= htmlspecialchars($vehicle['transmission'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="tag">Combustible: <?= htmlspecialchars($vehicle['fuel_type'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="hero-metric">
                <small>Tarifa diaria</small>
                <strong>$<?= number_format((float) $vehicle['daily_rate'], 2); ?></strong>
            </div>
            <div class="hero-actions">
                <a class="btn ghost" href="index.php?route=vehicles">Volver al listado</a>
            </div>
        </div>
    </section>

    <?php if (!empty($insights)) : ?>
        <section class="vehicle-insights">
            <?php foreach ($insights as $insight) :
                $statusKey = preg_replace('/[^a-z-]/', '', strtolower((string) ($insight['status'] ?? 'idle')));
                $statusKey = $statusKey !== '' ? $statusKey : 'idle';
            ?>
                <article class="vehicle-insight vehicle-insight--<?= htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>">
                    <small><?= htmlspecialchars($insight['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                    <strong><?= htmlspecialchars($insight['value'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <p><?= htmlspecialchars($insight['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="vehicle-detail__grid">
        <article class="vehicle-detail__card">
            <h2>Ficha técnica</h2>
            <ul class="vehicle-detail__spec-list">
                <li>
                    <span>Combustible</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars(ucfirst($vehicle['fuel_type']), ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Transmisión</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars(ucfirst($vehicle['transmission']), ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li>
                    <span>Kilometraje</span>
                    <strong class="vehicle-detail__value"><?= number_format((int) $vehicle['mileage_km']); ?> km</strong>
                </li>
                <li>
                    <span>Capacidad carga</span>
                    <strong class="vehicle-detail__value"><?= number_format((int) $vehicle['capacity_kg']); ?> kg</strong>
                </li>
                <li>
                    <span>Pasajeros</span>
                    <strong class="vehicle-detail__value"><?= (int) $vehicle['passenger_capacity']; ?> personas</strong>
                </li>
                <li>
                    <span>Color</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars($vehicle['color'] ?: 'Sin especificar', ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
            </ul>
            <?php if (!empty($vehicle['notes'])) : ?>
                <div class="vehicle-detail__notes">
                    <strong>Notas internas</strong>
                    <p><?= nl2br(htmlspecialchars($vehicle['notes'], ENT_QUOTES, 'UTF-8')); ?></p>
                </div>
            <?php endif; ?>
        </article>

        <article class="vehicle-detail__card">
            <h2>Documentación</h2>
            <ul class="vehicle-detail__spec-list vehicle-detail__spec-list--docs">
                <li class="vehicle-detail__doc-item">
                    <span>VIN</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars($vehicle['vin'] ?: 'No informado', ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li class="vehicle-detail__doc-item">
                    <span>Patente</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars($vehicle['license_plate'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li class="vehicle-detail__doc-item">
                    <span>Fecha compra</span>
                    <strong class="vehicle-detail__value"><?= $vehicle['purchased_at'] ? htmlspecialchars($vehicle['purchased_at'], ENT_QUOTES, 'UTF-8') : 'N/D'; ?></strong>
                </li>
                <li class="vehicle-detail__doc-item">
                    <span>Creado</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars(substr((string) $vehicle['created_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
                <li class="vehicle-detail__doc-item">
                    <span>Actualizado</span>
                    <strong class="vehicle-detail__value"><?= htmlspecialchars(substr((string) $vehicle['updated_at'], 0, 10), ENT_QUOTES, 'UTF-8'); ?></strong>
                </li>
            </ul>
        </article>
    </section>

    <section class="vehicle-gallery <?= $carouselMode ? 'vehicle-gallery--carousel' : ''; ?>" data-gallery="<?= $carouselMode ? 'carousel' : 'grid'; ?>">
        <header>
            <div>
                <h2>Galería</h2>
                <p>Imágenes cargadas para este vehículo.</p>
            </div>
            <a class="btn ghost" href="index.php?route=vehicles/edit&id=<?= (int) $vehicle['id']; ?>">Actualizar fotos</a>
        </header>

        <?php if ($imageCount === 0) : ?>
            <p class="vehicle-gallery__empty">Aún no cargaste imágenes.</p>
        <?php else : ?>
            <div class="vehicle-gallery__track" data-gallery-track>
                <?php foreach ($vehicleImages as $image) : ?>
                    <figure class="vehicle-gallery__item">
                        <img src="<?= htmlspecialchars($image['storage_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="vehicle-lightbox" data-lightbox hidden>
        <div class="vehicle-lightbox__backdrop" data-lightbox-close></div>
        <figure class="vehicle-lightbox__figure">
            <button type="button" class="vehicle-lightbox__close" data-lightbox-close>&times;</button>
            <img src="" alt="Detalle del vehículo" data-lightbox-img>
        </figure>
    </div>
</main>

<?php if (isset($assetBase)) : ?>
    <script src="<?= htmlspecialchars($assetBase . 'Js/vehicle-show.js', ENT_QUOTES, 'UTF-8'); ?>" defer></script>
<?php endif; ?>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>