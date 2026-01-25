<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/MaintenanceModel.php';
require_once dirname(__DIR__) . '/Model/RentalModel.php';
require_once dirname(__DIR__) . '/Model/VehicleModel.php';

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

    public function index(): void
    {
        if (empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('auth/login');
        }

        View::render('Public/Home.php', [
            'stats' => $this->getDashboardStats(),
            'recentRentals' => $this->rentals->recent(4),
            'upcomingMaintenance' => $this->maintenance->upcoming(4),
        ]);
    }

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

    private function redirectToRoute(string $route): void
    {
        $location = 'index.php?route=' . rawurlencode($route);
        header('Location: ' . $location);
        exit;
    }
}
