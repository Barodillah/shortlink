<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Click
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Atomically verify limits, increment counters on shortlinks, and insert click analytics record.
     * Prevents race conditions on max_clicks during concurrent traffic.
     */
    public function recordClickAndIncrement(int $shortlinkId, array $data): bool
    {
        $isBot = !empty($data['is_bot']) ? 1 : 0;
        $humanDelta = $isBot === 0 ? 1 : 0;
        $botDelta = $isBot === 1 ? 1 : 0;

        $this->db->beginTransaction();
        try {
            $upd = $this->db->prepare(
                'UPDATE shortlinks
                 SET total_clicks = total_clicks + 1,
                     human_clicks = human_clicks + :h_delta,
                     bot_clicks   = bot_clicks + :b_delta
                 WHERE id = :id
                   AND deleted_at IS NULL
                   AND status = "active"
                   AND (expires_at IS NULL OR expires_at > NOW())
                   AND (max_clicks IS NULL OR total_clicks < max_clicks)'
            );
            $upd->execute([
                ':h_delta' => $humanDelta,
                ':b_delta' => $botDelta,
                ':id'      => $shortlinkId,
            ]);

            if ($upd->rowCount() === 0) {
                $this->db->rollBack();
                return false;
            }

            $ins = $this->db->prepare(
                'INSERT INTO clicks (
                    shortlink_id, ip_address, user_agent, browser, os, device_type,
                    country, city, referrer, referrer_domain,
                    utm_source, utm_medium, utm_campaign, utm_term, utm_content,
                    is_bot, clicked_at
                ) VALUES (
                    :shortlink_id, :ip_address, :user_agent, :browser, :os, :device_type,
                    :country, :city, :referrer, :referrer_domain,
                    :utm_source, :utm_medium, :utm_campaign, :utm_term, :utm_content,
                    :is_bot, NOW()
                )'
            );

            $ins->execute([
                ':shortlink_id'    => $shortlinkId,
                ':ip_address'      => substr((string) ($data['ip_address'] ?? '127.0.0.1'), 0, 45),
                ':user_agent'      => isset($data['user_agent']) ? substr((string) $data['user_agent'], 0, 512) : null,
                ':browser'         => substr((string) ($data['browser'] ?? 'Unknown'), 0, 80),
                ':os'              => substr((string) ($data['os'] ?? 'Unknown'), 0, 80),
                ':device_type'     => substr((string) ($data['device_type'] ?? 'Desktop'), 0, 40),
                ':country'         => substr((string) ($data['country'] ?? 'Unknown'), 0, 80),
                ':city'            => substr((string) ($data['city'] ?? 'Unknown'), 0, 100),
                ':referrer'        => !empty($data['referrer']) ? substr((string) $data['referrer'], 0, 512) : null,
                ':referrer_domain' => !empty($data['referrer_domain']) ? substr((string) $data['referrer_domain'], 0, 190) : 'Direct',
                ':utm_source'      => !empty($data['utm_source']) ? substr((string) $data['utm_source'], 0, 120) : null,
                ':utm_medium'      => !empty($data['utm_medium']) ? substr((string) $data['utm_medium'], 0, 120) : null,
                ':utm_campaign'    => !empty($data['utm_campaign']) ? substr((string) $data['utm_campaign'], 0, 120) : null,
                ':utm_term'        => !empty($data['utm_term']) ? substr((string) $data['utm_term'], 0, 120) : null,
                ':utm_content'     => !empty($data['utm_content']) ? substr((string) $data['utm_content'], 0, 120) : null,
                ':is_bot'          => $isBot,
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            log_error('Failed to record click analytics: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get daily click trends (human vs bot vs total) over the last N days.
     */
    public function clicksByDate(?int $shortlinkId = null, ?int $userId = null, int $days = 14): array
    {
        $days = max(1, min(90, $days));
        [$whereSql, $params] = $this->buildScopeFilter($shortlinkId, $userId, $days, 'all');

        $sql = "SELECT
                    DATE(c.clicked_at) AS click_date,
                    COUNT(*) AS total_clicks,
                    SUM(CASE WHEN c.is_bot = 0 THEN 1 ELSE 0 END) AS human_clicks,
                    SUM(CASE WHEN c.is_bot = 1 THEN 1 ELSE 0 END) AS bot_clicks
                FROM clicks c
                INNER JOIN shortlinks s ON s.id = c.shortlink_id
                WHERE {$whereSql}
                GROUP BY DATE(c.clicked_at)
                ORDER BY click_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[(string) $r['click_date']] = [
                'total' => (int) $r['total_clicks'],
                'human' => (int) $r['human_clicks'],
                'bot'   => (int) $r['bot_clicks'],
            ];
        }

        $labels = [];
        $dates = [];
        $humanSeries = [];
        $botSeries = [];
        $totalSeries = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $dates[] = $d;
            $labels[] = date('d M', strtotime($d));
            $humanSeries[] = $byDate[$d]['human'] ?? 0;
            $botSeries[]   = $byDate[$d]['bot'] ?? 0;
            $totalSeries[] = $byDate[$d]['total'] ?? 0;
        }

        return [
            'dates'  => $dates,
            'labels' => $labels,
            'human'  => $humanSeries,
            'bot'    => $botSeries,
            'total'  => $totalSeries,
        ];
    }

    /**
     * Breakdown clicks by a specific whitelisted column (device_type, browser, os, country, referrer_domain).
     */
    public function breakdownByColumn(
        string $column,
        ?int $shortlinkId = null,
        ?int $userId = null,
        int $days = 30,
        string $botFilter = 'human',
        int $limit = 8
    ): array {
        $allowedColumns = ['device_type', 'browser', 'os', 'country', 'city', 'referrer_domain'];
        if (!in_array($column, $allowedColumns, true)) {
            throw new \InvalidArgumentException('Invalid analytics breakdown column.');
        }

        [$whereSql, $params] = $this->buildScopeFilter($shortlinkId, $userId, $days, $botFilter);

        $sql = "SELECT
                    COALESCE(NULLIF(c.{$column}, ''), 'Direct / Unknown') AS label,
                    COUNT(*) AS count
                FROM clicks c
                INNER JOIN shortlinks s ON s.id = c.shortlink_id
                WHERE {$whereSql}
                GROUP BY label
                ORDER BY count DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aggregate UTM campaign performance.
     */
    public function utmPerformance(
        ?int $shortlinkId = null,
        ?int $userId = null,
        int $days = 30,
        string $botFilter = 'human',
        int $limit = 10
    ): array {
        [$whereSql, $params] = $this->buildScopeFilter($shortlinkId, $userId, $days, $botFilter);
        $whereSql .= ' AND (c.utm_source IS NOT NULL OR c.utm_medium IS NOT NULL OR c.utm_campaign IS NOT NULL)';

        $sql = "SELECT
                    COALESCE(c.utm_source, '-') AS utm_source,
                    COALESCE(c.utm_medium, '-') AS utm_medium,
                    COALESCE(c.utm_campaign, '-') AS utm_campaign,
                    COALESCE(c.utm_term, '-') AS utm_term,
                    COALESCE(c.utm_content, '-') AS utm_content,
                    COUNT(*) AS clicks
                FROM clicks c
                INNER JOIN shortlinks s ON s.id = c.shortlink_id
                WHERE {$whereSql}
                GROUP BY c.utm_source, c.utm_medium, c.utm_campaign, c.utm_term, c.utm_content
                ORDER BY clicks DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent individual clicks for audit/inspection.
     */
    public function recentClicks(
        ?int $shortlinkId = null,
        ?int $userId = null,
        int $limit = 20,
        string $botFilter = 'all'
    ): array {
        [$whereSql, $params] = $this->buildScopeFilter($shortlinkId, $userId, 90, $botFilter);

        $sql = "SELECT c.*, s.short_code, s.title AS shortlink_title
                FROM clicks c
                INNER JOIN shortlinks s ON s.id = c.shortlink_id
                WHERE {$whereSql}
                ORDER BY c.clicked_at DESC, c.id DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Summary counts within the selected date window.
     */
    public function periodSummary(?int $shortlinkId = null, ?int $userId = null, int $days = 30): array
    {
        [$whereSql, $params] = $this->buildScopeFilter($shortlinkId, $userId, $days, 'all');

        $sql = "SELECT
                    COUNT(*) AS total_clicks,
                    SUM(CASE WHEN c.is_bot = 0 THEN 1 ELSE 0 END) AS human_clicks,
                    SUM(CASE WHEN c.is_bot = 1 THEN 1 ELSE 0 END) AS bot_clicks,
                    COUNT(DISTINCT c.ip_address) AS unique_visitors
                FROM clicks c
                INNER JOIN shortlinks s ON s.id = c.shortlink_id
                WHERE {$whereSql}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_clicks'    => (int) ($row['total_clicks'] ?? 0),
            'human_clicks'    => (int) ($row['human_clicks'] ?? 0),
            'bot_clicks'      => (int) ($row['bot_clicks'] ?? 0),
            'unique_visitors' => (int) ($row['unique_visitors'] ?? 0),
        ];
    }

    private function buildScopeFilter(?int $shortlinkId, ?int $userId, int $days, string $botFilter): array
    {
        $where = ['s.deleted_at IS NULL'];
        $params = [];

        if ($shortlinkId !== null) {
            $where[] = 'c.shortlink_id = :shortlink_id';
            $params[':shortlink_id'] = $shortlinkId;
        }

        if ($userId !== null) {
            $where[] = 's.user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        if ($days > 0) {
            $where[] = 'c.clicked_at >= (NOW() - INTERVAL :days DAY)';
            $params[':days'] = $days;
        }

        if ($botFilter === 'human') {
            $where[] = 'c.is_bot = 0';
        } elseif ($botFilter === 'bot') {
            $where[] = 'c.is_bot = 1';
        }

        return [implode(' AND ', $where), $params];
    }
}

