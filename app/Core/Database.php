<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . '/Config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'] ?? 'mysql',
                $config['host'] ?? '127.0.0.1',
                (int) ($config['port'] ?? 3306),
                $config['database'] ?? 'slink_db',
                $config['charset'] ?? 'utf8mb4'
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO(
                    $dsn,
                    (string) ($config['username'] ?? 'root'),
                    (string) ($config['password'] ?? ''),
                    $options
                );
            } catch (PDOException $e) {
                log_error('Database Connection Error: ' . $e->getMessage());
                throw new RuntimeException('Database connection failed. Please verify database configuration.');
            }
        }

        return self::$instance;
    }
}

