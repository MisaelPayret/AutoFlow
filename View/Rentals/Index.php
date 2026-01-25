<?php
$pageTitle = 'Alquileres | AutoFlow';
$bodyClass = 'dashboard-page rentals-page';
$rentals = $rentals ?? [];
$flashMessage = $flashMessage ?? null;
$statusOptions = $statusOptions ?? [];

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
    <?php endif; ?>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>