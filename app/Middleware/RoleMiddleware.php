<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\ActivityLog;

class RoleMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }

        if (!Auth::isSuperuser()) {
            ActivityLog::record(
                Auth::id(),
                'security.forbidden_role',
                'Unauthorized attempt to access superuser route: ' . ($_SERVER['REQUEST_URI'] ?? ''),
                Security::clientIp()
            );

            if (Security::isAjax()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Access denied. Superuser privileges required.']);
                exit;
            }

            Controller::renderError(403, 'Access Denied', 'You do not have permission to access this resource.');
        }
    }
}

