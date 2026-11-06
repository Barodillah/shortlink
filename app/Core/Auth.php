<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\ActivityLog;
use App\Models\User;
use PDO;

class Auth
{
    private static ?array $currentUser = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$currentUser;
        }

        self::$loaded = true;
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $userModel = new User();
        $user = $userModel->findById((int) $userId);

        // Verify user still exists and is active
        if (!$user || ($user['status'] ?? '') !== 'active') {
            self::logout();
            return null;
        }

        self::$currentUser = $user;
        return self::$currentUser;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user ? (string) $user['role'] : null;
    }

    public static function isSuperuser(): bool
    {
        return self::role() === 'superuser';
    }

    public static function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));
        $ip = Security::clientIp();

        if (self::isRateLimited($email, $ip)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please wait 15 minutes before trying again.',
            ];
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, (string) $user['password'])) {
            self::recordFailedAttempt($email, $ip);
            ActivityLog::record(null, 'auth.login_failed', sprintf('Failed login attempt for email: %s', $email), $ip);
            return [
                'success' => false,
                'message' => 'Invalid email address or password.',
            ];
        }

        if (($user['status'] ?? '') !== 'active') {
            ActivityLog::record((int) $user['id'], 'auth.login_blocked', 'Login attempt on inactive user account.', $ip);
            return [
                'success' => false,
                'message' => 'Your account is currently inactive. Please contact the administrator.',
            ];
        }

        // Successful login: clear failed attempts & regenerate session ID
        self::clearFailedAttempts($email, $ip);
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
        $_SESSION['_created_at'] = time();

        $userModel->updateLastLogin((int) $user['id']);
        self::$currentUser = $user;
        self::$loaded = true;

        ActivityLog::record((int) $user['id'], 'auth.login', 'User signed in successfully.', $ip);

        return [
            'success' => true,
            'user'    => $user,
        ];
    }

    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            ActivityLog::record((int) $userId, 'auth.logout', 'User signed out.', Security::clientIp());
        }

        self::$currentUser = null;
        self::$loaded = false;

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function isRateLimited(string $email, string $ip): bool
    {
        $config = require dirname(__DIR__) . '/Config/app.php';
        $maxAttempts = (int) ($config['rate_limit_max'] ?? 5);
        $windowMinutes = (int) ($config['rate_limit_window'] ?? 15);

        $db = Database::connection();

        // Cleanup old attempts older than 1 day occasionally
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (ip_address = :ip OR email = :email)
               AND attempted_at >= (NOW() - INTERVAL :window MINUTE)'
        );
        $stmt->execute([
            ':ip'     => $ip,
            ':email'  => $email,
            ':window' => $windowMinutes,
        ]);

        return (int) $stmt->fetchColumn() >= $maxAttempts;
    }

    public static function recordFailedAttempt(string $email, string $ip): void
    {
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO login_attempts (ip_address, email, attempted_at) VALUES (:ip, :email, NOW())'
        );
        $stmt->execute([
            ':ip'    => $ip,
            ':email' => substr($email, 0, 190),
        ]);
    }

    public static function clearFailedAttempts(string $email, string $ip): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('DELETE FROM login_attempts WHERE ip_address = :ip OR email = :email');
        $stmt->execute([
            ':ip'    => $ip,
            ':email' => $email,
        ]);
    }

    public static function refreshUser(): void
    {
        self::$loaded = false;
        self::$currentUser = null;
        self::user();
    }
}

