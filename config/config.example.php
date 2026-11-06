<?php
declare(strict_types=1);

/**
 * SLink Platform Configuration Template
 * Copy this file to config/config.php and update with your environment credentials.
 */
return [
    'app' => [
        'name' => 'SLink',
        'env' => 'production', // 'local' or 'production'
        'debug' => false,
        'url' => 'https://s.domain.com',
        'short_domain' => 'https://s.domain.com',
        'timezone' => 'Asia/Jakarta',
        'default_code_len' => 6,
        'redirect_code' => 302,
        'session_name' => 'slink_session',
        'session_lifetime' => 7200, // seconds (2 hours)
        'rate_limit_max' => 5,    // max failed login attempts
        'rate_limit_window' => 15,   // lockout window in minutes
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'slink_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
