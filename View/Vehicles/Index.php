<?php
$pageTitle = 'Vehículos | AutoFlow';
$bodyClass = 'dashboard-page vehicles-page';
$statusCounts = $statusCounts ?? [];
$statusOptions = $statusOptions ?? [];
$search = $search ?? '';
$status = $status ?? '';
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];
$flashMessage = $flashMessage ?? null;
$uploadWarnings = $uploadWarnings ?? [];
$uploadSummary = $uploadSummary ?? [];

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="vehicles">
    <section class="toolbar">
        <div>
            <h1>Vehículos</h1>
            <p>Gestioná la flota, aplicá filtros rápidos y mantené la información actualizada.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn primary" href="index.php?route=vehicles/create">+ Nuevo vehículo</a>
        </div>
    </section>

    <?php if (!empty($flashMessage)) : ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($uploadSummary)) : ?>
        <div class="alert alert-info">
            <strong>Última subida:</strong>
            <p><?= (int) ($uploadSummary['uploaded'] ?? 0); ?> ok · <?= (int) ($uploadSummary['skipped'] ?? 0); ?> omitidas.</p>
            <?php if (!empty($uploadSummary['notes'])) : ?>
                <ul>
                    <?php foreach ($uploadSummary['notes'] as $note) : ?>
                        <li><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($uploadWarnings)) : ?>
        <div class="alert alert-warning">
            <strong>Fotos:</strong>
            <ul>
                <?php foreach ($uploadWarnings as $warning) : ?>
                    <li><?= htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="GET" action="index.php" class="filter-form">
        <input type="hidden" name="route" value="vehicles">
        <div class="filter-group">
            <label for="search">Buscar</label>
            <input type="text" id="search" name="search" placeholder="Marca, modelo, patente..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="filter-group">
            <label for="status">Estado</label>
            <select name="status" id="status">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= $status === $option ? 'selected' : ''; ?>><?= ucfirst($option); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn ghost">Filtrar</button>
    </form>

    <section class="status-summary">
        <?php foreach ($statusOptions as $option) :
            $count = $statusCounts[$option] ?? 0; ?>
            <article>
                <small><?= ucfirst($option); ?></small>
                <strong><?= $count; ?></strong>
            </article>
        <?php endforeach; ?>
    </section>

    <?php if (empty($vehicles)) : ?>
        <p class="empty-state">Todavía no cargaste vehículos. Usá el botón "Nuevo vehículo" para empezar.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Padrón</th>
                        <th>Vehículo</th>
                        <th>Patente</th>
                        <th>Km</th>
                        <th>Carga (kg)</th>
                        <th>Pasajeros</th>
                        <th>Estado</th>
                        <th>Tarifa</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle) : ?>
                        <tr>
                            <td data-label="Padrón"><?= htmlspecialchars($vehicle['internal_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Vehículo" class="vehicle-cell">
                                <?php
                                $initials = strtoupper(substr((string) ($vehicle['brand'] ?? ''), 0, 1) . substr((string) ($vehicle['model'] ?? ''), 0, 1));
                                $initials = $initials !== '' ? $initials : 'AF';
                                ?>
                                <div class="vehicle-thumb-wrapper">
                                    <?php if (!empty($vehicle['cover_image'])) : ?>
                                        <img class="vehicle-thumb" src="<?= htmlspecialchars($vehicle['cover_image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php else : ?>
                                        <span class="vehicle-thumb vehicle-thumb--placeholder"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <strong><?= htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars((string) $vehicle['year'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Patente"><?= htmlspecialchars($vehicle['license_plate'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Km"><?= number_format((int) $vehicle['mileage_km']); ?> km</td>
                            <td data-label="Carga (kg)"><?= number_format((int) ($vehicle['capacity_kg'] ?? 0)); ?> kg</td>
                            <td data-label="Pasajeros"><?= (int) ($vehicle['passenger_capacity'] ?? 0); ?></td>
                            <td data-label="Estado"><span class="tag tag-status"><?= htmlspecialchars($vehicle['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td data-label="Tarifa">$<?= number_format((float) $vehicle['daily_rate'], 2); ?></td>
                            <td data-label="Acciones" class="table-actions">
                                <a class="table-action-btn table-action-btn--view" href="index.php?route=vehicles/show&id=<?= (int) $vehicle['id']; ?>">Ver</a>
                                <a class="table-action-btn table-action-btn--edit" href="index.php?route=vehicles/edit&id=<?= (int) $vehicle['id']; ?>">Editar</a>
                                <form method="POST" action="index.php?route=vehicles/delete" onsubmit="return confirm('¿Eliminar este vehículo?');">
                                    <input type="hidden" name="id" value="<?= (int) $vehicle['id']; ?>">
                                    <button type="submit" class="table-action-btn table-action-btn--delete">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination['totalPages'] > 1) : ?>
            <?php
            $query = $_GET;
            $currentPage = (int) $pagination['page'];
            $totalPages = (int) $pagination['totalPages'];
            ?>
            <nav class="pagination">
                <?php if ($currentPage > 1) :
                    $query['page'] = $currentPage - 1;
                ?>
                    <a class="pagination-link" href="index.php?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8'); ?>">Anterior</a>
                <?php endif; ?>

                <span class="pagination-meta">Página <?= $currentPage; ?> de <?= $totalPages; ?></span>

                <?php if ($currentPage < $totalPages) :
                    $query['page'] = $currentPage + 1;
                ?>
                    <a class="pagination-link" href="index.php?<?= htmlspecialchars(http_build_query($query), ENT_QUOTES, 'UTF-8'); ?>">Siguiente</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>