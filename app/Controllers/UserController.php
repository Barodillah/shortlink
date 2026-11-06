<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Shortlink;
use App\Models\User;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Superuser: List all users (/admin/users)
     */
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'search' => trim((string) ($_GET['q'] ?? '')),
            'role'   => trim((string) ($_GET['role'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
        ];

        $pagination = $this->userModel->paginateWithStats($filters, $page, 12);

        $this->view('users/index', [
            'pageTitle'  => 'User Management — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_users',
            'pagination' => $pagination,
            'filters'    => $filters,
        ]);
    }

    /**
     * Superuser: Show Create User form (/admin/users/create)
     */
    public function create(): void
    {
        $this->view('users/form', [
            'pageTitle'  => 'Create User — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_users',
            'mode'       => 'create',
            'targetUser' => null,
        ]);
    }

    /**
     * Superuser: Store new user (POST /admin/users)
     */
    public function store(): void
    {
        Security::requireCsrf();

        $name     = trim((string) ($_POST['name'] ?? ''));
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password    = (string) ($_POST['password'] ?? '');
        $roleInput   = (string) ($_POST['role'] ?? 'user');
        $statusInput = (string) ($_POST['status'] ?? 'active');
        $role        = in_array($roleInput, ['superuser', 'user'], true) ? $roleInput : 'user';
        $status      = in_array($statusInput, ['active', 'inactive'], true) ? $statusInput : 'active';

        set_old_input(['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status]);

        if ($name === '' || strlen($name) < 2) {
            set_flash('error', 'Name must be at least 2 characters long.');
            $this->redirect('/admin/users/create');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please enter a valid email address.');
            $this->redirect('/admin/users/create');
        }

        if ($this->userModel->emailExists($email)) {
            set_flash('error', 'An account with this email address already exists.');
            $this->redirect('/admin/users/create');
        }

        if (strlen($password) < 8) {
            set_flash('error', 'Password must be at least 8 characters long.');
            $this->redirect('/admin/users/create');
        }

        $newUserId = $this->userModel->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
            'status'   => $status,
        ]);

        clear_old_input();

        ActivityLog::record(
            Auth::id(),
            'user.create',
            sprintf('Created %s account for %s (%s, ID #%d)', $role, $name, $email, $newUserId),
            Security::clientIp()
        );

        set_flash('success', sprintf('User account for %s has been created.', $name));
        $this->redirect('/admin/users');
    }

    /**
     * Superuser: View User Detail & Statistics (/admin/users/{id})
     */
    public function show(string $id): void
    {
        $userId = (int) $id;
        $targetUser = $this->userModel->findById($userId);
        if (!$targetUser) {
            self::renderError(404, 'User Not Found', 'The requested user account does not exist.');
        }

        $shortlinkModel = new Shortlink();
        $clickModel = new Click();
        $logModel = new ActivityLog();

        $stats = $this->userModel->getUserSummaryStats($userId);
        $trends = $clickModel->clicksByDate(null, $userId, 14);
        $userLinks = $shortlinkModel->paginate(['user_id' => $userId, 'sort' => 'newest'], (int) ($_GET['page'] ?? 1), 10);
        $userLogs = $logModel->recentByUser($userId, 15);

        $this->view('users/detail', [
            'pageTitle'  => $targetUser['name'] . ' — User Detail',
            'activeMenu' => 'admin_users',
            'targetUser' => $targetUser,
            'stats'      => $stats,
            'trends'     => $trends,
            'userLinks'  => $userLinks,
            'userLogs'   => $userLogs,
        ]);
    }

    /**
     * Superuser: Show Edit User form (/admin/users/{id}/edit)
     */
    public function edit(string $id): void
    {
        $targetUser = $this->userModel->findById((int) $id);
        if (!$targetUser) {
            self::renderError(404, 'User Not Found', 'The requested user account does not exist.');
        }

        $this->view('users/form', [
            'pageTitle'  => 'Edit User: ' . $targetUser['name'],
            'activeMenu' => 'admin_users',
            'mode'       => 'edit',
            'targetUser' => $targetUser,
        ]);
    }

    /**
     * Superuser: Update user profile/role/status (POST /admin/users/{id}/update)
     */
    public function update(string $id): void
    {
        Security::requireCsrf();
        $userId = (int) $id;
        $targetUser = $this->userModel->findById($userId);
        if (!$targetUser) {
            self::renderError(404, 'User Not Found', 'The requested user account does not exist.');
        }

        $name   = trim((string) ($_POST['name'] ?? ''));
        $email  = strtolower(trim((string) ($_POST['email'] ?? '')));
        $role   = in_array($_POST['role'] ?? 'user', ['superuser', 'user'], true) ? $_POST['role'] : 'user';
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';

        if ($name === '' || strlen($name) < 2) {
            set_flash('error', 'Name must be at least 2 characters long.');
            $this->redirect('/admin/users/' . $userId . '/edit');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please enter a valid email address.');
            $this->redirect('/admin/users/' . $userId . '/edit');
        }

        if ($this->userModel->emailExists($email, $userId)) {
            set_flash('error', 'Another user is already using that email address.');
            $this->redirect('/admin/users/' . $userId . '/edit');
        }

        // Prevent superuser from demoting or deactivating their own account
        if ($userId === (int) Auth::id() && ($role !== 'superuser' || $status !== 'active')) {
            set_flash('error', 'You cannot demote or deactivate your own active Superuser account.');
            $this->redirect('/admin/users/' . $userId . '/edit');
        }

        $this->userModel->update($userId, [
            'name'   => $name,
            'email'  => $email,
            'role'   => $role,
            'status' => $status,
        ]);

        // Optional password reset if provided in edit form
        $newPassword = (string) ($_POST['new_password'] ?? '');
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                set_flash('error', 'New password must be at least 8 characters long.');
                $this->redirect('/admin/users/' . $userId . '/edit');
            }
            $this->userModel->updatePassword($userId, $newPassword);
        }

        ActivityLog::record(
            Auth::id(),
            'user.update',
            sprintf('Updated user #%d (%s, role: %s, status: %s)', $userId, $email, $role, $status),
            Security::clientIp()
        );

        set_flash('success', sprintf('User %s updated successfully.', $name));
        $this->redirect('/admin/users/' . $userId);
    }

    /**
     * Superuser: Toggle user active/inactive status (POST /admin/users/{id}/toggle)
     */
    public function toggle(string $id): void
    {
        Security::requireCsrf();
        $userId = (int) $id;

        if ($userId === (int) Auth::id()) {
            set_flash('error', 'You cannot deactivate your own account.');
            $this->redirect('/admin/users');
        }

        $targetUser = $this->userModel->findById($userId);
        if (!$targetUser) {
            self::renderError(404, 'User Not Found', 'User account not found.');
        }

        if ($targetUser['role'] === 'superuser' && $targetUser['status'] === 'active' && $this->userModel->countActiveSuperusers() <= 1) {
            set_flash('error', 'Cannot deactivate the last active Superuser account.');
            $this->redirect('/admin/users');
        }

        $newStatus = $this->userModel->toggleStatus($userId);

        ActivityLog::record(
            Auth::id(),
            'user.toggle_status',
            sprintf('Changed status of user %s (#%d) to %s', $targetUser['email'], $userId, (string) $newStatus),
            Security::clientIp()
        );

        set_flash('success', sprintf('User %s is now %s.', $targetUser['name'], (string) $newStatus));
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/admin/users');
        $this->redirect($referer);
    }

    /**
     * Superuser: Reset user password (POST /admin/users/{id}/reset-password)
     */
    public function resetPassword(string $id): void
    {
        Security::requireCsrf();
        $userId = (int) $id;
        $targetUser = $this->userModel->findById($userId);
        if (!$targetUser) {
            self::renderError(404, 'User Not Found', 'User account not found.');
        }

        $newPassword = (string) ($_POST['new_password'] ?? '');
        if (strlen($newPassword) < 8) {
            set_flash('error', 'Password must be at least 8 characters long.');
            $this->redirect('/admin/users/' . $userId);
        }

        $this->userModel->updatePassword($userId, $newPassword);

        ActivityLog::record(
            Auth::id(),
            'user.reset_password',
            sprintf('Reset password for user %s (#%d)', $targetUser['email'], $userId),
            Security::clientIp()
        );

        set_flash('success', sprintf('Password for %s has been reset.', $targetUser['name']));
        $this->redirect('/admin/users/' . $userId);
    }

    /**
     * Superuser: Safe delete user (POST /admin/users/{id}/delete)
     */
    public function destroy(string $id): void
    {
        Security::requireCsrf();
        $userId = (int) $id;

        if ($userId === (int) Auth::id()) {
            set_flash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
        }

        $targetUser = $this->userModel->findById($userId);
        if (!$targetUser) {
            self::renderError(404, 'User Not Found', 'User account not found.');
        }

        if ($targetUser['role'] === 'superuser' && $this->userModel->countActiveSuperusers() <= 1) {
            set_flash('error', 'Cannot delete the last Superuser account.');
            $this->redirect('/admin/users');
        }

        $deleted = $this->userModel->safeDelete($userId, (int) Auth::id());
        if (!$deleted) {
            set_flash('error', 'Could not delete user due to a database constraint error.');
            $this->redirect('/admin/users');
        }

        ActivityLog::record(
            Auth::id(),
            'user.delete',
            sprintf('Deleted user %s (%s, ID #%d) and archived their shortlinks.', $targetUser['name'], $targetUser['email'], $userId),
            Security::clientIp()
        );

        set_flash('success', sprintf('User %s has been safely deleted.', $targetUser['name']));
        $this->redirect('/admin/users');
    }

    /**
     * Logged-in user: View Profile & Change Password (/profile)
     */
    public function profile(): void
    {
        $user = Auth::user();
        $stats = $this->userModel->getUserSummaryStats((int) $user['id']);

        $this->view('users/profile', [
            'pageTitle'  => 'My Profile & Security — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'profile',
            'user'       => $user,
            'stats'      => $stats,
        ]);
    }

    /**
     * Logged-in user: Update own name & email (POST /profile/update)
     */
    public function updateProfile(): void
    {
        Security::requireCsrf();
        $userId = (int) Auth::id();

        $name  = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));

        if ($name === '' || strlen($name) < 2) {
            set_flash('error', 'Name must be at least 2 characters long.');
            $this->redirect('/profile');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Please enter a valid email address.');
            $this->redirect('/profile');
        }

        if ($this->userModel->emailExists($email, $userId)) {
            set_flash('error', 'This email address is already registered to another user.');
            $this->redirect('/profile');
        }

        $this->userModel->updateProfile($userId, $name, $email);
        Auth::refreshUser();

        ActivityLog::record($userId, 'profile.update', 'Updated personal profile information.', Security::clientIp());

        set_flash('success', 'Your profile has been updated.');
        $this->redirect('/profile');
    }

    /**
     * Logged-in user: Change own password (POST /profile/password)
     */
    public function changePassword(): void
    {
        Security::requireCsrf();
        $user = Auth::user();
        $userId = (int) $user['id'];

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($currentPassword, (string) $user['password'])) {
            set_flash('error', 'Your current password is incorrect.');
            $this->redirect('/profile');
        }

        if (strlen($newPassword) < 8) {
            set_flash('error', 'New password must be at least 8 characters long.');
            $this->redirect('/profile');
        }

        if ($newPassword !== $confirmPassword) {
            set_flash('error', 'New password and confirmation do not match.');
            $this->redirect('/profile');
        }

        $this->userModel->updatePassword($userId, $newPassword);
        session_regenerate_id(true);

        ActivityLog::record($userId, 'profile.password_change', 'Changed account password.', Security::clientIp());

        set_flash('success', 'Your password has been updated successfully.');
        $this->redirect('/profile');
    }
}
