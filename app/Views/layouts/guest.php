<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="<?= e(app_setting('app_tagline', 'Centralized Multi-User URL Shortener & Analytics Platform')) ?>">
    <title><?= e($pageTitle ?? app_setting('app_name', 'SLink')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-full bg-[#F8F8F8] text-[#171717] flex flex-col justify-between">
    <!-- Toast Container -->
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

    <main class="flex-1 flex items-center justify-center p-4 sm:p-6">
        <?= $content ?>
    </main>

    <footer class="py-5 text-center text-xs text-[#737373] border-t border-[#E5E5E5] bg-white">
        <span>&copy; <?= date('Y') ?> <?= e(app_setting('app_name', 'SLink')) ?>. Enterprise URL Shortener Platform.</span>
    </footer>

    <script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

