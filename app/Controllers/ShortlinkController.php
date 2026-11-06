<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Security;
use App\Models\ActivityLog;
use App\Models\Shortlink;
use App\Models\User;

class ShortlinkController extends Controller
{
    private Shortlink $shortlinkModel;

    public function __construct()
    {
        $this->shortlinkModel = new Shortlink();
    }

    /**
     * User's personal shortlinks list (/shortlinks)
     */
    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'user_id' => (int) Auth::id(),
            'search'  => trim((string) ($_GET['q'] ?? '')),
            'status'  => trim((string) ($_GET['status'] ?? '')),
            'sort'    => trim((string) ($_GET['sort'] ?? 'newest')),
        ];

        $pagination = $this->shortlinkModel->paginate($filters, $page, 10);

        $this->view('shortlinks/index', [
            'pageTitle'  => 'My Shortlinks — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'shortlinks',
            'pagination' => $pagination,
            'filters'    => $filters,
            'isGlobal'   => false,
        ]);
    }

    /**
     * Superuser view of all shortlinks across all users (/admin/shortlinks)
     */
    public function all(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'user_id' => !empty($_GET['user_id']) ? (int) $_GET['user_id'] : null,
            'search'  => trim((string) ($_GET['q'] ?? '')),
            'status'  => trim((string) ($_GET['status'] ?? '')),
            'sort'    => trim((string) ($_GET['sort'] ?? 'newest')),
        ];

        $pagination = $this->shortlinkModel->paginate($filters, $page, 12);
        $userModel = new User();

        $this->view('shortlinks/index', [
            'pageTitle'  => 'All Platform Shortlinks — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'admin_shortlinks',
            'pagination' => $pagination,
            'filters'    => $filters,
            'isGlobal'   => true,
            'usersList'  => $userModel->allSimple(),
        ]);
    }

    /**
     * Show Create Shortlink form (/shortlinks/create)
     */
    public function create(): void
    {
        $this->view('shortlinks/form', [
            'pageTitle'  => 'Create Shortlink — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => 'shortlinks_create',
            'mode'       => 'create',
            'shortlink'  => null,
        ]);
    }

    /**
     * Store a newly created shortlink (POST /shortlinks)
     */
    public function store(): void
    {
        Security::requireCsrf();

        $destinationUrl = trim((string) ($_POST['destination_url'] ?? ''));
        $customAlias    = trim((string) ($_POST['custom_alias'] ?? ''));
        $title          = trim((string) ($_POST['title'] ?? ''));
        $description    = trim((string) ($_POST['description'] ?? ''));
        $expiresAtInput = trim((string) ($_POST['expires_at'] ?? ''));
        $maxClicksInput = trim((string) ($_POST['max_clicks'] ?? ''));
        $statusInput    = (string) ($_POST['status'] ?? 'active');
        $status         = in_array($statusInput, ['active', 'inactive'], true) ? $statusInput : 'active';

        set_old_input($_POST);

        // 1. Validate destination URL
        $urlValidation = Security::validateDestinationUrl($destinationUrl);
        if (!$urlValidation['valid']) {
            set_flash('error', $urlValidation['error']);
            $this->redirect('/shortlinks/create');
        }

        // 2. Validate or generate short code
        $allowCustomAlias = (string) app_setting('allow_custom_alias', '1') === '1';
        if ($customAlias !== '') {
            if (!$allowCustomAlias && !Auth::isSuperuser()) {
                set_flash('error', 'Custom aliases are currently disabled by the administrator.');
                $this->redirect('/shortlinks/create');
            }

            $aliasValidation = Security::validateAlias($customAlias);
            if (!$aliasValidation['valid']) {
                set_flash('error', $aliasValidation['error']);
                $this->redirect('/shortlinks/create');
            }

            if ($this->shortlinkModel->codeExists($customAlias)) {
                set_flash('error', sprintf('The alias "%s" is already in use. Please choose another alias.', $customAlias));
                $this->redirect('/shortlinks/create');
            }
            $shortCode = $customAlias;
        } else {
            $shortCode = $this->shortlinkModel->generateUniqueCode();
        }

        // Prevent self-redirect loop
        $generatedShortUrl = short_url($shortCode);
        if (rtrim(strtolower($destinationUrl), '/') === rtrim(strtolower($generatedShortUrl), '/')) {
            set_flash('error', 'Destination URL cannot point to the shortlink itself.');
            $this->redirect('/shortlinks/create');
        }

        // 3. Validate expiration date
        $expiresAt = null;
        if ($expiresAtInput !== '') {
            $ts = strtotime($expiresAtInput);
            if (!$ts) {
                set_flash('error', 'Invalid expiration date format.');
                $this->redirect('/shortlinks/create');
            }
            if ($ts <= time()) {
                set_flash('error', 'Expiration date must be in the future.');
                $this->redirect('/shortlinks/create');
            }
            $expiresAt = date('Y-m-d H:i:s', $ts);
        }

        // 4. Validate maximum clicks
        $maxClicks = null;
        if ($maxClicksInput !== '') {
            if (!ctype_digit($maxClicksInput) || (int) $maxClicksInput < 1) {
                set_flash('error', 'Maximum clicks must be a positive whole number.');
                $this->redirect('/shortlinks/create');
            }
            $maxClicks = (int) $maxClicksInput;
        }

        // 5. Optional per-user quota check
        $maxLinksPerUser = (int) app_setting('max_links_per_user', 0);
        if ($maxLinksPerUser > 0 && !Auth::isSuperuser()) {
            $userModel = new User();
            $userStats = $userModel->getUserSummaryStats((int) Auth::id());
            if ($userStats['total_shortlinks'] >= $maxLinksPerUser) {
                set_flash('error', sprintf('You have reached the maximum limit of %d active shortlinks.', $maxLinksPerUser));
                $this->redirect('/shortlinks');
            }
        }

        $linkId = $this->shortlinkModel->create([
            'user_id'         => (int) Auth::id(),
            'short_code'      => $shortCode,
            'destination_url' => $destinationUrl,
            'title'           => $title !== '' ? substr($title, 0, 255) : null,
            'description'     => $description !== '' ? substr($description, 0, 1000) : null,
            'status'          => $status,
            'expires_at'      => $expiresAt,
            'max_clicks'      => $maxClicks,
        ]);

        clear_old_input();

        ActivityLog::record(
            Auth::id(),
            'shortlink.create',
            sprintf('Created shortlink /%s (ID #%d) -> %s', $shortCode, $linkId, substr($destinationUrl, 0, 120)),
            Security::clientIp()
        );

        set_flash('success', sprintf('Shortlink /%s created successfully.', $shortCode));
        $this->redirect('/shortlinks');
    }

    /**
     * Show Edit Shortlink form (/shortlinks/{id}/edit)
     */
    public function edit(string $id): void
    {
        $shortlink = $this->findAuthorizedLink((int) $id);

        $this->view('shortlinks/form', [
            'pageTitle'  => 'Edit Shortlink /' . $shortlink['short_code'] . ' — ' . app_setting('app_name', 'SLink'),
            'activeMenu' => Auth::isSuperuser() && (int) $shortlink['user_id'] !== (int) Auth::id() ? 'admin_shortlinks' : 'shortlinks',
            'mode'       => 'edit',
            'shortlink'  => $shortlink,
        ]);
    }

    /**
     * Update an existing shortlink (POST /shortlinks/{id}/update)
     */
    public function update(string $id): void
    {
        Security::requireCsrf();
        $linkId = (int) $id;
        $shortlink = $this->findAuthorizedLink($linkId);

        $destinationUrl = trim((string) ($_POST['destination_url'] ?? ''));
        $customAlias    = trim((string) ($_POST['custom_alias'] ?? ''));
        $title          = trim((string) ($_POST['title'] ?? ''));
        $description    = trim((string) ($_POST['description'] ?? ''));
        $expiresAtInput = trim((string) ($_POST['expires_at'] ?? ''));
        $maxClicksInput = trim((string) ($_POST['max_clicks'] ?? ''));
        $statusInput    = (string) ($_POST['status'] ?? 'active');
        $status         = in_array($statusInput, ['active', 'inactive'], true) ? $statusInput : 'active';

        $urlValidation = Security::validateDestinationUrl($destinationUrl);
        if (!$urlValidation['valid']) {
            set_flash('error', $urlValidation['error']);
            $this->redirect('/shortlinks/' . $linkId . '/edit');
        }

        $shortCode = $shortlink['short_code'];
        if ($customAlias !== '' && $customAlias !== $shortlink['short_code']) {
            $aliasValidation = Security::validateAlias($customAlias);
            if (!$aliasValidation['valid']) {
                set_flash('error', $aliasValidation['error']);
                $this->redirect('/shortlinks/' . $linkId . '/edit');
            }
            if ($this->shortlinkModel->codeExists($customAlias, $linkId)) {
                set_flash('error', sprintf('The alias "%s" is already taken by another shortlink.', $customAlias));
                $this->redirect('/shortlinks/' . $linkId . '/edit');
            }
            $shortCode = $customAlias;
        }

        $expiresAt = null;
        if ($expiresAtInput !== '') {
            $ts = strtotime($expiresAtInput);
            if (!$ts) {
                set_flash('error', 'Invalid expiration date format.');
                $this->redirect('/shortlinks/' . $linkId . '/edit');
            }
            $expiresAt = date('Y-m-d H:i:s', $ts);
        }

        $maxClicks = null;
        if ($maxClicksInput !== '') {
            if (!ctype_digit($maxClicksInput) || (int) $maxClicksInput < 1) {
                set_flash('error', 'Maximum clicks must be a positive whole number.');
                $this->redirect('/shortlinks/' . $linkId . '/edit');
            }
            $maxClicks = (int) $maxClicksInput;
        }

        $this->shortlinkModel->update($linkId, [
            'short_code'      => $shortCode,
            'destination_url' => $destinationUrl,
            'title'           => $title !== '' ? substr($title, 0, 255) : null,
            'description'     => $description !== '' ? substr($description, 0, 1000) : null,
            'status'          => $status,
            'expires_at'      => $expiresAt,
            'max_clicks'      => $maxClicks,
        ]);

        ActivityLog::record(
            Auth::id(),
            'shortlink.update',
            sprintf('Updated shortlink /%s (ID #%d)', $shortCode, $linkId),
            Security::clientIp()
        );

        set_flash('success', sprintf('Shortlink /%s has been updated.', $shortCode));
        $returnTo = ($_POST['return_to'] ?? '') === 'admin' && Auth::isSuperuser() ? '/admin/shortlinks' : '/shortlinks';
        $this->redirect($returnTo);
    }

    /**
     * Toggle active/inactive status (POST /shortlinks/{id}/toggle)
     */
    public function toggle(string $id): void
    {
        Security::requireCsrf();
        $linkId = (int) $id;
        $shortlink = $this->findAuthorizedLink($linkId);

        $newStatus = $this->shortlinkModel->toggleStatus($linkId);

        ActivityLog::record(
            Auth::id(),
            'shortlink.toggle',
            sprintf('Changed status of shortlink /%s (ID #%d) to %s', $shortlink['short_code'], $linkId, (string) $newStatus),
            Security::clientIp()
        );

        if (Security::isAjax()) {
            $this->json([
                'success'    => true,
                'new_status' => $newStatus,
                'message'    => sprintf('Shortlink /%s is now %s.', $shortlink['short_code'], $newStatus),
            ]);
        }

        set_flash('success', sprintf('Shortlink /%s is now %s.', $shortlink['short_code'], (string) $newStatus));
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/shortlinks');
        $this->redirect($referer);
    }

    /**
     * Soft delete shortlink (POST /shortlinks/{id}/delete)
     */
    public function destroy(string $id): void
    {
        Security::requireCsrf();
        $linkId = (int) $id;
        $shortlink = $this->findAuthorizedLink($linkId);

        $this->shortlinkModel->softDelete($linkId);

        ActivityLog::record(
            Auth::id(),
            'shortlink.delete',
            sprintf('Deleted shortlink /%s (ID #%d)', $shortlink['short_code'], $linkId),
            Security::clientIp()
        );

        set_flash('success', sprintf('Shortlink /%s has been deleted.', $shortlink['short_code']));
        $returnTo = ($_POST['return_to'] ?? '') === 'admin' && Auth::isSuperuser() ? '/admin/shortlinks' : '/shortlinks';
        $this->redirect($returnTo);
    }

    /**
     * Strict server-side ownership validation (Anti-IDOR protection).
     * Regular users can ONLY access shortlinks where user_id == Auth::id().
     * Superusers can access all shortlinks.
     */
    private function findAuthorizedLink(int $id): array
    {
        $shortlink = $this->shortlinkModel->findById($id);
        if (!$shortlink) {
            self::renderError(404, 'Shortlink Not Found', 'The requested shortlink does not exist or has been removed.');
        }

        if (!Auth::isSuperuser() && (int) $shortlink['user_id'] !== (int) Auth::id()) {
            ActivityLog::record(
                Auth::id(),
                'security.idor_blocked',
                sprintf('Blocked unauthorized access attempt to shortlink ID #%d owned by user #%d', $id, (int) $shortlink['user_id']),
                Security::clientIp()
            );
            self::renderError(403, 'Access Denied', 'You do not have permission to view or modify this shortlink.');
        }

        return $shortlink;
    }
}
