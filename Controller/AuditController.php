<?php

require_once dirname(__DIR__) . '/Database/Database.php';
require_once dirname(__DIR__) . '/Core/View.php';
require_once dirname(__DIR__) . '/Model/AuditLogModel.php';

/**
 * Consulta de auditoria con filtros basicos.
 */
class AuditController
{
    private Database $database;
    private AuditLogModel $auditLogs;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->database = new Database();
        $this->auditLogs = new AuditLogModel($this->database->getConnection());
    }

    /**
     * Muestra el listado de auditoria con filtros.
     */
    public function index(): void
    {
        $this->ensureAuthenticated();

        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'action' => trim((string) ($_GET['action'] ?? '')),
            'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
            'entity_id' => trim((string) ($_GET['entity_id'] ?? '')),
            'user_id' => trim((string) ($_GET['user_id'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        ];

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $total = $this->auditLogs->count($filters);
        $totalPages = (int) max(1, ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $logs = $this->auditLogs->search($filters, $perPage, $offset);
        $users = $this->auditLogs->listUsers();

        View::render('Audit/Index.php', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => ['create', 'update', 'delete'],
            'entityTypes' => ['vehicle', 'rental', 'maintenance'],
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'pageTitle' => 'Auditoria | AutoFlow',
            'bodyClass' => 'dashboard-page audit-page',
        ]);
    }

    private function ensureAuthenticated(): void
    {
        if (empty($_SESSION['auth_user_id'])) {
            $this->redirectToRoute('auth/login');
        }
    }

    private function redirectToRoute(string $route): void
    {
        $location = 'index.php?route=' . rawurlencode($route);
        header('Location: ' . $location);
        exit;
    }
}
