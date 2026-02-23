<?php
$vehicle = $vehicle ?? null;
$vehicleImages = $vehicleImages ?? [];
$heroImage = $heroImage ?? null;
$specs = $specs ?? [];
$perks = $perks ?? [];
$vehicleDescription = $vehicleDescription ?? '';
$vehicleName = $vehicleName ?? 'Vehículo AutoFlow';
$shareError = $shareError ?? null;
$ctaUrl = $ctaUrl ?? 'mailto:ventas@autoflow.local';
$customerPortalUrl = $customerPortalUrl ?? 'index.php?route=auth/login';

$pageTitle = $vehicleName . ' · AutoFlow';
$bodyClass = 'share-page';
?>
<?php include_once __DIR__ . '/../Include/Header.php'; ?>

<header class="share-hero">
    <?php if ($heroImage && !$shareError) : ?>
        <img class="share-hero__bg" src="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <div class="share-hero__content">
        <span class="share-pill">AutoFlow · Cliente</span>
        <p class="share-hero__eyebrow"><?= $shareError ? 'Ficha no disponible' : 'Disponible para integrar a tu operación'; ?></p>
        <h1><?= $shareError ? 'Lo sentimos' : htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="share-hero__copy">
            <?= $shareError
                ? 'El vehículo que intentaste consultar ya no se encuentra publicado.'
                : htmlspecialchars($vehicleDescription, ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <div class="share-hero__actions">
            <a class="share-btn share-btn--primary" href="<?= htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8'); ?>">Hablar con AutoFlow</a>
            <a class="share-btn share-btn--ghost" href="<?= htmlspecialchars($customerPortalUrl, ENT_QUOTES, 'UTF-8'); ?>">Soy parte del equipo</a>
        </div>
    </div>
</header>

<main class="share-main">
    <?php if ($shareError) : ?>
        <section class="share-card share-card--error">
            <h2>Vehículo no disponible</h2>
            <p><?= htmlspecialchars($shareError, ENT_QUOTES, 'UTF-8'); ?></p>
            <a class="share-btn share-btn--primary" href="<?= htmlspecialchars($customerPortalUrl, ENT_QUOTES, 'UTF-8'); ?>">Ingresar al portal</a>
        </section>
    <?php else : ?>
        <section class="share-details">
            <article class="share-copy">
                <h2>Por qué elegirlo</h2>
                <?php if (!empty($perks)) : ?>
                    <ul class="share-perks">
                        <?php foreach ($perks as $perk) : ?>
                            <li><?= htmlspecialchars($perk, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
            <article class="share-specs">
                <h2>Ficha rápida</h2>
                <div class="share-specs__grid">
                    <?php foreach ($specs as $spec) : ?>
                        <div class="share-spec-card">
                            <small><?= htmlspecialchars($spec['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                            <strong><?= htmlspecialchars($spec['value'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="share-gallery">
            <header>
                <div>
                    <p class="share-pill share-pill--muted">Galería</p>
                    <h2>Conocelo en detalle</h2>
                </div>
                <span><?= count($vehicleImages); ?> imágenes</span>
            </header>
            <?php if (empty($vehicleImages)) : ?>
                <p class="share-gallery__empty">Pronto subiremos imágenes de alta resolución.</p>
            <?php else : ?>
                <div class="share-gallery__grid">
                    <?php foreach ($vehicleImages as $image) : ?>
                        <figure class="share-gallery__item">
                            <img src="<?= htmlspecialchars($image['storage_path'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8'); ?>">
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="share-cta">
            <div>
                <p class="share-pill">Próximos pasos</p>
                <h2>¿Listo para sumarlo a tu flota?</h2>
                <p>Coordinamos demo, contrato y entrega sin complicaciones. Nuestro equipo te acompaña en todo momento.</p>
            </div>
            <div class="share-cta__actions">
                <a class="share-btn share-btn--primary" href="<?= htmlspecialchars($ctaUrl, ENT_QUOTES, 'UTF-8'); ?>">Agendar demo</a>
                <a class="share-btn share-btn--ghost" href="<?= htmlspecialchars($customerPortalUrl, ENT_QUOTES, 'UTF-8'); ?>">Acceder al portal</a>
            </div>
        </section>
    <?php endif; ?>
</main>

<section class="share-footer">
    <div>
        <strong>AutoFlow</strong>
        <p>Movilidad corporativa con datos y soporte humano.</p>
    </div>
    <div class="share-footer__contact">
        <a href="mailto:ventas@autoflow.local">ventas@autoflow.local</a>
        <a href="tel:+598000000">+598 000 000</a>
    </div>
</section>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>