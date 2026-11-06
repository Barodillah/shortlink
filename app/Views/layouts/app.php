<?php
use App\Core\Auth;

$currentUser = Auth::user();
$isSuperuser = Auth::isSuperuser();
$activeMenu  = $activeMenu ?? 'dashboard';
$appName     = (string) app_setting('app_name', 'SLink');
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="<?= e(app_setting('app_tagline', 'Centralized Multi-User URL Shortener & Analytics')) ?>">
    <title><?= e($pageTitle ?? $appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
</head>
<body class="h-full bg-[#F8F8F8] text-[#171717]">

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-black/40 z-30 hidden lg:hidden"></div>

    <div class="min-h-full flex">
        <!-- Sidebar Navigation -->
        <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-[#E5E5E5] flex flex-col transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:shrink-0 transition-transform duration-200 ease-in-out">
            <!-- Brand Header -->
            <div class="h-16 px-5 border-b border-[#E5E5E5] flex items-center justify-between">
                <a href="<?= e(url($isSuperuser ? '/admin/dashboard' : '/dashboard')) ?>" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-md bg-[#171717] flex items-center justify-center">
                        <i data-lucide="link-2" class="w-4 h-4 text-[#E60012]"></i>
                    </div>
                    <div>
                        <span class="font-bold text-base tracking-tight text-[#171717]"><?= e($appName) ?></span>
                        <span class="block text-[10px] font-medium uppercase tracking-wider text-[#737373] -mt-0.5">
                            <?= $isSuperuser ? 'Superuser' : 'Workspace' ?>
                        </span>
                    </div>
                </a>
                <button type="button" id="sidebar-close-btn" class="lg:hidden p-1.5 text-[#737373] hover:text-[#171717] rounded-md">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Quick Primary Action -->
            <div class="p-4 border-b border-[#E5E5E5]">
                <a href="<?= e(url('/shortlinks/create')) ?>" class="btn-primary w-full">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Create Shortlink</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                <?php if ($isSuperuser): ?>
                    <div>
                        <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-[#737373]">Platform Control</p>
                        <div class="space-y-1">
                            <a href="<?= e(url('/admin/dashboard')) ?>" class="nav-item <?= $activeMenu === 'admin_dashboard' ? 'active' : '' ?>">
                                <i data-lucide="layout-grid" class="w-4 h-4"></i>
                                <span>Global Dashboard</span>
                            </a>
                            <a href="<?= e(url('/admin/shortlinks')) ?>" class="nav-item <?= $activeMenu === 'admin_shortlinks' ? 'active' : '' ?>">
                                <i data-lucide="globe" class="w-4 h-4"></i>
                                <span>All Shortlinks</span>
                            </a>
                            <a href="<?= e(url('/admin/analytics')) ?>" class="nav-item <?= $activeMenu === 'admin_analytics' ? 'active' : '' ?>">
                                <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                                <span>Global Analytics</span>
                            </a>
                            <a href="<?= e(url('/admin/users')) ?>" class="nav-item <?= $activeMenu === 'admin_users' ? 'active' : '' ?>">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                <span>User Management</span>
                            </a>
                            <a href="<?= e(url('/admin/logs')) ?>" class="nav-item <?= $activeMenu === 'admin_logs' ? 'active' : '' ?>">
                                <i data-lucide="scroll-text" class="w-4 h-4"></i>
                                <span>Activity Logs</span>
                            </a>
                            <a href="<?= e(url('/admin/settings')) ?>" class="nav-item <?= $activeMenu === 'admin_settings' ? 'active' : '' ?>">
                                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                                <span>App Settings</span>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-wider text-[#737373]">Personal Workspace</p>
                    <div class="space-y-1">
                        <a href="<?= e(url('/dashboard')) ?>" class="nav-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                            <i data-lucide="gauge" class="w-4 h-4"></i>
                            <span>My Dashboard</span>
                        </a>
                        <a href="<?= e(url('/shortlinks')) ?>" class="nav-item <?= $activeMenu === 'shortlinks' ? 'active' : '' ?>">
                            <i data-lucide="link" class="w-4 h-4"></i>
                            <span>My Shortlinks</span>
                        </a>
                        <a href="<?= e(url('/shortlinks/create')) ?>" class="nav-item <?= $activeMenu === 'shortlinks_create' ? 'active' : '' ?>">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span>Create Link</span>
                        </a>
                        <a href="<?= e(url('/analytics')) ?>" class="nav-item <?= $activeMenu === 'analytics' ? 'active' : '' ?>">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                            <span>My Analytics</span>
                        </a>
                        <a href="<?= e(url('/profile')) ?>" class="nav-item <?= $activeMenu === 'profile' ? 'active' : '' ?>">
                            <i data-lucide="user-cog" class="w-4 h-4"></i>
                            <span>Profile & Security</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User Footer -->
            <div class="mt-auto p-4 border-t border-[#E5E5E5] bg-[#F8F8F8] shrink-0">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-[#171717] truncate"><?= e($currentUser['name'] ?? 'User') ?></p>
                        <p class="text-[11px] text-[#737373] truncate"><?= e($currentUser['email'] ?? '') ?></p>
                    </div>
                    <form method="POST" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="p-2 text-[#737373] hover:text-[#E60012] hover:bg-white rounded-md border border-transparent hover:border-[#E5E5E5] transition-colors" title="Sign Out">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Header Bar -->
            <header class="h-16 bg-white border-b border-[#E5E5E5] px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button type="button" id="sidebar-open-btn" class="lg:hidden p-2 -ml-2 text-[#171717] hover:bg-[#F8F8F8] rounded-md">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                    <div class="flex items-center gap-2 text-xs text-[#737373]">
                        <span class="hidden sm:inline font-medium text-[#171717]">Domain:</span>
                        <code class="px-2 py-0.5 bg-[#F8F8F8] border border-[#E5E5E5] rounded text-[#171717] font-mono text-xs">
                            <?= e(rtrim(short_domain_display(), '/')) ?>
                        </code>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-medium border <?= $isSuperuser ? 'bg-[#FFF5F5] text-[#E60012] border-[#FECACA]' : 'bg-[#F8F8F8] text-[#171717] border-[#E5E5E5]' ?>">
                        <i data-lucide="<?= $isSuperuser ? 'shield-check' : 'user' ?>" class="w-3.5 h-3.5"></i>
                        <?= $isSuperuser ? 'Superuser' : 'Standard User' ?>
                    </span>
                    <a href="<?= e(url('/profile')) ?>" class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium text-[#737373] hover:text-[#171717]">
                        <i data-lucide="settings" class="w-3.5 h-3.5"></i>
                        <span>Account</span>
                    </a>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <?= $content ?>
            </main>

            <footer class="py-4 px-6 lg:px-8 border-t border-[#E5E5E5] bg-white text-xs text-[#737373] flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>&copy; <?= date('Y') ?> <?= e($appName) ?>. Multi-User URL Shortener Platform.</span>
                <span class="font-mono text-[11px]">PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?> &middot; PDO Secure</span>
            </footer>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2">
        <?php if ($successMsg = get_flash('success')): ?>
            <div data-auto-dismiss class="toast-enter flex items-center justify-between gap-3 bg-white border border-[#171717] px-4 py-3 rounded-md shadow-sm min-w-[280px] max-w-md text-sm text-[#171717]">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 shrink-0 text-[#171717]"></i>
                    <span class="font-medium"><?= e($successMsg) ?></span>
                </div>
                <button type="button" data-toast-close class="text-[#737373] hover:text-[#171717] p-0.5">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg = get_flash('error')): ?>
            <div data-auto-dismiss class="toast-enter flex items-center justify-between gap-3 bg-white border border-[#E60012] px-4 py-3 rounded-md shadow-sm min-w-[280px] max-w-md text-sm text-[#171717]">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-[#E60012]"></i>
                    <span class="font-medium"><?= e($errorMsg) ?></span>
                </div>
                <button type="button" data-toast-close class="text-[#737373] hover:text-[#171717] p-0.5">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reusable Modal 1: QR Code Generator & Downloader -->
    <div id="qr-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white border border-[#E5E5E5] rounded-lg max-w-sm w-full p-6 shadow-lg">
            <div class="flex items-center justify-between pb-3 border-b border-[#E5E5E5]">
                <div class="flex items-center gap-2">
                    <i data-lucide="qr-code" class="w-4 h-4 text-[#E60012]"></i>
                    <h3 id="qr-modal-title" class="text-sm font-semibold text-[#171717] truncate">Shortlink QR Code</h3>
                </div>
                <button type="button" data-modal-close="qr-modal" class="text-[#737373] hover:text-[#171717]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="py-5 text-center space-y-3">
                <div id="qr-canvas-container" class="flex justify-center"></div>
                <p id="qr-modal-url" class="text-xs font-mono text-[#737373] break-all px-2"></p>
            </div>
            <div class="flex items-center gap-2 pt-3 border-t border-[#E5E5E5]">
                <button type="button" id="qr-copy-btn" data-copy-text="" class="btn-secondary flex-1 text-xs">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copy URL</span>
                </button>
                <button type="button" id="qr-download-btn" class="btn-primary flex-1 text-xs">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>Download PNG</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Reusable Modal 2: Delete Confirmation -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white border border-[#E5E5E5] rounded-lg max-w-md w-full p-6 shadow-lg">
            <div class="flex items-start gap-3">
                <div class="w-9 h-9 rounded-md bg-[#FFF5F5] border border-[#FECACA] flex items-center justify-center shrink-0">
                    <i data-lucide="trash-2" class="w-4 h-4 text-[#E60012]"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-[#171717]">Confirm Deletion</h3>
                    <p class="mt-1 text-sm text-[#737373]">
                        Are you sure you want to delete <strong id="delete-modal-label" class="text-[#171717]">this item</strong>? The shortlink will stop redirecting immediately while preserving historical analytics.
                    </p>
                </div>
            </div>
            <form id="delete-modal-form" method="POST" action="" class="mt-6 flex items-center justify-end gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="return_to" id="delete-modal-return" value="">
                <button type="button" data-modal-close="delete-modal" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Delete</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Reusable Modal 3: Destination URL Preview -->
    <div id="preview-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white border border-[#E5E5E5] rounded-lg max-w-lg w-full p-6 shadow-lg">
            <div class="flex items-center justify-between pb-3 border-b border-[#E5E5E5]">
                <div class="flex items-center gap-2">
                    <i data-lucide="external-link" class="w-4 h-4 text-[#E60012]"></i>
                    <h3 id="preview-modal-title" class="text-sm font-semibold text-[#171717]">Destination URL Preview</h3>
                </div>
                <button type="button" data-modal-close="preview-modal" class="text-[#737373] hover:text-[#171717]">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="py-4 space-y-3 text-sm">
                <div>
                    <span class="text-xs font-medium text-[#737373] uppercase tracking-wider">Shortlink</span>
                    <p id="preview-modal-short" class="mt-1 font-mono text-xs bg-[#F8F8F8] border border-[#E5E5E5] p-2.5 rounded break-all text-[#171717]"></p>
                </div>
                <div>
                    <span class="text-xs font-medium text-[#737373] uppercase tracking-wider">Target Destination URL</span>
                    <p id="preview-modal-dest" class="mt-1 font-mono text-xs bg-[#F8F8F8] border border-[#E5E5E5] p-2.5 rounded break-all text-[#171717]"></p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E5E5E5]">
                <button type="button" data-modal-close="preview-modal" class="btn-secondary text-xs">Close</button>
                <a id="preview-modal-open" href="#" target="_blank" rel="noopener noreferrer" class="btn-primary text-xs">
                    <span>Open Destination</span>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

