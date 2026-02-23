<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/AuthGuard.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/MaintenanceModel.php';
require_once dirname(__DIR__) . '/Model/RentalModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

/**
 * Panel principal que agrupa estadísticas rápidas para usuarios autenticados.
 *
 * Orquesta la lectura de vehículos, alquileres y mantenimientos para producir
 * una vista compacta de salud de la flota.
 */
class HomeController
{
    private Database $database;
    private VehicleModel $vehicles;
    private RentalModel $rentals;
    private MaintenanceModel $maintenance;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $connection = $this->database->getConnection();
        $this->vehicles = new VehicleModel($connection);
        $this->rentals = new RentalModel($connection);
        $this->maintenance = new MaintenanceModel($connection);
    }

    /**
     * Renderiza la página de inicio segura con métricas y listados resumidos.
     */
    public function index(): void
    {
        AuthGuard::requireRoles(['admin']);

        View::render('Public/Home.php', [
            'stats' => $this->getDashboardStats(),
            'recentRentals' => $this->rentals->recent(4),
            'upcomingMaintenance' => $this->maintenance->upcoming(4),
        ]);
    }

    /**
     * Calcula todas las métricas mostradas en el hero del dashboard.
     *
     * Combina agregados de los diferentes modelos para evitar que la vista
     * tenga que conocer detalles de la base de datos.
     */
    private function getDashboardStats(): array
    {
        $statusCounts = $this->vehicles->countByStatus();
        $totalVehicles = array_sum($statusCounts);

        $stats = [
            'totalVehicles' => $totalVehicles,
            'availableVehicles' => $statusCounts['available'] ?? 0,
            'maintenanceVehicles' => $statusCounts['maintenance'] ?? 0,
            'activeRentals' => $this->rentals->countByStatuses(['confirmed', 'in_progress']),
            'overdueMaintenance' => $this->maintenance->countOverdue(),
            'draftRentals' => $this->rentals->countByStatuses(['draft']),
        ];

        $stats['utilization'] = $stats['totalVehicles'] > 0
            ? (int) round(($stats['activeRentals'] / $stats['totalVehicles']) * 100)
            : 0;

        $stats['rentalRevenue30d'] = $this->rentals->sumRevenueLastDays(30);
        $stats['maintenanceSpend30d'] = $this->maintenance->sumCostLastDays(30);
        $stats['avgRentalDuration'] = (int) round($this->rentals->averageDurationDays());

        return $stats;
    }

    /**
     * Redirige a cualquier ruta definida en el router público.
     */
    private function redirectToRoute(string $route): void
    {
        $location = 'index.php?route=' . rawurlencode($route);
        header('Location: ' . $location);
        exit;
    }
}
