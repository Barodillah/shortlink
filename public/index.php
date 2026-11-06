<?php
declare(strict_types=1);

use App\Controllers\AnalyticsController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\RedirectController;
use App\Controllers\SettingsController;
use App\Controllers\ShortlinkController;
use App\Controllers\UserController;
use App\Core\Controller;
use App\Core\Router;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

// 1. Load Application Config
$appConfig = require dirname(__DIR__) . '/app/Config/app.php';
date_default_timezone_set((string) ($appConfig['timezone'] ?? 'Asia/Jakarta'));

// 2. Production Error Handling (never expose raw stack traces in production)
if (!empty($appConfig['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

// 3. PSR-4 Style Class Autoloader for App\* namespace
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// 4. Load Global Helper Functions
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

// 5. Global Exception Handler
set_exception_handler(static function (\Throwable $e) use ($appConfig): void {
    log_error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'uri'  => $_SERVER['REQUEST_URI'] ?? '',
    ]);
    http_response_code(500);
    if (!empty($appConfig['debug'])) {
        echo '<pre>' . e($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
        exit;
    }
    Controller::renderError(500, 'System Error', 'An unexpected error occurred while processing your request.');
});

// 6. Initialize Secure Session & Send HTTP Security Headers
Security::initSession($appConfig);
Security::sendSecurityHeaders();

// 7. Register Routes
$router = new Router();

// Root entry
$router->get('/', [DashboardController::class, 'home']);

// Authentication routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/logout', [AuthController::class, 'logout']);

// User Panel routes (Authenticated)
$router->get('/dashboard', [DashboardController::class, 'userDashboard'], [AuthMiddleware::class]);
$router->get('/shortlinks', [ShortlinkController::class, 'index'], [AuthMiddleware::class]);
$router->get('/shortlinks/create', [ShortlinkController::class, 'create'], [AuthMiddleware::class]);
$router->post('/shortlinks', [ShortlinkController::class, 'store'], [AuthMiddleware::class]);
$router->get('/shortlinks/{id:[0-9]+}/edit', [ShortlinkController::class, 'edit'], [AuthMiddleware::class]);
$router->post('/shortlinks/{id:[0-9]+}/update', [ShortlinkController::class, 'update'], [AuthMiddleware::class]);
$router->post('/shortlinks/{id:[0-9]+}/toggle', [ShortlinkController::class, 'toggle'], [AuthMiddleware::class]);
$router->post('/shortlinks/{id:[0-9]+}/delete', [ShortlinkController::class, 'destroy'], [AuthMiddleware::class]);
$router->get('/shortlinks/{id:[0-9]+}/analytics', [AnalyticsController::class, 'detail'], [AuthMiddleware::class]);

$router->get('/analytics', [AnalyticsController::class, 'userIndex'], [AuthMiddleware::class]);
$router->get('/profile', [UserController::class, 'profile'], [AuthMiddleware::class]);
$router->post('/profile/update', [UserController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->post('/profile/password', [UserController::class, 'changePassword'], [AuthMiddleware::class]);

// Superuser Panel routes (Authenticated + Superuser Role)
$router->get('/admin/dashboard', [DashboardController::class, 'globalDashboard'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->get('/admin/shortlinks', [ShortlinkController::class, 'all'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->get('/admin/analytics', [AnalyticsController::class, 'globalIndex'], [AuthMiddleware::class, RoleMiddleware::class]);

$router->get('/admin/users', [UserController::class, 'index'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->get('/admin/users/create', [UserController::class, 'create'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->post('/admin/users', [UserController::class, 'store'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->get('/admin/users/{id:[0-9]+}', [UserController::class, 'show'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->get('/admin/users/{id:[0-9]+}/edit', [UserController::class, 'edit'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->post('/admin/users/{id:[0-9]+}/update', [UserController::class, 'update'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->post('/admin/users/{id:[0-9]+}/toggle', [UserController::class, 'toggle'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->post('/admin/users/{id:[0-9]+}/reset-password', [UserController::class, 'resetPassword'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->post('/admin/users/{id:[0-9]+}/delete', [UserController::class, 'destroy'], [AuthMiddleware::class, RoleMiddleware::class]);

$router->get('/admin/settings', [SettingsController::class, 'index'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->post('/admin/settings', [SettingsController::class, 'update'], [AuthMiddleware::class, RoleMiddleware::class]);
$router->get('/admin/logs', [SettingsController::class, 'logs'], [AuthMiddleware::class, RoleMiddleware::class]);

// Public Shortlink Redirect Endpoint (must be registered last)
$router->get('/{code:[a-zA-Z0-9_-]+}', [RedirectController::class, 'handle']);

// 8. Dispatch Current Request
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

