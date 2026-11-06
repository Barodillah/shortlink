<?php
declare(strict_types=1);

namespace App\Core;

class Security
{
    /**
     * Reserved routes and words that cannot be registered as shortlink aliases.
     */
    public const RESERVED_ALIASES = [
        'admin',
        'login',
        'logout',
        'dashboard',
        'api',
        'assets',
        'register',
        'settings',
        'profile',
        'install',
        'shortlinks',
        'analytics',
        'users',
        'logs',
        'public',
        'storage',
        'app',
        'config',
        'database',
        'index.php',
        'favicon.ico',
        'favicon.svg',
        'robots.txt',
    ];

    public static function initSession(array $appConfig): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $lifetime = (int) ($appConfig['session_lifetime'] ?? 7200);
        $sessionName = (string) ($appConfig['session_name'] ?? 'slink_session');

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        // Regenerate session ID periodically (every 30 mins) to mitigate fixation
        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        } elseif (time() - (int) $_SESSION['_created_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created_at'] = time();
        }
    }

    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals((string) $_SESSION['_csrf_token'], (string) $token);
    }

    public static function requireCsrf(): void
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::verifyCsrf(is_string($token) ? $token : null)) {
            http_response_code(419);
            if (self::isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Invalid or expired security token (CSRF). Please refresh the page.']);
                exit;
            }
            set_flash('error', 'Session token expired or invalid. Please submit the form again.');
            $referer = $_SERVER['HTTP_REFERER'] ?? url('/login');
            header('Location: ' . $referer);
            exit;
        }
    }

    public static function isAjax(): bool
    {
        return (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    public static function clientIp(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_REAL_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $parts = explode(',', (string) $candidate);
            $ip = trim($parts[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '127.0.0.1';
    }

    /**
     * Validate destination URL to prevent open redirects to dangerous schemes (javascript:, data:, file:, etc.)
     * and self-referential loops.
     */
    public static function validateDestinationUrl(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['valid' => false, 'error' => 'Destination URL is required.'];
        }

        if (strlen($url) > 2048) {
            return ['valid' => false, 'error' => 'Destination URL must not exceed 2048 characters.'];
        }

        // Block dangerous schemes explicitly
        if (preg_match('/^\s*(javascript|data|vbscript|file|ftp|mailto|tel):/i', $url)) {
            return ['valid' => false, 'error' => 'Invalid URL scheme. Only HTTP and HTTPS URLs are allowed.'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Please enter a valid URL including http:// or https://.'];
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['valid' => false, 'error' => 'Only http:// and https:// destination URLs are permitted.'];
        }

        if (empty($parsed['host']) || !str_contains($parsed['host'], '.') && $parsed['host'] !== 'localhost') {
            return ['valid' => false, 'error' => 'Destination URL must contain a valid domain host.'];
        }

        return ['valid' => true, 'url' => $url];
    }

    /**
     * Validate custom alias against reserved words and forbidden characters.
     */
    public static function validateAlias(string $alias): array
    {
        $alias = trim($alias);
        if ($alias === '') {
            return ['valid' => true, 'alias' => ''];
        }

        if (strlen($alias) < 2 || strlen($alias) > 64) {
            return ['valid' => false, 'error' => 'Custom alias must be between 2 and 64 characters long.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $alias)) {
            return ['valid' => false, 'error' => 'Custom alias may only contain letters, numbers, hyphens (-), and underscores (_).'];
        }

        if (in_array(strtolower($alias), self::RESERVED_ALIASES, true)) {
            return ['valid' => false, 'error' => sprintf('The alias "%s" is a reserved system keyword and cannot be used.', $alias)];
        }

        return ['valid' => true, 'alias' => $alias];
    }

    /**
     * Generate cryptographically secure random alphanumeric short code.
     */
    public static function generateShortCode(int $length = 6): string
    {
        $length = max(6, min(12, $length));
        $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($alphabet) - 1;
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
