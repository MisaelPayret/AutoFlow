<?php
$pageTitle = 'Panel | AutoFlow';
$bodyClass = 'home-page dashboard-page';
$stats = $stats ?? [];
$recentRentals = $recentRentals ?? [];
$upcomingMaintenance = $upcomingMaintenance ?? [];
$userName = $_SESSION['auth_user_name'] ?? 'Administrador';
$totalVehicles = $stats['totalVehicles'] ?? 0;
$availableVehicles = $stats['availableVehicles'] ?? 0;
$maintenanceVehicles = $stats['maintenanceVehicles'] ?? 0;
$activeRentals = $stats['activeRentals'] ?? 0;
$fleetUtilization = $stats['utilization'] ?? ($totalVehicles > 0 ? round(($activeRentals / $totalVehicles) * 100) : 0);
$activeCoverage = $totalVehicles > 0 ? round(($activeRentals / max(1, $totalVehicles)) * 100) : 0;
$rentalRevenue30d = $stats['rentalRevenue30d'] ?? 0.0;
$maintenanceSpend30d = $stats['maintenanceSpend30d'] ?? 0.0;
$avgRentalDuration = $stats['avgRentalDuration'] ?? 0;
$overdueMaintenance = $stats['overdueMaintenance'] ?? 0;
$draftRentals = $stats['draftRentals'] ?? 0;

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="dashboard">
    <section class="dashboard-hero">
        <div>
            <span class="badge">Panel principal</span>
            <h1>Hola <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?> 👋</h1>
            <p>Revisa el estado de la flota, alquileres activos y mantenimientos próximos.</p>
        </div>
        <div class="hero-metric">
            <small>Utilización de flota</small>
            <strong><?= $fleetUtilization; ?>%</strong>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <small>Total de vehículos</small>
            <strong><?= $totalVehicles; ?></strong>
            <span><?= $availableVehicles; ?> disponibles</span>
        </article>
        <article class="stat-card">
            <small>En mantenimiento</small>
            <strong><?= $maintenanceVehicles; ?></strong>
            <span>Revisar tareas</span>
        </article>
        <article class="stat-card">
            <small>Alquileres activos</small>
            <strong><?= $activeRentals; ?></strong>
            <span><?= $activeCoverage; ?>% de la flota</span>
        </article>
        <article class="stat-card">
            <small>Ingresos últimos 30 días</small>
            <strong>$<?= number_format($rentalRevenue30d, 0, ',', '.'); ?></strong>
            <span>Estimado por contratos</span>
        </article>
        <article class="stat-card">
            <small>Costo mantenimiento 30 días</small>
            <strong>$<?= number_format($maintenanceSpend30d, 0, ',', '.'); ?></strong>
            <span>Duración media: <?= (int) $avgRentalDuration; ?> días</span>
        </article>
        <article class="stat-card">
            <small>Servicios atrasados</small>
            <strong><?= $overdueMaintenance; ?></strong>
            <span>Agendá cuanto antes</span>
        </article>
        <article class="stat-card">
            <small>Alquileres en borrador</small>
            <strong><?= $draftRentals; ?></strong>
            <span>Listos para confirmar</span>
        </article>
    </section>

    <section class="data-panels">
        <article class="data-card">
            <header>
                <h2>Mantenimientos próximos</h2>
                <a href="index.php?route=maintenance" class="link-muted">Ver todos</a>
            </header>
            <?php if (empty($upcomingMaintenance)) : ?>
                <p class="empty-state">No hay mantenimientos programados por ahora.</p>
            <?php else : ?>
                <ul class="data-list">
                    <?php foreach ($upcomingMaintenance as $maintenance) : ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($maintenance['service_type'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars($maintenance['brand'] . ' ' . $maintenance['model'], ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($maintenance['license_plate'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <span class="tag"><?= htmlspecialchars($maintenance['next_service_date'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="data-card">
            <header>
                <h2>Alquileres recientes</h2>
                <a href="index.php?route=rentals/create" class="link-muted">Registrar</a>
            </header>
            <?php if (empty($recentRentals)) : ?>
                <p class="empty-state">Aún no registraste alquileres.</p>
            <?php else : ?>
                <ul class="data-list">
                    <?php foreach ($recentRentals as $rental) : ?>
                        <li>
                            <div>
                                <strong><?= htmlspecialchars($rental['client_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?= htmlspecialchars($rental['brand'] . ' ' . $rental['model'], ENT_QUOTES, 'UTF-8'); ?> · <?= htmlspecialchars($rental['license_plate'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <span class="tag tag-status">
                                <?= htmlspecialchars(str_replace('_', ' ', $rental['status']), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>