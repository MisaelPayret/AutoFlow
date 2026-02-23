<?php
$pageTitle = $pageTitle ?? 'Obligaciones | AutoFlow';
$bodyClass = $bodyClass ?? 'dashboard-page obligations-page';
$records = $records ?? [];
$summary = $summary ?? ['totalRecords' => 0, 'totalAmount' => 0, 'pendingCount' => 0, 'overdueCount' => 0, 'paidCount' => 0];
$flashMessage = $flashMessage ?? null;
$vehicles = $vehicles ?? [];
$typeOptions = $typeOptions ?? [];
$statusOptions = $statusOptions ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];
$search = $filters['search'] ?? '';
$vehicleId = $filters['vehicle_id'] ?? '';
$obligationType = $filters['obligation_type'] ?? '';
$status = $filters['status'] ?? '';
$dateFrom = $filters['date_from'] ?? '';
$dateTo = $filters['date_to'] ?? '';
$hasFilters = $search !== '' || $vehicleId !== '' || $obligationType !== '' || $status !== '' || $dateFrom !== '' || $dateTo !== '';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="obligations">
    <section class="toolbar">
        <div>
            <h1>Obligaciones</h1>
            <p>Gestioná vencimientos de patente, seguro, impuestos y otros cargos.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn primary" href="index.php?route=obligations/create">+ Nueva obligación</a>
        </div>
    </section>

    <?php if (!empty($flashMessage)) : ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="status-summary">
        <article>
            <small>Total</small>
            <strong><?= (int) ($summary['totalRecords'] ?? 0); ?></strong>
        </article>
        <article>
            <small>Vigentes</small>
            <strong><?= (int) ($summary['pendingCount'] ?? 0); ?></strong>
        </article>
        <article>
            <small>Vencidas</small>
            <strong><?= (int) ($summary['overdueCount'] ?? 0); ?></strong>
        </article>
        <article>
            <small>Pagadas</small>
            <strong><?= (int) ($summary['paidCount'] ?? 0); ?></strong>
        </article>
        <article>
            <small>Total acumulado</small>
            <strong>$<?= number_format((float) ($summary['totalAmount'] ?? 0), 2); ?></strong>
        </article>
    </section>

    <form method="GET" action="index.php" class="filter-form">
        <input type="hidden" name="route" value="obligations">
        <div class="filter-group">
            <label for="search">Buscar</label>
            <input type="text" id="search" name="search" placeholder="Marca, modelo, patente..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
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
            <label for="obligation_type">Tipo</label>
            <select id="obligation_type" name="obligation_type">
                <option value="">Todos</option>
                <?php foreach ($typeOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= $obligationType === $option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <?php foreach ($statusOptions as $option) : ?>
                    <option value="<?= $option; ?>" <?= $status === $option ? 'selected' : ''; ?>><?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?></option>
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
        <?php if ($hasFilters) : ?>
            <a class="btn ghost" href="index.php?route=obligations">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($records)) : ?>
        <p class="empty-state">Todavía no registraste obligaciones. Usá "Nueva obligación" para comenzar.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Vencimiento</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $record) : ?>
                        <tr>
                            <td data-label="Vehículo">
                                <strong><?= htmlspecialchars(($record['brand'] ?? '') . ' ' . ($record['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($record['license_plate'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Tipo"><?= htmlspecialchars(ucfirst($record['obligation_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Vencimiento"><?= htmlspecialchars($record['due_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Monto">$<?= number_format((float) ($record['amount'] ?? 0), 2); ?></td>
                            <td data-label="Estado">
                                <?php $statusKey = $record['status'] ?? 'pending'; ?>
                                <span class="tag tag-status tag-status--<?= htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars(ucfirst($statusKey), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td data-label="Pago">
                                <?= !empty($record['paid_at']) ? htmlspecialchars($record['paid_at'], ENT_QUOTES, 'UTF-8') : '—'; ?>
                            </td>
                            <td data-label="Acciones" class="table-actions">
                                <a class="table-action-btn table-action-btn--edit" href="index.php?route=obligations/edit&id=<?= (int) $record['id']; ?>">Editar</a>
                                <form method="POST" action="index.php?route=obligations/delete" onsubmit="return confirm('¿Eliminar esta obligación?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
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