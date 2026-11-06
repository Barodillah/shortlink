<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-[#171717] tracking-tight">Application Settings</h1>
            <span class="badge badge-expired">Superuser</span>
        </div>
        <p class="text-xs text-[#737373] mt-0.5">Configure platform identity, shortlink domain, default code generation, and redirect rules.</p>
    </div>

    <div class="bg-white border border-[#E5E5E5] rounded-lg p-6">
        <form method="POST" action="<?= e(url('/admin/settings')) ?>" data-loading-form class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="app_name" class="block text-xs font-semibold text-[#171717] mb-1.5">Application Name</label>
                    <input type="text" id="app_name" name="app_name" value="<?= e($settings['app_name'] ?? 'SLink') ?>" required class="input-monochrome">
                </div>
                <div>
                    <label for="app_tagline" class="block text-xs font-semibold text-[#171717] mb-1.5">Platform Tagline</label>
                    <input type="text" id="app_tagline" name="app_tagline" value="<?= e($settings['app_tagline'] ?? '') ?>" class="input-monochrome">
                </div>
            </div>

            <div class="pt-4 border-t border-[#E5E5E5]">
                <label for="shortlink_domain" class="block text-xs font-semibold text-[#171717] mb-1.5">
                    Shortlink Base Domain
                </label>
                <input
                    type="url"
                    id="shortlink_domain"
                    name="shortlink_domain"
                    value="<?= e($settings['shortlink_domain'] ?? '') ?>"
                    placeholder="https://s.domain.com (leave blank to auto-detect <?= e(base_url()) ?>)"
                    class="input-monochrome font-mono"
                >
                <p class="mt-1 text-[11px] text-[#737373]">
                    In production, set this to <code>https://s.domain.com</code>. When left empty on localhost, it automatically uses <code><?= e(base_url()) ?></code>.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-[#E5E5E5]">
                <div>
                    <label for="default_code_length" class="block text-xs font-semibold text-[#171717] mb-1.5">Default Random Code Length</label>
                    <select id="default_code_length" name="default_code_length" class="input-monochrome">
                        <?php foreach ([6, 7, 8] as $len): ?>
                            <option value="<?= $len ?>" <?= (int) ($settings['default_code_length'] ?? 6) === $len ? 'selected' : '' ?>>
                                <?= $len ?> characters
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="redirect_status_code" class="block text-xs font-semibold text-[#171717] mb-1.5">HTTP Redirect Status</label>
                    <select id="redirect_status_code" name="redirect_status_code" class="input-monochrome">
                        <option value="302" <?= (int) ($settings['redirect_status_code'] ?? 302) === 302 ? 'selected' : '' ?>>HTTP 302 (Temporary — Default)</option>
                        <option value="301" <?= (int) ($settings['redirect_status_code'] ?? 302) === 301 ? 'selected' : '' ?>>HTTP 301 (Permanent)</option>
                        <option value="307" <?= (int) ($settings['redirect_status_code'] ?? 302) === 307 ? 'selected' : '' ?>>HTTP 307 (Temporary Strict)</option>
                    </select>
                </div>

                <div>
                    <label for="max_links_per_user" class="block text-xs font-semibold text-[#171717] mb-1.5">Max Links per User</label>
                    <input
                        type="number"
                        id="max_links_per_user"
                        name="max_links_per_user"
                        min="0"
                        value="<?= e($settings['max_links_per_user'] ?? '0') ?>"
                        class="input-monochrome"
                    >
                    <p class="mt-1 text-[11px] text-[#737373]">Set to <code>0</code> for unlimited.</p>
                </div>
            </div>

            <div class="pt-4 border-t border-[#E5E5E5]">
                <label class="inline-flex items-center gap-2.5 cursor-pointer">
                    <input
                        type="checkbox"
                        name="allow_custom_alias"
                        value="1"
                        <?= ($settings['allow_custom_alias'] ?? '1') === '1' ? 'checked' : '' ?>
                        class="w-4 h-4 accent-[#E60012] rounded border-[#E5E5E5]"
                    >
                    <span class="text-xs font-medium text-[#171717]">Allow standard users to specify custom shortlink aliases</span>
                </label>
            </div>

            <div class="pt-4 border-t border-[#E5E5E5] flex justify-end">
                <button type="submit" data-loading-text="Saving..." class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Save Configuration</span>
                </button>
            </div>
        </form>
    </div>
</div>

