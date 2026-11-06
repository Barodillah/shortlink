<?php
declare(strict_types=1);

use App\Core\Security;
use App\Models\Setting;

/**
 * HTML output escaping helper to prevent XSS.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Determine base URL path dynamically (supports both DocumentRoot=/public on s.domain.com
 * and subdirectory deployment like http://localhost/slink).
 */
function base_path(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    // Strip /public/index.php or /index.php
    $base = preg_replace('#(/public)?/index\.php$#', '', $scriptName);
    return rtrim((string) $base, '/');
}

/**
 * Full base URL of the current application.
 */
function base_url(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . base_path();
}

/**
 * Generate an internal application URL.
 */
function url(string $path = '/'): string
{
    $bp = base_path();
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return $bp !== '' ? $bp . '/' : '/';
    }
    return $bp . $path;
}

/**
 * Generate an asset URL.
 */
function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

/**
 * Generate full shortlink URL using configured shortlink domain or current base_url().
 */
function short_url(string $shortCode): string
{
    $configuredDomain = trim((string) app_setting('shortlink_domain', ''));
    if ($configuredDomain !== '') {
        return rtrim($configuredDomain, '/') . '/' . rawurlencode($shortCode);
    }
    return rtrim(base_url(), '/') . '/' . rawurlencode($shortCode);
}

/**
 * Get shortlink domain prefix for display in UI.
 */
function short_domain_display(): string
{
    $configuredDomain = trim((string) app_setting('shortlink_domain', ''));
    $domain = $configuredDomain !== '' ? $configuredDomain : base_url();
    $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));
    return $domain . '/';
}

/**
 * Read application setting with in-memory request caching.
 */
function app_setting(string $key, mixed $default = null): mixed
{
    static $settingsCache = null;
    if ($settingsCache === null) {
        try {
            $settingsCache = Setting::allKeyValue();
        } catch (\Throwable $e) {
            $settingsCache = [];
        }
    }
    return array_key_exists($key, $settingsCache) ? $settingsCache[$key] : $default;
}

/**
 * Reset cached settings after an update.
 */
function clear_settings_cache(): void
{
    // Handled via Setting::allKeyValue(true)
    Setting::allKeyValue(true);
}

/**
 * CSRF hidden input field helper.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(Security::csrfToken()) . '">';
}

function csrf_token(): string
{
    return Security::csrfToken();
}

/**
 * Flash messaging helpers.
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type] = $message;
}

function get_flash(string $type): ?string
{
    if (!isset($_SESSION['_flash'][$type])) {
        return null;
    }
    $msg = (string) $_SESSION['_flash'][$type];
    unset($_SESSION['_flash'][$type]);
    return $msg;
}

function has_flash(): bool
{
    return !empty($_SESSION['_flash']);
}

/**
 * Old form input value helper.
 */
function set_old_input(array $data): void
{
    $_SESSION['_old_input'] = $data;
}

function old(string $key, mixed $default = ''): string
{
    $val = $_SESSION['_old_input'][$key] ?? $default;
    return is_scalar($val) ? (string) $val : '';
}

function clear_old_input(): void
{
    unset($_SESSION['_old_input']);
}

/**
 * Format large numbers nicely.
 */
function format_number(int|float $number): string
{
    return number_format((float) $number, 0, '.', ',');
}

/**
 * Format human-readable relative time.
 */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return 'Never';
    }
    $timestamp = strtotime($datetime);
    if (!$timestamp) {
        return $datetime;
    }
    $diff = time() - $timestamp;
    if ($diff < 0) {
        $abs = abs($diff);
        if ($abs < 3600) {
            return 'in ' . max(1, (int) floor($abs / 60)) . 'm';
        }
        if ($abs < 86400) {
            return 'in ' . (int) floor($abs / 3600) . 'h';
        }
        return 'in ' . (int) floor($abs / 86400) . 'd';
    }
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return (int) floor($diff / 60) . 'm ago';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . 'h ago';
    }
    if ($diff < 2592000) {
        return (int) floor($diff / 86400) . 'd ago';
    }
    return date('d M Y', $timestamp);
}

/**
 * Log error message to /storage/logs/app.log without exposing internals to users.
 */
function log_error(string $message, array $context = []): void
{
    $logDir = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app.log';
    $ctx = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
    $line = sprintf("[%s] %s%s\n", date('Y-m-d H:i:s'), $message, $ctx);
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Parse User-Agent string into Browser, OS, Device Type, and Bot flag.
 * Separates human clicks from bot/crawler/preview requests as required by PRD Section 7.
 */
function parse_user_agent(?string $ua): array
{
    $ua = trim((string) $ua);

    if ($ua === '') {
        return [
            'browser'     => 'Unknown',
            'os'          => 'Unknown',
            'device_type' => 'Bot',
            'is_bot'      => 1,
        ];
    }

    // 1. Comprehensive Bot & Link Preview Detection
    $botPatterns = [
        'bot', 'crawl', 'spider', 'slurp', 'mediapartners', 'apis-google',
        'googlebot', 'bingbot', 'yandex', 'baiduspider', 'duckduckbot',
        'facebookexternalhit', 'facebot', 'twitterbot', 'linkedinbot',
        'whatsapp', 'telegrambot', 'slackbot', 'discordbot', 'pinterest',
        'skypeuripreview', 'applebot', 'petalbot', 'ahrefsbot', 'semrushbot',
        'mj12bot', 'dotbot', 'rogerbot', 'exabot', 'ia_archiver',
        'curl/', 'wget/', 'python-requests', 'python-urllib', 'aiohttp',
        'go-http-client', 'java/', 'okhttp', 'axios/', 'node-fetch',
        'postmanruntime', 'insomnia', 'headlesschrome', 'phantomjs',
        'playwright', 'puppeteer', 'selenium', 'uptimerobot', 'pingdom',
        'gtmetrix', 'lighthouse',
    ];

    $uaLower = strtolower($ua);
    foreach ($botPatterns as $pattern) {
        if (str_contains($uaLower, $pattern)) {
            return [
                'browser'     => 'Bot / Crawler',
                'os'          => 'Bot',
                'device_type' => 'Bot',
                'is_bot'      => 1,
            ];
        }
    }

    // 2. Operating System Detection
    $os = 'Other';
    if (preg_match('/iphone|ipod/i', $ua)) {
        $os = 'iOS';
    } elseif (preg_match('/ipad/i', $ua)) {
        $os = 'iOS';
    } elseif (preg_match('/android/i', $ua)) {
        $os = 'Android';
    } elseif (preg_match('/windows nt/i', $ua)) {
        $os = 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
        $os = 'macOS';
    } elseif (preg_match('/cros/i', $ua)) {
        $os = 'ChromeOS';
    } elseif (preg_match('/linux/i', $ua)) {
        $os = 'Linux';
    }

    // 3. Device Type Detection
    $deviceType = 'Desktop';
    if (preg_match('/ipad|tablet|kindle|playbook|silk|(android(?!.*mobile))/i', $ua)) {
        $deviceType = 'Tablet';
    } elseif (preg_match('/mobile|iphone|ipod|android.*mobile|iemobile|opera mini|blackberry/i', $ua)) {
        $deviceType = 'Mobile';
    }

    // 4. Browser Detection
    $browser = 'Other';
    if (preg_match('/edg\/|edga\/|edgios\//i', $ua)) {
        $browser = 'Edge';
    } elseif (preg_match('/opr\/|opera/i', $ua)) {
        $browser = 'Opera';
    } elseif (preg_match('/samsungbrowser/i', $ua)) {
        $browser = 'Samsung Internet';
    } elseif (preg_match('/ucbrowser/i', $ua)) {
        $browser = 'UC Browser';
    } elseif (preg_match('/firefox\/|fxios\//i', $ua)) {
        $browser = 'Firefox';
    } elseif (preg_match('/chrome\/|crios\//i', $ua)) {
        $browser = 'Chrome';
    } elseif (preg_match('/safari\//i', $ua) && !preg_match('/chrome|crios|edg|opr/i', $ua)) {
        $browser = 'Safari';
    } elseif (preg_match('/msie|trident/i', $ua)) {
        $browser = 'Internet Explorer';
    }

    return [
        'browser'     => $browser,
        'os'          => $os,
        'device_type' => $deviceType,
        'is_bot'      => 0,
    ];
}

/**
 * Resolve Country & City from CDN/Proxy GeoIP headers or Accept-Language heuristic without external blocking latency.
 */
function resolve_geo_location(string $ip): array
{
    // 1. Check standard reverse-proxy / Cloudflare / cloud geo headers first
    $countryCodeMap = [
        'ID' => ['Indonesia', 'Jakarta'],
        'SG' => ['Singapore', 'Singapore'],
        'MY' => ['Malaysia', 'Kuala Lumpur'],
        'US' => ['United States', 'New York'],
        'GB' => ['United Kingdom', 'London'],
        'AU' => ['Australia', 'Sydney'],
        'JP' => ['Japan', 'Tokyo'],
        'DE' => ['Germany', 'Frankfurt'],
        'NL' => ['Netherlands', 'Amsterdam'],
        'IN' => ['India', 'Mumbai'],
        'FR' => ['France', 'Paris'],
        'CA' => ['Canada', 'Toronto'],
    ];

    $cfCountry = strtoupper(trim((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? $_SERVER['HTTP_X_COUNTRY_CODE'] ?? '')));
    $cfCity = trim((string) ($_SERVER['HTTP_CF_IPCITY'] ?? $_SERVER['HTTP_X_CITY'] ?? ''));

    if ($cfCountry !== '' && isset($countryCodeMap[$cfCountry])) {
        return [
            'country' => $countryCodeMap[$cfCountry][0],
            'city'    => $cfCity !== '' ? $cfCity : $countryCodeMap[$cfCountry][1],
        ];
    }

    // 2. Fallback heuristic based on Accept-Language or local network
    $acceptLang = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    if (str_contains($acceptLang, 'id-id') || str_contains($acceptLang, 'id,')) {
        return ['country' => 'Indonesia', 'city' => 'Jakarta'];
    }
    if (str_contains($acceptLang, 'ms-my')) {
        return ['country' => 'Malaysia', 'city' => 'Kuala Lumpur'];
    }
    if (str_contains($acceptLang, 'en-sg')) {
        return ['country' => 'Singapore', 'city' => 'Singapore'];
    }
    if (str_contains($acceptLang, 'en-gb')) {
        return ['country' => 'United Kingdom', 'city' => 'London'];
    }
    if (str_contains($acceptLang, 'ja')) {
        return ['country' => 'Japan', 'city' => 'Tokyo'];
    }
    if (str_contains($acceptLang, 'en-us') || str_contains($acceptLang, 'en')) {
        return ['country' => 'Indonesia', 'city' => 'Jakarta'];
    }

    if ($ip === '127.0.0.1' || $ip === '::1') {
        return ['country' => 'Indonesia', 'city' => 'Jakarta'];
    }

    return ['country' => 'Unknown', 'city' => 'Unknown'];
}

