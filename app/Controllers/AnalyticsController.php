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

class AnalyticsController extends Controller
{
    private Click $clickModel;
    private Shortlink $shortlinkModel;

    public function __construct()
    {
        $this->clickModel = new Click();
        $this->shortlinkModel = new Shortlink();
    }

    /**
     * User Analytics Overview (/analytics) - Scoped strictly to logged-in user's shortlinks
     */
    public function userIndex(): void
    {
        $userId = (int) Auth::id();
        $days = max(7, min(90, (int) ($_GET['days'] ?? 30)));
        $traffic = (string) ($_GET['traffic'] ?? 'human');
        $botFilter = in_array($traffic, ['all', 'human', 'bot'], true) ? $traffic : 'human';

        $data = $this->compileAnalyticsPayload(null, $userId, $days, $botFilter);

        $this->view('analytics/overview', array_merge($data, [
            'pageTitle' => 'My Link Analytics — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'analytics',
            'isGlobal' => false,
            'days' => $days,
            'botFilter' => $botFilter,
        ]));
    }

    /**
     * Superuser Global Analytics (/admin/analytics)
     */
    public function globalIndex(): void
    {
        $days = max(7, min(90, (int) ($_GET['days'] ?? 30)));
        $traffic = (string) ($_GET['traffic'] ?? 'human');
        $botFilter = in_array($traffic, ['all', 'human', 'bot'], true) ? $traffic : 'human';
        $filterUserId = !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null;

        $data = $this->compileAnalyticsPayload(null, $filterUserId, $days, $botFilter);
        $userModel = new User();

        $this->view('analytics/overview', array_merge($data, [
            'pageTitle' => 'Global Platform Analytics — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_analytics',
            'isGlobal' => true,
            'days' => $days,
            'botFilter' => $botFilter,
            'filterUserId' => $filterUserId,
            'usersList' => $userModel->allSimple(),
        ]));
    }

    /**
     * Detailed Analytics for a single Shortlink (/shortlinks/{id}/analytics)
     * Enforces strict ownership validation (Anti-IDOR).
     */
    public function detail(string $id): void
    {
        $linkId = (int) $id;
        $shortlink = $this->shortlinkModel->findById($linkId);

        if (!$shortlink) {
            self::renderError(404, 'Shortlink Not Found', 'The requested shortlink does not exist.');
        }

        if (!Auth::isSuperuser() && (int) $shortlink['user_id'] !== (int) Auth::id()) {
            ActivityLog::record(
                Auth::id(),
                'security.idor_blocked',
                sprintf('Blocked unauthorized analytics access to shortlink ID #%d', $linkId),
                Security::clientIp()
            );
            self::renderError(403, 'Access Denied', 'You do not have permission to view analytics for this shortlink.');
        }

        $days = max(7, min(90, (int) ($_GET['days'] ?? 30)));
        $traffic = (string) ($_GET['traffic'] ?? 'human');
        $botFilter = in_array($traffic, ['all', 'human', 'bot'], true) ? $traffic : 'human';

        $data = $this->compileAnalyticsPayload($linkId, null, $days, $botFilter);

        $this->view('analytics/detail', array_merge($data, [
            'pageTitle' => 'Analytics: /' . $shortlink['short_code'] . ' — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => Auth::isSuperuser() && (int) $shortlink['user_id'] !== (int) Auth::id() ? 'admin_shortlinks' : 'shortlinks',
            'shortlink' => $shortlink,
            'days' => $days,
            'botFilter' => $botFilter,
        ]));
    }

    private function compileAnalyticsPayload(?int $shortlinkId, ?int $userId, int $days, string $botFilter): array
    {
        return [
            'periodSummary' => $this->clickModel->periodSummary($shortlinkId, $userId, $days),
            'clicksByDate' => $this->clickModel->clicksByDate($shortlinkId, $userId, $days),
            'byDevice' => $this->clickModel->breakdownByColumn('device_type', $shortlinkId, $userId, $days, $botFilter, 6),
            'byBrowser' => $this->clickModel->breakdownByColumn('browser', $shortlinkId, $userId, $days, $botFilter, 8),
            'byOs' => $this->clickModel->breakdownByColumn('os', $shortlinkId, $userId, $days, $botFilter, 8),
            'byCountry' => $this->clickModel->breakdownByColumn('country', $shortlinkId, $userId, $days, $botFilter, 8),
            'byReferrer' => $this->clickModel->breakdownByColumn('referrer_domain', $shortlinkId, $userId, $days, $botFilter, 8),
            'utmRows' => $this->clickModel->utmPerformance($shortlinkId, $userId, $days, $botFilter, 10),
            'recentClicks' => $this->clickModel->recentClicks($shortlinkId, $userId, 20, $botFilter),
            'topLinks' => $shortlinkId === null ? $this->shortlinkModel->topPerforming($userId, 6) : [],
        ];
    }
}
