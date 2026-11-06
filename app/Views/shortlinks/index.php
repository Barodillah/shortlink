<?php
use App\Models\Shortlink;

$items      = $pagination['items'] ?? [];
$total      = (int) ($pagination['total'] ?? 0);
$page       = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$baseRoute  = !empty($isGlobal) ? '/admin/shortlinks' : '/shortlinks';
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-[#171717] tracking-tight">
                <?= !empty($isGlobal) ? 'All Platform Shortlinks' : 'My Shortlinks' ?>
            </h1>
            <p class="text-xs text-[#737373] mt-0.5">
                <?= !empty($isGlobal)
                    ? 'Inspect, edit, toggle, or remove shortlinks across all platform users.'
                    : 'Manage, preview, generate QR codes, and monitor your shortlinks.' ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(url('/shortlinks/create')) ?>" class="btn-primary text-xs">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create Shortlink</span>
            </a>
        </div>
    </div>

    <!-- Search, Filter & Sort Toolbar -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
        <form method="GET" action="<?= e(url($baseRoute)) ?>" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            <div class="<?= !empty($isGlobal) ? 'lg:col-span-4' : 'lg:col-span-5' ?>">
                <div class="relative">
                    <input
                        type="text"
                        name="q"
                        value="<?= e($filters['search'] ?? '') ?>"
                        placeholder="Search alias, title, destination URL<?= !empty($isGlobal) ? ', or user' : '' ?>..."
                        class="input-monochrome"
                    >
                </div>
            </div>

            <?php if (!empty($isGlobal) && !empty($usersList)): ?>
                <div class="lg:col-span-2">
                    <select name="user_id" class="input-monochrome">
                        <option value="">All Users</option>
                        <?php foreach ($usersList as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
                                <?= e($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="lg:col-span-2">
                <select name="status" class="input-monochrome">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="expired" <?= ($filters['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired / Limit Reached</option>
                </select>
            </div>

            <div class="lg:col-span-2">
                <select name="sort" class="input-monochrome">
                    <option value="newest" <?= ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="clicks_desc" <?= ($filters['sort'] ?? '') === 'clicks_desc' ? 'selected' : '' ?>>Most Clicks</option>
                    <option value="clicks_asc" <?= ($filters['sort'] ?? '') === 'clicks_asc' ? 'selected' : '' ?>>Least Clicks</option>
                </select>
            </div>

            <div class="<?= !empty($isGlobal) ? 'lg:col-span-2' : 'lg:col-span-3' ?> flex items-center gap-2">
                <button type="submit" class="btn-dark flex-1 text-xs py-2">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="<?= e(url($baseRoute)) ?>" class="btn-secondary text-xs py-2 px-3" title="Reset Filters">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Shortlinks Table -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#E5E5E5] flex items-center justify-between bg-[#F8F8F8]">
            <span class="text-xs font-medium text-[#737373]">
                Showing <strong class="text-[#171717]"><?= count($items) ?></strong> of <strong class="text-[#171717]"><?= format_number($total) ?></strong> shortlinks
            </span>
        </div>

        <?php if (empty($items)): ?>
            <div class="p-12 text-center">
                <div class="w-11 h-11 rounded-md bg-[#F8F8F8] border border-[#E5E5E5] flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="link-2-off" class="w-5 h-5 text-[#737373]"></i>
                </div>
                <h3 class="text-sm font-semibold text-[#171717]">No shortlinks found</h3>
                <p class="text-xs text-[#737373] mt-1 max-w-sm mx-auto">
                    No shortlinks matched your current filter criteria, or you haven't created any links yet.
                </p>
                <div class="mt-4">
                    <a href="<?= e(url('/shortlinks/create')) ?>" class="btn-primary text-xs">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Create First Shortlink</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E5E5E5] text-[11px] font-semibold uppercase tracking-wider text-[#737373] bg-white">
                            <th class="py-3 px-4">Shortlink</th>
                            <th class="py-3 px-4">Destination</th>
                            <?php if (!empty($isGlobal)): ?>
                                <th class="py-3 px-4">Owner</th>
                            <?php endif; ?>
                            <th class="py-3 px-4">Clicks</th>
                            <th class="py-3 px-4">Status / Limits</th>
                            <th class="py-3 px-4">Created</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E5E5] text-xs">
                        <?php foreach ($items as $link): ?>
                            <?php
                            $fullShortUrl = short_url($link['short_code']);
                            $effectiveState = Shortlink::computeEffectiveState($link);
                            ?>
                            <tr class="hover:bg-[#F8F8F8] transition-colors">
                                <!-- Shortlink & Title -->
                                <td class="py-3.5 px-4 align-top">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= e($fullShortUrl) ?>" target="_blank" rel="noopener noreferrer" class="font-mono font-semibold text-[#E60012] hover:underline">
                                            /<?= e($link['short_code']) ?>
                                        </a>
                                        <button type="button" data-copy-text="<?= e($fullShortUrl) ?>" class="text-[#737373] hover:text-[#171717]" title="Copy Shortlink">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                    <?php if (!empty($link['title'])): ?>
                                        <p class="text-xs font-medium text-[#171717] mt-0.5 truncate max-w-[200px]"><?= e($link['title']) ?></p>
                                    <?php endif; ?>
                                </td>

                                <!-- Destination URL -->
                                <td class="py-3.5 px-4 align-top max-w-xs">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs text-[#737373] truncate max-w-[240px]" title="<?= e($link['destination_url']) ?>">
                                            <?= e($link['destination_url']) ?>
                                        </p>
                                        <button type="button"
                                                data-preview-url="<?= e($link['destination_url']) ?>"
                                                data-preview-short="<?= e($fullShortUrl) ?>"
                                                data-preview-title="<?= e($link['title'] ?: '/' . $link['short_code']) ?>"
                                                class="text-[#737373] hover:text-[#171717] shrink-0"
                                                title="Preview Destination URL">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>

                                <!-- Owner (Superuser only) -->
                                <?php if (!empty($isGlobal)): ?>
                                    <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                        <a href="<?= e(url('/admin/users/' . $link['user_id'])) ?>" class="font-medium text-[#171717] hover:text-[#E60012]">
                                            <?= e($link['owner_name']) ?>
                                        </a>
                                        <span class="block text-[11px] text-[#737373]"><?= e($link['owner_email']) ?></span>
                                    </td>
                                <?php endif; ?>

                                <!-- Clicks -->
                                <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                    <a href="<?= e(url('/shortlinks/' . $link['id'] . '/analytics')) ?>" class="group inline-block">
                                        <span class="font-bold text-sm text-[#171717] group-hover:text-[#E60012]">
                                            <?= format_number((int) $link['total_clicks']) ?>
                                        </span>
                                        <span class="block text-[11px] text-[#737373]">
                                            <?= format_number((int) $link['human_clicks']) ?> human &middot; <?= format_number((int) $link['bot_clicks']) ?> bot
                                        </span>
                                    </a>
                                </td>

                                <!-- Status & Expiration/Max Clicks -->
                                <td class="py-3.5 px-4 align-top whitespace-nowrap">
                                    <?php if ($effectiveState === 'active'): ?>
                                        <span class="badge badge-active">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#171717]"></span>
                                            Active
                                        </span>
                                    <?php elseif ($effectiveState === 'inactive'): ?>
                                        <span class="badge badge-inactive">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#737373]"></span>
                                            Inactive
                                        </span>
                                    <?php elseif ($effectiveState === 'expired'): ?>
                                        <span class="badge badge-expired">Expired</span>
                                    <?php else: ?>
                                        <span class="badge badge-expired">Max Clicks Reached</span>
                                    <?php endif; ?>

                                    <div class="mt-1 text-[11px] text-[#737373] space-y-0.5">
                                        <?php if (!empty($link['expires_at'])): ?>
                                            <div>Exp: <?= e(date('d M Y H:i', strtotime((string) $link['expires_at']))) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($link['max_clicks'])): ?>
                                            <div>Cap: <?= format_number((int) $link['total_clicks']) ?> / <?= format_number((int) $link['max_clicks']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Created At -->
                                <td class="py-3.5 px-4 align-top whitespace-nowrap text-[#737373]">
                                    <span class="block text-[#171717]"><?= e(date('d M Y', strtotime((string) $link['created_at']))) ?></span>
                                    <span class="block text-[11px]"><?= e(time_ago($link['created_at'])) ?></span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 align-top whitespace-nowrap text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button"
                                                data-copy-text="<?= e($fullShortUrl) ?>"
                                                class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                                title="Copy Link">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <button type="button"
                                                data-qr-url="<?= e($fullShortUrl) ?>"
                                                data-qr-code="<?= e($link['short_code']) ?>"
                                                data-qr-title="<?= e($link['title'] ?: '/' . $link['short_code']) ?>"
                                                class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                                title="QR Code">
                                            <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                        </button>

                                        <a href="<?= e(url('/shortlinks/' . $link['id'] . '/analytics')) ?>"
                                           class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                           title="Analytics">
                                            <i data-lucide="bar-chart-2" class="w-3.5 h-3.5"></i>
                                        </a>

                                        <form method="POST" action="<?= e(url('/shortlinks/' . $link['id'] . '/toggle')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit"
                                                    class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                                    title="<?= $link['status'] === 'active' ? 'Pause / Deactivate' : 'Activate' ?>">
                                                <i data-lucide="<?= $link['status'] === 'active' ? 'pause' : 'play' ?>" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>

                                        <a href="<?= e(url('/shortlinks/' . $link['id'] . '/edit')) ?>"
                                           class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                           title="Edit">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        </a>

                                        <button type="button"
                                                data-delete-action="<?= e(url('/shortlinks/' . $link['id'] . '/delete')) ?>"
                                                data-delete-label="/<?= e($link['short_code']) ?>"
                                                data-return-to="<?= !empty($isGlobal) ? 'admin' : 'user' ?>"
                                                class="p-1.5 text-[#737373] hover:text-[#E60012] border border-[#E5E5E5] rounded bg-white"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
                <?php
                $queryBase = $_GET;
                unset($queryBase['page']);
                ?>
                <div class="px-5 py-3.5 border-t border-[#E5E5E5] flex items-center justify-between text-xs">
                    <span class="text-[#737373]">Page <strong class="text-[#171717]"><?= $page ?></strong> of <strong class="text-[#171717]"><?= $totalPages ?></strong></span>
                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="<?= e(url($baseRoute . '?' . http_build_query(array_merge($queryBase, ['page' => $page - 1])))) ?>" class="btn-secondary py-1 px-2.5 text-xs">Prev</a>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                            <a href="<?= e(url($baseRoute . '?' . http_build_query(array_merge($queryBase, ['page' => $p])))) ?>"
                               class="px-2.5 py-1 rounded border text-xs font-medium <?= $p === $page ? 'bg-[#E60012] text-white border-[#E60012]' : 'bg-white text-[#171717] border-[#E5E5E5] hover:bg-[#F8F8F8]' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= e(url($baseRoute . '?' . http_build_query(array_merge($queryBase, ['page' => $page + 1])))) ?>" class="btn-secondary py-1 px-2.5 text-xs">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

