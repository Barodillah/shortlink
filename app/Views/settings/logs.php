<?php
$items      = $pagination['items'] ?? [];
$total      = (int) ($pagination['total'] ?? 0);
$page       = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
?>
<div class="space-y-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-[#171717] tracking-tight">System & User Activity Logs</h1>
            <span class="badge badge-expired">Superuser</span>
        </div>
        <p class="text-xs text-[#737373] mt-0.5">Audit trail of authentication events, shortlink modifications, security blocks, and system changes.</p>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
        <form method="GET" action="<?= e(url('/admin/logs')) ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-5">
                <input
                    type="text"
                    name="q"
                    value="<?= e($filters['search'] ?? '') ?>"
                    placeholder="Search action, description, IP, or user..."
                    class="input-monochrome"
                >
            </div>
            <div class="sm:col-span-3">
                <select name="user_id" class="input-monochrome">
                    <option value="">All Users</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2">
                <select name="action" class="input-monochrome">
                    <option value="">All Categories</option>
                    <option value="auth." <?= ($filters['action'] ?? '') === 'auth.' ? 'selected' : '' ?>>Authentication</option>
                    <option value="shortlink." <?= ($filters['action'] ?? '') === 'shortlink.' ? 'selected' : '' ?>>Shortlinks</option>
                    <option value="user." <?= ($filters['action'] ?? '') === 'user.' ? 'selected' : '' ?>>User Management</option>
                    <option value="security." <?= ($filters['action'] ?? '') === 'security.' ? 'selected' : '' ?>>Security Alerts</option>
                    <option value="settings." <?= ($filters['action'] ?? '') === 'settings.' ? 'selected' : '' ?>>Settings</option>
                </select>
            </div>
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="btn-dark flex-1 text-xs py-2">Filter</button>
                <a href="<?= e(url('/admin/logs')) ?>" class="btn-secondary text-xs py-2 px-3">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#E5E5E5] bg-[#F8F8F8] text-xs text-[#737373]">
            Showing <strong class="text-[#171717]"><?= count($items) ?></strong> of <strong class="text-[#171717]"><?= format_number($total) ?></strong> events
        </div>
        <div class="table-container">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#E5E5E5] text-[11px] font-semibold uppercase tracking-wider text-[#737373] bg-white">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">Actor</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5] text-xs">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[#737373]">No log entries match your filter.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $log): ?>
                            <?php $isSecurity = str_starts_with((string) $log['action'], 'security.') || str_contains((string) $log['action'], 'failed'); ?>
                            <tr class="hover:bg-[#F8F8F8]">
                                <td class="py-3 px-4 whitespace-nowrap text-[#737373]">
                                    <span class="text-[#171717] block"><?= e(date('d M Y H:i:s', strtotime((string) $log['created_at']))) ?></span>
                                    <span class="text-[11px]"><?= e(time_ago($log['created_at'])) ?></span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <?php if (!empty($log['user_id'])): ?>
                                        <a href="<?= e(url('/admin/users/' . $log['user_id'])) ?>" class="font-medium text-[#171717] hover:text-[#E60012]">
                                            <?= e($log['user_name'] ?? 'User #' . $log['user_id']) ?>
                                        </a>
                                        <span class="block text-[11px] text-[#737373]"><?= e($log['user_email'] ?? '') ?></span>
                                    <?php else: ?>
                                        <span class="text-[#737373]">System / Unauthenticated</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <code class="px-2 py-0.5 rounded border font-mono text-[11px] <?= $isSecurity ? 'bg-[#FFF5F5] text-[#E60012] border-[#FECACA]' : 'bg-[#F8F8F8] text-[#171717] border-[#E5E5E5]' ?>">
                                        <?= e($log['action']) ?>
                                    </code>
                                </td>
                                <td class="py-3 px-4 text-[#171717]"><?= e($log['description']) ?></td>
                                <td class="py-3 px-4 whitespace-nowrap font-mono text-[#737373]"><?= e($log['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $queryBase = $_GET;
            unset($queryBase['page']);
            ?>
            <div class="px-5 py-3.5 border-t border-[#E5E5E5] flex items-center justify-between text-xs">
                <span class="text-[#737373]">Page <strong class="text-[#171717]"><?= $page ?></strong> of <strong class="text-[#171717]"><?= $totalPages ?></strong></span>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(url('/admin/logs?' . http_build_query(array_merge($queryBase, ['page' => $page - 1])))) ?>" class="btn-secondary py-1 px-2.5 text-xs">Prev</a>
                    <?php endif; ?>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <a href="<?= e(url('/admin/logs?' . http_build_query(array_merge($queryBase, ['page' => $p])))) ?>"
                           class="px-2.5 py-1 rounded border text-xs font-medium <?= $p === $page ? 'bg-[#E60012] text-white border-[#E60012]' : 'bg-white text-[#171717] border-[#E5E5E5]' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(url('/admin/logs?' . http_build_query(array_merge($queryBase, ['page' => $page + 1])))) ?>" class="btn-secondary py-1 px-2.5 text-xs">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

