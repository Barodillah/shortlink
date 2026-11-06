<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ActivityLog;
use App\Models\Click;
use App\Models\Shortlink;
use App\Models\User;

class DashboardController extends Controller
{
    public function home(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
        if (Auth::isSuperuser()) {
            $this->redirect('/admin/dashboard');
        }
        $this->redirect('/dashboard');
    }

    /**
     * User Personal Dashboard (/dashboard)
     */
    public function userDashboard(): void
    {
        $userId = (int) Auth::id();
        $userModel = new User();
        $shortlinkModel = new Shortlink();
        $clickModel = new Click();

        $days = max(7, min(90, (int) ($_GET['days'] ?? 14)));
        $stats = $userModel->getUserSummaryStats($userId);
        $topLinks = $shortlinkModel->topPerforming($userId, 5);
        $trends = $clickModel->clicksByDate(null, $userId, $days);
        $recentLinks = $shortlinkModel->paginate(['user_id' => $userId, 'sort' => 'newest'], 1, 5)['items'];

        $this->view('dashboard/user', [
            'pageTitle'   => 'Dashboard — ' . app_setting('app_name', 'SLink'),
            'activeMenu'  => 'dashboard',
            'stats'       => $stats,
            'topLinks'    => $topLinks,
            'trends'      => $trends,
            'recentLinks' => $recentLinks,
            'days'        => $days,
        ]);
    }

    /**
     * Superuser Global Dashboard (/admin/dashboard)
     */
    public function globalDashboard(): void
    {
        $shortlinkModel = new Shortlink();
        $userModel = new User();
        $clickModel = new Click();
        $logModel = new ActivityLog();

        $days = max(7, min(90, (int) ($_GET['days'] ?? 14)));
        $stats = $shortlinkModel->globalSummaryStats();
        $topLinks = $shortlinkModel->topPerforming(null, 6);
        $topUsers = $userModel->getTopUsers(5);
        $trends = $clickModel->clicksByDate(null, null, $days);
        $recentLogs = $logModel->paginate([], 1, 8)['items'];

        $this->view('dashboard/superuser', [
            'pageTitle'  => 'Global Dashboard — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_dashboard',
            'stats'      => $stats,
            'topLinks'   => $topLinks,
            'topUsers'   => $topUsers,
            'trends'     => $trends,
            'recentLogs' => $recentLogs,
            'days'       => $days,
        ]);
    }
}

