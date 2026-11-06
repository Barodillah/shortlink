<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Security;
use PDO;

class Shortlink
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findById(int $id, bool $includeDeleted = false): ?array
    {
        $sql = 'SELECT s.*, u.name AS owner_name, u.email AS owner_email
                FROM shortlinks s
                INNER JOIN users u ON u.id = s.user_id
                WHERE s.id = :id';
        if (!$includeDeleted) {
            $sql .= ' AND s.deleted_at IS NULL';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lookup shortlink by exact case-sensitive short_code (collation utf8mb4_bin).
     */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM shortlinks
             WHERE short_code = :code AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM shortlinks WHERE short_code = :code AND id != :id AND deleted_at IS NULL'
            );
            $stmt->execute([':code' => $code, ':id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM shortlinks WHERE short_code = :code AND deleted_at IS NULL'
            );
            $stmt->execute([':code' => $code]);
        }
        return (int) $stmt->fetchColumn() > 0;
    }

    public function generateUniqueCode(?int $length = null): string
    {
        $len = $length ?? (int) app_setting('default_code_length', 6);
        $len = max(6, min(8, $len));

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = Security::generateShortCode($len);
            if (!in_array(strtolower($candidate), Security::RESERVED_ALIASES, true) && !$this->rawCodeExists($candidate)) {
                return $candidate;
            }
        }

        // Fallback to 8 characters if dense
        return Security::generateShortCode(8);
    }

    private function rawCodeExists(string $code): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM shortlinks WHERE short_code = :code');
        $stmt->execute([':code' => $code]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Free up any soft-deleted record that holds the same short_code so a newly created custom alias
     * will not collide with a soft-deleted row on the UNIQUE constraint while keeping analytics intact.
     */
    private function releaseSoftDeletedCodeCollision(string $code): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, short_code FROM shortlinks WHERE short_code = :code AND deleted_at IS NOT NULL'
        );
        $stmt->execute([':code' => $code]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $archivedCode = substr($row['short_code'], 0, 45) . '_del_' . $row['id'] . '_' . substr(bin2hex(random_bytes(2)), 0, 4);
            $upd = $this->db->prepare('UPDATE shortlinks SET short_code = :new_code WHERE id = :id');
            $upd->execute([':new_code' => $archivedCode, ':id' => $row['id']]);
        }
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $code = (string) $data['short_code'];
            $this->releaseSoftDeletedCodeCollision($code);

            $stmt = $this->db->prepare(
                'INSERT INTO shortlinks
                    (user_id, short_code, destination_url, title, description, status, expires_at, max_clicks, total_clicks, human_clicks, bot_clicks, created_at, updated_at)
                 VALUES
                    (:user_id, :short_code, :destination_url, :title, :description, :status, :expires_at, :max_clicks, 0, 0, 0, NOW(), NOW())'
            );

            $stmt->execute([
                ':user_id'         => (int) $data['user_id'],
                ':short_code'      => $code,
                ':destination_url' => (string) $data['destination_url'],
                ':title'           => !empty($data['title']) ? trim((string) $data['title']) : null,
                ':description'     => !empty($data['description']) ? trim((string) $data['description']) : null,
                ':status'          => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active',
                ':expires_at'      => !empty($data['expires_at']) ? $data['expires_at'] : null,
                ':max_clicks'      => (!empty($data['max_clicks']) && (int) $data['max_clicks'] > 0) ? (int) $data['max_clicks'] : null,
            ]);

            $id = (int) $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            $code = (string) $data['short_code'];
            $this->releaseSoftDeletedCodeCollision($code);

            $stmt = $this->db->prepare(
                'UPDATE shortlinks
                 SET short_code      = :short_code,
                     destination_url = :destination_url,
                     title           = :title,
                     description     = :description,
                     status          = :status,
                     expires_at      = :expires_at,
                     max_clicks      = :max_clicks,
                     updated_at      = NOW()
                 WHERE id = :id AND deleted_at IS NULL'
            );

            $ok = $stmt->execute([
                ':short_code'      => $code,
                ':destination_url' => (string) $data['destination_url'],
                ':title'           => !empty($data['title']) ? trim((string) $data['title']) : null,
                ':description'     => !empty($data['description']) ? trim((string) $data['description']) : null,
                ':status'          => in_array($data['status'] ?? 'active', ['active', 'inactive'], true) ? $data['status'] : 'active',
                ':expires_at'      => !empty($data['expires_at']) ? $data['expires_at'] : null,
                ':max_clicks'      => (!empty($data['max_clicks']) && (int) $data['max_clicks'] > 0) ? (int) $data['max_clicks'] : null,
                ':id'              => $id,
            ]);

            $this->db->commit();
            return $ok;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function toggleStatus(int $id): ?string
    {
        $link = $this->findById($id);
        if (!$link) {
            return null;
        }
        $newStatus = $link['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $this->db->prepare('UPDATE shortlinks SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
        return $newStatus;
    }

    /**
     * Soft delete shortlink so click analytics history is preserved.
     */
    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE shortlinks
             SET status = "inactive",
                 deleted_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id AND deleted_at IS NULL'
        );
        return $stmt->execute([':id' => $id]);
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $where = ['s.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 's.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(s.short_code LIKE :q1 OR s.title LIKE :q2 OR s.destination_url LIKE :q3 OR u.name LIKE :q4 OR u.email LIKE :q5)';
            $term = '%' . trim((string) $filters['search']) . '%';
            $params[':q1'] = $term;
            $params[':q2'] = $term;
            $params[':q3'] = $term;
            $params[':q4'] = $term;
            $params[':q5'] = $term;
        }

        if (!empty($filters['status'])) {
            $st = $filters['status'];
            if ($st === 'active') {
                $where[] = 's.status = "active" AND (s.expires_at IS NULL OR s.expires_at > NOW()) AND (s.max_clicks IS NULL OR s.total_clicks < s.max_clicks)';
            } elseif ($st === 'inactive') {
                $where[] = 's.status = "inactive"';
            } elseif ($st === 'expired') {
                $where[] = '((s.expires_at IS NOT NULL AND s.expires_at <= NOW()) OR (s.max_clicks IS NOT NULL AND s.total_clicks >= s.max_clicks))';
            }
        }

        $whereSql = implode(' AND ', $where);

        $sortMap = [
            'newest'      => 's.created_at DESC, s.id DESC',
            'oldest'      => 's.created_at ASC, s.id ASC',
            'clicks_desc' => 's.total_clicks DESC, s.created_at DESC',
            'clicks_asc'  => 's.total_clicks ASC, s.created_at DESC',
        ];
        $sortKey = (string) ($filters['sort'] ?? 'newest');
        $orderBy = $sortMap[$sortKey] ?? $sortMap['newest'];

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM shortlinks s
             INNER JOIN users u ON u.id = s.user_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT s.*, u.name AS owner_name, u.email AS owner_email
                FROM shortlinks s
                INNER JOIN users u ON u.id = s.user_id
                WHERE {$whereSql}
                ORDER BY {$orderBy}
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

    public function topPerforming(?int $userId = null, int $limit = 5): array
    {
        $where = 's.deleted_at IS NULL';
        $params = [];
        if ($userId !== null) {
            $where .= ' AND s.user_id = :uid';
            $params[':uid'] = $userId;
        }

        $sql = "SELECT s.*, u.name AS owner_name, u.email AS owner_email
                FROM shortlinks s
                INNER JOIN users u ON u.id = s.user_id
                WHERE {$where}
                ORDER BY s.total_clicks DESC, s.human_clicks DESC, s.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function globalSummaryStats(): array
    {
        $usersCount = (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $activeUsersCount = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

        $stmt = $this->db->query(
            'SELECT
                COUNT(*) AS total_shortlinks,
                SUM(CASE WHEN status = "active" AND (expires_at IS NULL OR expires_at > NOW()) AND (max_clicks IS NULL OR total_clicks < max_clicks) THEN 1 ELSE 0 END) AS active_shortlinks,
                COALESCE(SUM(total_clicks), 0) AS total_clicks,
                COALESCE(SUM(human_clicks), 0) AS human_clicks,
                COALESCE(SUM(bot_clicks), 0) AS bot_clicks
             FROM shortlinks
             WHERE deleted_at IS NULL'
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_users'       => $usersCount,
            'active_users'      => $activeUsersCount,
            'total_shortlinks'  => (int) ($row['total_shortlinks'] ?? 0),
            'active_shortlinks' => (int) ($row['active_shortlinks'] ?? 0),
            'total_clicks'      => (int) ($row['total_clicks'] ?? 0),
            'human_clicks'      => (int) ($row['human_clicks'] ?? 0),
            'bot_clicks'        => (int) ($row['bot_clicks'] ?? 0),
        ];
    }

    public static function computeEffectiveState(array $shortlink): string
    {
        if (($shortlink['status'] ?? 'active') !== 'active') {
            return 'inactive';
        }
        if (!empty($shortlink['expires_at']) && strtotime((string) $shortlink['expires_at']) <= time()) {
            return 'expired';
        }
        if (!empty($shortlink['max_clicks']) && (int) $shortlink['total_clicks'] >= (int) $shortlink['max_clicks']) {
            return 'limit_reached';
        }
        return 'active';
    }
}

