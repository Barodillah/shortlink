<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        if ($excludeUserId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id != :id');
            $stmt->execute([':email' => strtolower(trim($email)), ':id' => $excludeUserId]);
        } else {
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $stmt->execute([':email' => strtolower(trim($email))]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, status, created_at, updated_at)
             VALUES (:name, :email, :password, :role, :status, NOW(), NOW())'
        );
        $stmt->execute([
            ':name'     => trim((string) $data['name']),
            ':email'    => strtolower(trim((string) $data['email'])),
            ':password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            ':role'     => in_array($data['role'] ?? 'user', ['superuser', 'user'], true) ? $data['role'] : 'user',
            ':status'   => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET name = :name,
                 email = :email,
                 role = :role,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([
            ':name'   => trim((string) $data['name']),
            ':email'  => strtolower(trim((string) $data['email'])),
            ':role'   => in_array($data['role'] ?? 'user', ['superuser', 'user'], true) ? $data['role'] : 'user',
            ':status' => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active',
            ':id'     => $id,
        ]);
    }

    public function updateProfile(int $id, string $name, string $email): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET name = :name, email = :email, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([
            ':name'  => trim($name),
            ':email' => strtolower(trim($email)),
            ':id'    => $id,
        ]);
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([
            ':password' => password_hash($newPassword, PASSWORD_BCRYPT),
            ':id'       => $id,
        ]);
    }

    public function toggleStatus(int $id): ?string
    {
        $user = $this->findById($id);
        if (!$user) {
            return null;
        }
        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $this->db->prepare('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
        return $newStatus;
    }

    /**
     * Safe user deletion inside a database transaction:
     * Reassigns user's shortlinks to a target superuser (and soft-deletes active ones if requested)
     * so analytics history (`clicks` and `shortlinks`) is never orphaned or lost.
     */
    public function safeDelete(int $userIdToDelete, int $fallbackOwnerId): bool
    {
        $this->db->beginTransaction();
        try {
            // Reassign shortlinks to fallback superuser & mark soft-deleted so links stop redirecting
            // while preserving all historical click analytics.
            $stmtLinks = $this->db->prepare(
                'UPDATE shortlinks
                 SET user_id = :fallback_id,
                     status = "inactive",
                     deleted_at = COALESCE(deleted_at, NOW()),
                     updated_at = NOW()
                 WHERE user_id = :uid'
            );
            $stmtLinks->execute([
                ':fallback_id' => $fallbackOwnerId,
                ':uid'         => $userIdToDelete,
            ]);

            $stmtDel = $this->db->prepare('DELETE FROM users WHERE id = :uid');
            $stmtDel->execute([':uid' => $userIdToDelete]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            log_error('Failed safeDelete for user ' . $userIdToDelete . ': ' . $e->getMessage());
            return false;
        }
    }

    public function countActiveSuperusers(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'superuser' AND status = 'active'");
        return (int) $stmt->fetchColumn();
    }

    public function paginateWithStats(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :q1 OR u.email LIKE :q2)';
            $term = '%' . trim((string) $filters['search']) . '%';
            $params[':q1'] = $term;
            $params[':q2'] = $term;
        }

        if (!empty($filters['role']) && in_array($filters['role'], ['superuser', 'user'], true)) {
            $where[] = 'u.role = :role';
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
            $where[] = 'u.status = :status';
            $params[':status'] = $filters['status'];
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT u.*,
                       COUNT(s.id) AS total_links,
                       COALESCE(SUM(s.total_clicks), 0) AS total_clicks,
                       COALESCE(SUM(s.human_clicks), 0) AS human_clicks
                FROM users u
                LEFT JOIN shortlinks s ON s.user_id = u.id AND s.deleted_at IS NULL
                WHERE {$whereSql}
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
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

    public function allSimple(): array
    {
        $stmt = $this->db->query('SELECT id, name, email, role, status FROM users ORDER BY name ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserSummaryStats(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(*) AS total_shortlinks,
                SUM(CASE WHEN status = "active" AND (expires_at IS NULL OR expires_at > NOW()) AND (max_clicks IS NULL OR total_clicks < max_clicks) THEN 1 ELSE 0 END) AS active_shortlinks,
                COALESCE(SUM(total_clicks), 0) AS total_clicks,
                COALESCE(SUM(human_clicks), 0) AS human_clicks,
                COALESCE(SUM(bot_clicks), 0) AS bot_clicks
             FROM shortlinks
             WHERE user_id = :uid AND deleted_at IS NULL'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_shortlinks'  => (int) ($row['total_shortlinks'] ?? 0),
            'active_shortlinks' => (int) ($row['active_shortlinks'] ?? 0),
            'total_clicks'      => (int) ($row['total_clicks'] ?? 0),
            'human_clicks'      => (int) ($row['human_clicks'] ?? 0),
            'bot_clicks'        => (int) ($row['bot_clicks'] ?? 0),
        ];
    }

    public function getTopUsers(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.name, u.email, u.role, u.status,
                    COUNT(s.id) AS total_links,
                    COALESCE(SUM(s.total_clicks), 0) AS total_clicks,
                    COALESCE(SUM(s.human_clicks), 0) AS human_clicks
             FROM users u
             LEFT JOIN shortlinks s ON s.user_id = u.id AND s.deleted_at IS NULL
             GROUP BY u.id
             ORDER BY total_clicks DESC, total_links DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

