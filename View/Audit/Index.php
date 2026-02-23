<?php
$pageTitle = $pageTitle ?? 'Auditoria | AutoFlow';
$bodyClass = $bodyClass ?? 'dashboard-page audit-page';
$logs = $logs ?? [];
$filters = $filters ?? [];
$actions = $actions ?? [];
$entityTypes = $entityTypes ?? [];
$users = $users ?? [];
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];
$search = $filters['search'] ?? '';
$action = $filters['action'] ?? '';
$entityType = $filters['entity_type'] ?? '';
$entityId = $filters['entity_id'] ?? '';
$userId = $filters['user_id'] ?? '';
$dateFrom = $filters['date_from'] ?? '';
$dateTo = $filters['date_to'] ?? '';

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="audit">
    <section class="toolbar">
        <div>
            <h1>Auditoria</h1>
            <p>Revision de cambios realizados en el sistema.</p>
        </div>
    </section>

    <form method="GET" action="index.php" class="filter-form">
        <input type="hidden" name="route" value="audit">
        <div class="filter-group">
            <label for="search">Buscar</label>
            <input type="text" id="search" name="search" placeholder="Accion, entidad, usuario..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="filter-group">
            <label for="action">Accion</label>
            <select id="action" name="action">
                <option value="">Todas</option>
                <?php foreach ($actions as $option) : ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $action === $option ? 'selected' : ''; ?>>
                        <?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="entity_type">Entidad</label>
            <select id="entity_type" name="entity_type">
                <option value="">Todas</option>
                <?php foreach ($entityTypes as $option) : ?>
                    <option value="<?= htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?= $entityType === $option ? 'selected' : ''; ?>>
                        <?= htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="entity_id">ID entidad</label>
            <input type="number" id="entity_id" name="entity_id" min="1" value="<?= htmlspecialchars((string) $entityId, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="filter-group">
            <label for="user_id">Usuario</label>
            <select id="user_id" name="user_id">
                <option value="">Todos</option>
                <?php foreach ($users as $user) :
                    $label = trim((string) ($user['name'] ?? ''));
                    $email = trim((string) ($user['email'] ?? ''));
                    $display = $label !== '' ? $label : $email;
                ?>
                    <option value="<?= (int) $user['id']; ?>" <?= (string) $userId === (string) $user['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($display !== '' ? $display : 'Usuario #' . $user['id'], ENT_QUOTES, 'UTF-8'); ?>
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

    <?php if (empty($logs)) : ?>
        <p class="empty-state">No hay registros de auditoria para los filtros actuales.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Accion</th>
                        <th>Entidad</th>
                        <th>Resumen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <?php
                        $userLabel = $log['user_name'] ?? $log['user_email'] ?? 'Sistema';
                        $entityLabel = ($log['entity_type'] ?? '') . ($log['entity_id'] ? ' #' . $log['entity_id'] : '');
                        ?>
                        <tr>
                            <td data-label="Fecha"><?= htmlspecialchars(substr((string) $log['created_at'], 0, 16), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Usuario"><?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Accion"><?= htmlspecialchars($log['action'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Entidad"><?= htmlspecialchars($entityLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td data-label="Resumen">
                                <details>
                                    <summary><?= htmlspecialchars($log['summary'] ?? 'Ver detalle', ENT_QUOTES, 'UTF-8'); ?></summary>
                                    <?php if (!empty($log['before_data'])) : ?>
                                        <div class="audit-detail">
                                            <strong>Antes</strong>
                                            <pre><?= htmlspecialchars($log['before_data'], ENT_QUOTES, 'UTF-8'); ?></pre>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($log['after_data'])) : ?>
                                        <div class="audit-detail">
                                            <strong>Despues</strong>
                                            <pre><?= htmlspecialchars($log['after_data'], ENT_QUOTES, 'UTF-8'); ?></pre>
                                        </div>
                                    <?php endif; ?>
                                </details>
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