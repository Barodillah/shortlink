<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;

class SettingsController extends Controller
{
    public function index(): void
    {
        $settings = Setting::allKeyValue(true);

        $this->view('settings/index', [
            'pageTitle'  => 'Application Settings — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_settings',
            'settings'   => $settings,
        ]);
    }

    public function update(): void
    {
        Security::requireCsrf();

        $appName           = trim((string) ($_POST['app_name'] ?? 'SLink'));
        $appTagline        = trim((string) ($_POST['app_tagline'] ?? ''));
        $shortlinkDomain   = rtrim(trim((string) ($_POST['shortlink_domain'] ?? '')), '/');
        $defaultCodeLength = max(6, min(8, (int) ($_POST['default_code_length'] ?? 6)));
        $redirectCode      = in_array((int) ($_POST['redirect_status_code'] ?? 302), [301, 302, 307], true)
            ? (string) (int) $_POST['redirect_status_code']
            : '302';
        $allowCustomAlias  = isset($_POST['allow_custom_alias']) ? '1' : '0';
        $maxLinksPerUser   = max(0, (int) ($_POST['max_links_per_user'] ?? 0));

        if ($shortlinkDomain !== '') {
            if (!filter_var($shortlinkDomain, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $shortlinkDomain)) {
                set_flash('error', 'Shortlink domain must be a valid URL starting with https:// or http:// (or leave blank to auto-detect).');
                $this->redirect('/admin/settings');
            }
        }

        Setting::updateBatch([
            'app_name'             => $appName !== '' ? substr($appName, 0, 80) : 'SLink',
            'app_tagline'          => substr($appTagline, 0, 160),
            'shortlink_domain'     => $shortlinkDomain,
            'default_code_length'  => (string) $defaultCodeLength,
            'redirect_status_code' => $redirectCode,
            'allow_custom_alias'   => $allowCustomAlias,
            'max_links_per_user'   => (string) $maxLinksPerUser,
        ]);

        ActivityLog::record(
            Auth::id(),
            'settings.update',
            sprintf('Updated application settings (domain: %s, redirect: %s, code_len: %d)', $shortlinkDomain ?: 'auto', $redirectCode, $defaultCodeLength),
            Security::clientIp()
        );

        set_flash('success', 'Application configuration saved successfully.');
        $this->redirect('/admin/settings');
    }

    public function logs(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'search'  => trim((string) ($_GET['q'] ?? '')),
            'user_id' => !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null,
            'action'  => trim((string) ($_GET['action'] ?? '')),
        ];

        $logModel = new ActivityLog();
        $userModel = new User();
        $pagination = $logModel->paginate($filters, $page, 20);

        $this->view('settings/logs', [
            'pageTitle'  => 'Activity & System Logs — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_logs',
            'pagination' => $pagination,
            'filters'    => $filters,
            'usersList'  => $userModel->allSimple(),
        ]);
    }
}

