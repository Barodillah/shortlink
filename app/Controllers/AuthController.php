<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isSuperuser() ? '/admin/dashboard' : '/dashboard');
        }

        $this->view('auth/login', [
            'pageTitle' => 'Sign In — ' . app_setting('app_name', 'SLink'),
        ], 'layouts/guest');
    }

    public function login(): void
    {
        Security::requireCsrf();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        set_old_input(['email' => $email]);

        if ($email === '' || $password === '') {
            set_flash('error', 'Please enter both your email address and password.');
            $this->redirect('/login');
        }

        $result = Auth::attempt($email, $password);
        if (!$result['success']) {
            set_flash('error', $result['message']);
            $this->redirect('/login');
        }

        clear_old_input();
        set_flash('success', 'Welcome back, ' . $result['user']['name'] . '.');

        if (($result['user']['role'] ?? '') === 'superuser') {
            $this->redirect('/admin/dashboard');
        }

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::requireCsrf();
        }
        Auth::logout();
        // Start a clean session to store flash message
        $appConfig = require dirname(__DIR__) . '/Config/app.php';
        Security::initSession($appConfig);
        set_flash('success', 'You have been signed out safely.');
        $this->redirect('/login');
    }
}

