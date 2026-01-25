<?php
$pageTitle = 'Mantenimientos | AutoFlow';
$bodyClass = 'dashboard-page maintenance-page';
$records = $records ?? [];
$flashMessage = $flashMessage ?? null;

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
    <?php endif; ?>
</main>

<?php include_once __DIR__ . '/../Include/Footer.php'; ?>