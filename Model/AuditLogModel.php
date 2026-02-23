<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Registro de auditoria de operaciones criticas.
 */
class AuditLogModel extends BaseModel
{
    /**
     * Crea un registro de auditoria.
     */
    public function create(array $payload): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO `audit_logs` (user_id, action, entity_type, entity_id, summary, before_data, after_data)
             VALUES (:user_id, :action, :entity_type, :entity_id, :summary, :before_data, :after_data)'
        );

        $statement->execute([
            'user_id' => $payload['user_id'] ?? null,
            'action' => $payload['action'] ?? 'unknown',
            'entity_type' => $payload['entity_type'] ?? 'unknown',
            'entity_id' => $payload['entity_id'] ?? null,
            'summary' => $payload['summary'] ?? null,
            'before_data' => $payload['before_data'] ?? null,
            'after_data' => $payload['after_data'] ?? null,
        ]);
    }

    /**
     * Busca auditorias con filtros basicos.
     */
    public function search(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        $sql =
            'SELECT a.*, u.name AS user_name, u.email AS user_email
             FROM `audit_logs` AS a
             LEFT JOIN `users` AS u ON u.id = a.user_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);
        $sql .= ' ORDER BY a.`created_at` DESC LIMIT :limit OFFSET :offset';

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Devuelve el total para paginacion.
     */
    public function count(array $filters = []): int
    {
        $sql =
            'SELECT COUNT(*)
             FROM `audit_logs` AS a
             LEFT JOIN `users` AS u ON u.id = a.user_id
             WHERE 1=1';
        $params = [];
        $sql .= $this->buildFilterSql($filters, $params);

        $statement = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Lista usuarios para filtros.
     */
    public function listUsers(): array
    {
        $statement = $this->pdo->query('SELECT id, name, email FROM `users` ORDER BY name ASC, email ASC');
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Construye condiciones WHERE segun filtros.
     */
    private function buildFilterSql(array $filters, array &$params): string
    {
        $sql = '';

        if (!empty($filters['search'])) {
            $sql .= ' AND (
                a.`summary` LIKE :search
                OR a.`entity_type` LIKE :search
                OR a.`action` LIKE :search
                OR u.`name` LIKE :search
                OR u.`email` LIKE :search
            )';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND a.`action` = :action';
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND a.`entity_type` = :entity_type';
            $params['entity_type'] = $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $sql .= ' AND a.`entity_id` = :entity_id';
            $params['entity_id'] = (int) $filters['entity_id'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= ' AND a.`user_id` = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND a.`created_at` >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND a.`created_at` <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        return $sql;
    }
}
