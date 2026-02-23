<?php
$pageTitle = 'Alquileres | AutoFlow';
$bodyClass = 'dashboard-page rentals-page';
$rentals = $rentals ?? [];
$flashMessage = $flashMessage ?? null;
$statusOptions = $statusOptions ?? [];
$vehicles = $vehicles ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];
$search = $filters['search'] ?? '';
$status = $filters['status'] ?? '';
$vehicleId = $filters['vehicle_id'] ?? '';
$dateFrom = $filters['date_from'] ?? '';
$dateTo = $filters['date_to'] ?? '';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="rentals">
    <section class="toolbar">
        <div>
            <h1>Alquileres</h1>
            <p>Seguimiento rápido de contratos activos y próximos ingresos.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn primary" href="index.php?route=rentals/create">+ Nuevo alquiler</a>
        </div>
    </section>

    <?php if (!empty($flashMessage)) : ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="index.php" class="filter-form">
        <input type="hidden" name="route" value="rentals">
        <div class="filter-group">
            <label for="search">Buscar</label>
            <input type="text" id="search" name="search" placeholder="Cliente, doc, patente..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="filter-group">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= $status === $option ? 'selected' : ''; ?>>
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $option)), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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

    <?php if (empty($rentals)) : ?>
        <p class="empty-state">Todavía no se cargaron alquileres. Creá el primero para comenzar a medir ocupación.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Vehículo</th>
                        <th>Periodo</th>
                        <th>Duración</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $rental) : ?>
                        <tr>
                            <td data-label="Cliente">
                                <strong><?= htmlspecialchars($rental['client_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($rental['client_document'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Vehículo">
                                <strong><?= htmlspecialchars($rental['brand'] . ' ' . $rental['model'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($rental['license_plate'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Periodo">
                                <?= htmlspecialchars($rental['start_date'], ENT_QUOTES, 'UTF-8'); ?>
                                &rarr;
                                <?= htmlspecialchars($rental['end_date'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <?php
                            $durationDays = 0;
                            try {
                                $start = new DateTime($rental['start_date']);
                                $end = new DateTime($rental['end_date']);
                                $durationDays = max(1, $start->diff($end)->days + 1);
                            } catch (Exception $exception) {
                                $durationDays = 0;
                            }
                            ?>
                            <td data-label="Duración"><?= $durationDays > 0 ? $durationDays . ' días' : '—'; ?></td>
                            <td data-label="Estado">
                                <span class="tag tag-status"><?= htmlspecialchars($rental['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td data-label="Total">$<?= number_format((float) $rental['total_amount'], 2); ?></td>
                            <td data-label="Acciones" class="table-actions">
                                <a class="table-action-btn table-action-btn--edit" href="index.php?route=rentals/edit&id=<?= (int) $rental['id']; ?>">Editar</a>
                                <form method="POST" action="index.php?route=rentals/delete" onsubmit="return confirm('¿Eliminar este alquiler?');">
                                    <input type="hidden" name="id" value="<?= (int) $rental['id']; ?>">
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