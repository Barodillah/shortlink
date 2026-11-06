<div class="w-full max-w-md">
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-6 sm:p-8 shadow-sm">
        <!-- Brand Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-md bg-[#171717] flex items-center justify-center shrink-0">
                <i data-lucide="link-2" class="w-5 h-5 text-[#E60012]"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight text-[#171717]"><?= e(app_setting('app_name', 'SLink')) ?></h1>
                <p class="text-xs text-[#737373]">Multi-User URL Shortener Platform</p>
            </div>
        </div>

        <div class="mb-6">
            <h2 class="text-base font-semibold text-[#171717]">Sign in to your account</h2>
            <p class="text-xs text-[#737373] mt-1">Enter your credentials to manage and analyze your shortlinks.</p>
        </div>

        <form method="POST" action="<?= e(url('/login')) ?>" data-loading-form class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="email" class="block text-xs font-medium text-[#171717] mb-1.5">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e(old('email')) ?>"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="name@s.domain.com"
                    class="input-monochrome"
                >
            </div>

            <div>
                <label for="password" class="block text-xs font-medium text-[#171717] mb-1.5">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••••••"
                    class="input-monochrome"
                >
            </div>

            <div class="pt-2">
                <button type="submit" data-loading-text="Signing in..." class="btn-primary w-full py-2.5">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Sign In</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-[#E5E5E5] text-xs text-[#737373] flex items-start gap-2">
            <i data-lucide="shield-alert" class="w-4 h-4 text-[#737373] shrink-0 mt-0.5"></i>
            <span>Public registration is disabled. Contact your platform Superuser to request an account.</span>
        </div>
    </div>
</div>

