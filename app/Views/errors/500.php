<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>500 — System Error</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/favicon.svg')) ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-full bg-[#F8F8F8] text-[#171717] flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white border border-[#E5E5E5] rounded-lg p-8 text-center">
        <div class="w-12 h-12 rounded-md bg-[#FFF5F5] border border-[#FECACA] flex items-center justify-center mx-auto mb-4">
            <i data-lucide="alert-triangle" class="w-6 h-6 text-[#E60012]"></i>
        </div>
        <span class="text-xs font-mono uppercase tracking-widest text-[#E60012] font-semibold">Error 500</span>
        <h1 class="mt-1 text-xl font-bold text-[#171717]"><?= e($title ?? 'System Error') ?></h1>
        <p class="mt-2 text-sm text-[#737373]">
            <?= e($message ?? 'An unexpected error occurred. The incident has been logged.') ?>
        </p>
        <div class="mt-6">
            <a href="<?= e(url('/')) ?>" class="btn-dark w-full">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Return Home</span>
            </a>
        </div>
    </div>
    <script>if (window.lucide) window.lucide.createIcons();</script>
</body>
</html>

