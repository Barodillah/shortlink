<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Security;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            if (Security::isAjax()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Authentication required.']);
                exit;
            }
            set_flash('error', 'Please sign in to access this page.');
            header('Location: ' . url('/login'));
            exit;
        }
    }
}

