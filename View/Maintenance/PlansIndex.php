<?php
$pageTitle = $pageTitle ?? 'Planes de mantenimiento | AutoFlow';
$bodyClass = $bodyClass ?? 'dashboard-page maintenance-page maintenance-plans-page';
$plans = $plans ?? [];
$vehicles = $vehicles ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];
$flashMessage = $flashMessage ?? null;
$vehicleId = $filters['vehicle_id'] ?? '';
$isActive = $filters['is_active'] ?? '';
$hasFilters = $vehicleId !== '' || $isActive !== '';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="maintenance">
    <section class="toolbar">
        <div>
            <h1>Planes de mantenimiento</h1>
            <p>Definí intervalos por vehículo para anticipar próximos servicios.</p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=maintenance">Volver</a>
            <a class="btn primary" href="index.php?route=maintenance/plans/create">+ Nuevo plan</a>
        </div>
    </section>

    <?php if (!empty($flashMessage)) : ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <form method="GET" action="index.php" class="filter-form">
        <input type="hidden" name="route" value="maintenance/plans">
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
            <label for="is_active">Estado</label>
            <select id="is_active" name="is_active">
                <option value="">Todos</option>
                <option value="1" <?= $isActive === '1' ? 'selected' : ''; ?>>Activos</option>
                <option value="0" <?= $isActive === '0' ? 'selected' : ''; ?>>Inactivos</option>
            </select>
        </div>
        <button type="submit" class="btn ghost">Filtrar</button>
        <?php if ($hasFilters) : ?>
            <a class="btn ghost" href="index.php?route=maintenance/plans">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($plans)) : ?>
        <p class="empty-state">Todavía no cargaste planes. Creá el primero para automatizar los vencimientos.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Servicio</th>
                        <th>Intervalo</th>
                        <th>Próximo</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan) : ?>
                        <tr>
                            <td data-label="Vehículo">
                                <strong><?= htmlspecialchars(($plan['brand'] ?? '') . ' ' . ($plan['model'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($plan['license_plate'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Servicio"><?= htmlspecialchars($plan['service_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Intervalo">
                                <?php
                                $intervalParts = [];
                                if (!empty($plan['interval_km'])) {
                                    $intervalParts[] = number_format((int) $plan['interval_km']) . ' km';
                                }
                                if (!empty($plan['interval_months'])) {
                                    $intervalParts[] = (int) $plan['interval_months'] . ' meses';
                                }
                                echo htmlspecialchars($intervalParts ? implode(' · ', $intervalParts) : '—', ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td data-label="Próximo">
                                <?php
                                $nextParts = [];
                                if (!empty($plan['next_service_date'])) {
                                    $nextParts[] = $plan['next_service_date'];
                                }
                                if (!empty($plan['next_service_km'])) {
                                    $nextParts[] = number_format((int) $plan['next_service_km']) . ' km';
                                }
                                echo htmlspecialchars($nextParts ? implode(' · ', $nextParts) : '—', ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td data-label="Estado">
                                <span class="tag tag-status tag-status--<?= !empty($plan['is_active']) ? 'completed' : 'pending'; ?>">
                                    <?= !empty($plan['is_active']) ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td data-label="Acciones" class="table-actions">
                                <a class="table-action-btn table-action-btn--edit" href="index.php?route=maintenance/plans/edit&id=<?= (int) $plan['id']; ?>">Editar</a>
                                <form method="POST" action="index.php?route=maintenance/plans/delete" onsubmit="return confirm('¿Eliminar este plan?');">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id" value="<?= (int) $plan['id']; ?>">
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