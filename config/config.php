<?php
declare(strict_types=1);

/**
 * Local Environment Configuration (XAMPP)
 */
return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'SLink',
        'env' => getenv('APP_ENV') ?: 'production',
        'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
        'url' => getenv('APP_URL') ?: '', // Auto-detected if empty
        'short_domain' => getenv('SHORT_DOMAIN') ?: '', // Loaded from DB settings or auto-detected
        'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Jakarta',
        'default_code_len' => 6,
        'redirect_code' => 302,
        'session_name' => 'slink_session',
        'session_lifetime' => 7200,
        'rate_limit_max' => 5,
        'rate_limit_window' => 15,
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_DATABASE') ?: 'slink_db',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
        'charset' => 'utf8mb4',
    ],
];
