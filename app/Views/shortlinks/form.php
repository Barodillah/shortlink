<?php
use App\Core\Auth;

$isEdit = ($mode ?? 'create') === 'edit' && !empty($shortlink);
$formAction = $isEdit
    ? url('/shortlinks/' . (int) $shortlink['id'] . '/update')
    : url('/shortlinks');

$destVal   = $isEdit ? (string) $shortlink['destination_url'] : old('destination_url');
$aliasVal  = $isEdit ? (string) $shortlink['short_code'] : old('custom_alias');
$titleVal  = $isEdit ? (string) ($shortlink['title'] ?? '') : old('title');
$descVal   = $isEdit ? (string) ($shortlink['description'] ?? '') : old('description');
$statusVal = $isEdit ? (string) ($shortlink['status'] ?? 'active') : old('status', 'active');
$maxClicks = $isEdit ? (string) ($shortlink['max_clicks'] ?? '') : old('max_clicks');

$expiresVal = '';
if ($isEdit && !empty($shortlink['expires_at'])) {
    $expiresVal = date('Y-m-d\TH:i', strtotime((string) $shortlink['expires_at']));
} elseif (!$isEdit) {
    $expiresVal = old('expires_at');
}
?>
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-[#171717] tracking-tight">
                <?= $isEdit ? 'Edit Shortlink /' . e($shortlink['short_code']) : 'Create New Shortlink' ?>
            </h1>
            <p class="text-xs text-[#737373] mt-0.5">
                <?= $isEdit
                    ? 'Modify destination URL, custom alias, expiration schedule, or click limits.'
                    : 'Configure destination URL, optional custom alias, and access controls.' ?>
            </p>
        </div>
        <a href="<?= e(url('/shortlinks')) ?>" class="btn-secondary text-xs">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Links</span>
        </a>
    </div>

    <div class="bg-white border border-[#E5E5E5] rounded-lg p-6">
        <form method="POST" action="<?= e($formAction) ?>" data-loading-form class="space-y-5">
            <?= csrf_field() ?>
            <?php if ($isEdit && Auth::isSuperuser() && (int) $shortlink['user_id'] !== (int) Auth::id()): ?>
                <input type="hidden" name="return_to" value="admin">
            <?php endif; ?>

            <!-- Destination URL (Required) -->
            <div>
                <label for="destination_url" class="block text-xs font-semibold text-[#171717] mb-1.5">
                    Destination URL <span class="text-[#E60012]">*</span>
                </label>
                <input
                    type="url"
                    id="destination_url"
                    name="destination_url"
                    value="<?= e($destVal) ?>"
                    required
                    placeholder="https://example.com/products/product-a?utm_source=newsletter"
                    class="input-monochrome"
                >
                <p class="mt-1 text-[11px] text-[#737373]">Must start with <code>http://</code> or <code>https://</code>. UTM parameters in the destination or shortlink will be tracked automatically.</p>
            </div>

            <!-- Custom Alias & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <div class="sm:col-span-8">
                    <label for="custom_alias" class="block text-xs font-semibold text-[#171717] mb-1.5">
                        Custom Alias <?= $isEdit ? '<span class="text-[#E60012]">*</span>' : '<span class="text-[#737373] font-normal">(Optional — auto-generated if blank)</span>' ?>
                    </label>
                    <div class="flex items-stretch">
                        <span class="inline-flex items-center px-3 bg-[#F8F8F8] border border-r-0 border-[#E5E5E5] rounded-l-md text-xs font-mono text-[#737373]">
                            <?= e(short_domain_display()) ?>
                        </span>
                        <input
                            type="text"
                            id="custom_alias"
                            name="custom_alias"
                            value="<?= e($aliasVal) ?>"
                            <?= $isEdit ? 'required' : '' ?>
                            pattern="[a-zA-Z0-9_-]{2,64}"
                            placeholder="promo2026"
                            class="input-monochrome rounded-l-none font-mono"
                        >
                    </div>
                    <p class="mt-1 text-[11px] text-[#737373]">Case-sensitive alphanumeric characters, hyphens (<code>-</code>), and underscores (<code>_</code>).</p>
                </div>

                <div class="sm:col-span-4">
                    <label for="status" class="block text-xs font-semibold text-[#171717] mb-1.5">Status</label>
                    <select id="status" name="status" class="input-monochrome">
                        <option value="active" <?= $statusVal === 'active' ? 'selected' : '' ?>>Active (Redirecting)</option>
                        <option value="inactive" <?= $statusVal === 'inactive' ? 'selected' : '' ?>>Inactive (Paused)</option>
                    </select>
                </div>
            </div>

            <!-- Title & Description -->
            <div class="grid grid-cols-1 gap-4 pt-2 border-t border-[#E5E5E5]">
                <div>
                    <label for="title" class="block text-xs font-semibold text-[#171717] mb-1.5">
                        Title <span class="text-[#737373] font-normal">(Optional)</span>
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?= e($titleVal) ?>"
                        maxlength="255"
                        placeholder="e.g., Q4 Product Launch Campaign"
                        class="input-monochrome"
                    >
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold text-[#171717] mb-1.5">
                        Description / Internal Notes <span class="text-[#737373] font-normal">(Optional)</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        maxlength="1000"
                        placeholder="Add campaign context or notes for your team..."
                        class="input-monochrome"
                    ><?= e($descVal) ?></textarea>
                </div>
            </div>

            <!-- Expiration Date & Maximum Clicks -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-[#E5E5E5]">
                <div>
                    <label for="expires_at" class="block text-xs font-semibold text-[#171717] mb-1.5">
                        Expiration Date <span class="text-[#737373] font-normal">(Optional)</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="expires_at"
                        name="expires_at"
                        value="<?= e($expiresVal) ?>"
                        class="input-monochrome"
                    >
                    <p class="mt-1 text-[11px] text-[#737373]">Leave empty if the shortlink should never expire by date.</p>
                </div>

                <div>
                    <label for="max_clicks" class="block text-xs font-semibold text-[#171717] mb-1.5">
                        Maximum Clicks Limit <span class="text-[#737373] font-normal">(Optional)</span>
                    </label>
                    <input
                        type="number"
                        id="max_clicks"
                        name="max_clicks"
                        min="1"
                        step="1"
                        value="<?= e($maxClicks) ?>"
                        placeholder="Unlimited"
                        class="input-monochrome"
                    >
                    <p class="mt-1 text-[11px] text-[#737373]">Automatically stops redirecting once total clicks reach this number.</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-[#E5E5E5] flex items-center justify-end gap-3">
                <a href="<?= e(url('/shortlinks')) ?>" class="btn-secondary">Cancel</a>
                <button type="submit" data-loading-text="Saving..." class="btn-primary">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span><?= $isEdit ? 'Save Changes' : 'Create Shortlink' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

