<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting
{
    private static ?array $cache = null;

    public static function allKeyValue(bool $forceRefresh = false): array
    {
        if (self::$cache !== null && !$forceRefresh) {
            return self::$cache;
        }

        $db = Database::connection();
        $stmt = $db->query('SELECT setting_key, setting_value FROM settings');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['setting_key']] = $row['setting_value'];
        }

        self::$cache = $result;
        return self::$cache;
    }

    public static function set(string $key, ?string $value): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (:key, :val, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );
        $stmt->execute([
            ':key' => $key,
            ':val' => $value,
        ]);
        self::$cache = null;
    }

    public static function updateBatch(array $settings): void
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO settings (setting_key, setting_value, updated_at)
                 VALUES (:key, :val, NOW())
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
            );
            foreach ($settings as $key => $value) {
                $stmt->execute([
                    ':key' => (string) $key,
                    ':val' => $value !== null ? (string) $value : '',
                ]);
            }
            $db->commit();
            self::$cache = null;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

