<?php
$pageTitle = $pageTitle ?? 'Historial de alquileres | AutoFlow';
$bodyClass = $bodyClass ?? 'dashboard-page rentals-page rentals-history-page';
$history = $history ?? [];
$summary = $summary ?? ['totalRentals' => 0, 'totalAmount' => 0, 'lastEndDate' => null];
$clientIdentifier = $clientIdentifier ?? '';
$pagination = $pagination ?? ['page' => 1, 'perPage' => 50, 'total' => 0, 'totalPages' => 1];

include_once __DIR__ . '/../Include/Header.php';
?>

<main class="rentals">
    <section class="toolbar">
        <div>
            <h1>Historial de alquileres</h1>
            <p><?= htmlspecialchars($clientIdentifier, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="toolbar-actions">
            <a class="btn ghost" href="index.php?route=rentals">Volver</a>
        </div>
    </section>

    <section class="status-summary">
        <article>
            <small>Total de alquileres</small>
            <strong><?= (int) ($summary['totalRentals'] ?? 0); ?></strong>
        </article>
        <article>
            <small>Ingresos acumulados</small>
            <strong>$<?= number_format((float) ($summary['totalAmount'] ?? 0), 2); ?></strong>
        </article>
        <article>
            <small>Último cierre</small>
            <strong><?= htmlspecialchars($summary['lastEndDate'] ?? 'N/D', ENT_QUOTES, 'UTF-8'); ?></strong>
        </article>
    </section>

    <?php if (empty($history)) : ?>
        <p class="empty-state">No hay alquileres registrados para este cliente.</p>
    <?php else : ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Periodo</th>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $rental) : ?>
                        <tr>
                            <td data-label="Vehículo">
                                <strong><?= htmlspecialchars($rental['brand'] . ' ' . $rental['model'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?= htmlspecialchars($rental['license_plate'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </td>
                            <td data-label="Periodo">
                                <?= htmlspecialchars($rental['start_date'], ENT_QUOTES, 'UTF-8'); ?>
                                &rarr;
                                <?= htmlspecialchars($rental['end_date'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td data-label="Estado">
                                <?php
                                $statusKey = preg_replace('/[^a-z_]/', '', strtolower((string) ($rental['status'] ?? 'pending')));
                                $statusKey = $statusKey !== '' ? $statusKey : 'pending';
                                ?>
                                <span class="tag tag-status tag-status--<?= htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string) $rental['status'])), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td data-label="Total">$<?= number_format((float) $rental['total_amount'], 2); ?></td>
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