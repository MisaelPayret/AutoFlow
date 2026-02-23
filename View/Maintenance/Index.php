<?php
$pageTitle = 'Mantenimientos | AutoFlow';
$bodyClass = 'dashboard-page maintenance-page';
$records = $records ?? [];
$flashMessage = $flashMessage ?? null;
$vehicles = $vehicles ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];
$search = $filters['search'] ?? '';
$vehicleId = $filters['vehicle_id'] ?? '';
$dateFrom = $filters['date_from'] ?? '';
$dateTo = $filters['date_to'] ?? '';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="maintenance">
    <section class="toolbar">
        <div>
            <h1>Mantenimientos</h1>
            <p>Controlá los servicios realizados y planificá los próximos turnos.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn primary" href="index.php?route=maintenance/create">+ Registrar servicio</a>
        </div>
    </section>

    <?php if (!empty($flashMessage)) : ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="index.php" class="filter-form">
        <input type="hidden" name="route" value="maintenance">
        <div class="filter-group">
            <label for="search">Buscar</label>
            <input type="text" id="search" name="search" placeholder="Servicio, patente, marca..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="filter-group">
            <label for="vehicle_id">Vehículo</label>
            <select id="vehicle_id" name="vehicle_id">
                <option value="">Todos</option>
                <?php foreach ($vehicles as $vehicle) :
                    $label = $vehicle['brand'] . ' ' . $vehicle['model'] . ' · ' . $vehicle['license_plate'];
                ?>
                    <option value="<?= (int) $vehicle['id']; ?>" <?= (string) $vehicleId === (string) $vehicle['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="date_from">Desde</label>
            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="filter-group">
            <label for="date_to">Hasta</label>
            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <button type="submit" class="btn ghost">Filtrar</button>
    </form>

    <?php if (empty($records)) : ?>
        <p class="empty-state">Todavía no registraste mantenimientos. Usá el botón "Registrar servicio" para empezar.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Servicio</th>
                        <th>Fecha</th>
                        <th>Km</th>
                        <th>Costo</th>
                        <th>Próximo servicio</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record) : ?>
                        <tr>
                            <td data-label="Vehículo">
                                <strong><?= htmlspecialchars($record['brand'] . ' ' . $record['model'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($record['license_plate'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Servicio"><?= htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Fecha"><?= htmlspecialchars($record['service_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Km"><?= $record['mileage_km'] !== null ? number_format((int) $record['mileage_km']) . ' km' : '—'; ?></td>
                            <td data-label="Costo">$<?= number_format((float) $record['cost'], 2); ?></td>
                            <td data-label="Próximo servicio">
                                <?= !empty($record['next_service_date']) ? htmlspecialchars($record['next_service_date'], ENT_QUOTES, 'UTF-8') : 'Sin definir'; ?>
                            </td>
                            <td data-label="Acciones" class="table-actions">
                                <a class="table-action-btn table-action-btn--edit" href="index.php?route=maintenance/edit&id=<?= (int) $record['id']; ?>">Editar</a>
                                <form method="POST" action="index.php?route=maintenance/delete" onsubmit="return confirm('¿Eliminar este registro?');">
                                    <input type="hidden" name="id" value="<?= (int) $record['id']; ?>">
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