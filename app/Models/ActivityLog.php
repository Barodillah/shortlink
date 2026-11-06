<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class ActivityLog
{
    public static function record(?int $userId, string $action, string $description, string $ipAddress): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                 VALUES (:user_id, :action, :description, :ip_address, NOW())'
            );
            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => substr($action, 0, 100),
                ':description' => $description,
                ':ip_address'  => substr($ipAddress, 0, 45),
            ]);
        } catch (\Throwable $e) {
            log_error('Failed to record activity log: ' . $e->getMessage());
        }
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $db = Database::connection();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(al.action LIKE :q1 OR al.description LIKE :q2 OR al.ip_address LIKE :q3 OR u.name LIKE :q4 OR u.email LIKE :q5)';
            $term = '%' . trim((string) $filters['search']) . '%';
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
            $params[':q4'] = $term;
            $params[':q5'] = $term;
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = 'al.action LIKE :action_prefix';
            $params[':action_prefix'] = trim((string) $filters['action']) . '%';
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT al.*, u.name AS user_name, u.email AS user_email, u.role AS user_role
                FROM activity_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE {$whereSql}
                ORDER BY al.created_at DESC, al.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items'       => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function recentByUser(int $userId, int $limit = 15): array
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT * FROM activity_logs
             WHERE user_id = :uid
             ORDER BY created_at DESC, id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

