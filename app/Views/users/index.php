<?php
use App\Core\Auth;

$items      = $pagination['items'] ?? [];
$total      = (int) ($pagination['total'] ?? 0);
$page       = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-[#171717] tracking-tight">User Management</h1>
                <span class="badge badge-expired">Superuser</span>
            </div>
            <p class="text-xs text-[#737373] mt-0.5">Create, manage, activate/deactivate, reset passwords, and inspect platform users.</p>
        </div>
        <a href="<?= e(url('/admin/users/create')) ?>" class="btn-primary text-xs">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            <span>Create New User</span>
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
        <form method="GET" action="<?= e(url('/admin/users')) ?>" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-5">
                <input
                    type="text"
                    name="q"
                    value="<?= e($filters['search'] ?? '') ?>"
                    placeholder="Search by user name or email..."
                    class="input-monochrome"
                >
            </div>
            <div class="sm:col-span-2">
                <select name="role" class="input-monochrome">
                    <option value="">All Roles</option>
                    <option value="superuser" <?= ($filters['role'] ?? '') === 'superuser' ? 'selected' : '' ?>>Superuser</option>
                    <option value="user" <?= ($filters['role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <select name="status" class="input-monochrome">
                    <option value="">All Statuses</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-center gap-2">
                <button type="submit" class="btn-dark flex-1 text-xs py-2">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    <span>Filter</span>
                </button>
                <a href="<?= e(url('/admin/users')) ?>" class="btn-secondary text-xs py-2 px-3">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-[#E5E5E5] bg-[#F8F8F8] text-xs text-[#737373]">
            Showing <strong class="text-[#171717]"><?= count($items) ?></strong> of <strong class="text-[#171717]"><?= format_number($total) ?></strong> users
        </div>

        <div class="table-container">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#E5E5E5] text-[11px] font-semibold uppercase tracking-wider text-[#737373] bg-white">
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Shortlinks</th>
                        <th class="py-3 px-4">Total Clicks</th>
                        <th class="py-3 px-4">Last Login</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5] text-xs">
                    <?php foreach ($items as $u): ?>
                        <?php $isSelf = (int) $u['id'] === (int) Auth::id(); ?>
                        <tr class="hover:bg-[#F8F8F8] transition-colors">
                            <td class="py-3.5 px-4">
                                <a href="<?= e(url('/admin/users/' . $u['id'])) ?>" class="font-semibold text-[#171717] hover:text-[#E60012] block">
                                    <?= e($u['name']) ?>
                                    <?php if ($isSelf): ?>
                                        <span class="text-[10px] font-mono text-[#737373]">(You)</span>
                                    <?php endif; ?>
                                </a>
                                <span class="text-[#737373]"><?= e($u['email']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <?php if ($u['role'] === 'superuser'): ?>
                                    <span class="badge badge-expired">Superuser</span>
                                <?php else: ?>
                                    <span class="badge badge-active">User</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <?php if ($u['status'] === 'active'): ?>
                                    <span class="badge badge-active">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#171717]"></span>
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#737373]"></span>
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-[#171717]"><?= format_number((int) $u['total_links']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-[#171717]"><?= format_number((int) $u['total_clicks']) ?></span>
                                <span class="block text-[11px] text-[#737373]"><?= format_number((int) $u['human_clicks']) ?> human</span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-[#737373]">
                                <?= e(time_ago($u['last_login_at'])) ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="<?= e(url('/admin/users/' . $u['id'])) ?>" class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white" title="View User Detail & Stats">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <a href="<?= e(url('/admin/users/' . $u['id'] . '/edit')) ?>" class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white" title="Edit User">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <?php if (!$isSelf): ?>
                                        <form method="POST" action="<?= e(url('/admin/users/' . $u['id'] . '/toggle')) ?>" class="inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white" title="<?= $u['status'] === 'active' ? 'Deactivate User' : 'Activate User' ?>">
                                                <i data-lucide="<?= $u['status'] === 'active' ? 'user-x' : 'user-check' ?>" class="w-3.5 h-3.5"></i>
                                            </button>
                                        </form>
                                        <button type="button"
                                                data-delete-action="<?= e(url('/admin/users/' . $u['id'] . '/delete')) ?>"
                                                data-delete-label="User <?= e($u['name']) ?> (<?= e($u['email']) ?>)"
                                                class="p-1.5 text-[#737373] hover:text-[#E60012] border border-[#E5E5E5] rounded bg-white"
                                                title="Safe Delete User">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

