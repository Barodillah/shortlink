<?php
$isEdit = ($mode ?? 'create') === 'edit' && !empty($targetUser);
$actionUrl = $isEdit ? url('/admin/users/' . (int) $targetUser['id'] . '/update') : url('/admin/users');

$nameVal   = $isEdit ? (string) $targetUser['name'] : old('name');
$emailVal  = $isEdit ? (string) $targetUser['email'] : old('email');
$roleVal   = $isEdit ? (string) $targetUser['role'] : old('role', 'user');
$statusVal = $isEdit ? (string) $targetUser['status'] : old('status', 'active');
?>
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-[#171717] tracking-tight">
                <?= $isEdit ? 'Edit User: ' . e($targetUser['name']) : 'Create New User' ?>
            </h1>
            <p class="text-xs text-[#737373] mt-0.5">
                <?= $isEdit ? 'Update user profile, role permissions, account status, or password.' : 'Provision a new platform user or superuser account.' ?>
            </p>
        </div>
        <a href="<?= e(url('/admin/users')) ?>" class="btn-secondary text-xs">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Back to Users</span>
        </a>
    </div>

    <div class="bg-white border border-[#E5E5E5] rounded-lg p-6">
        <form method="POST" action="<?= e($actionUrl) ?>" data-loading-form class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label for="name" class="block text-xs font-semibold text-[#171717] mb-1.5">Full Name <span class="text-[#E60012]">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($nameVal) ?>" required maxlength="120" placeholder="Nadia Pratama" class="input-monochrome">
            </div>

            <div>
                <label for="email" class="block text-xs font-semibold text-[#171717] mb-1.5">Email Address <span class="text-[#E60012]">*</span></label>
                <input type="email" id="email" name="email" value="<?= e($emailVal) ?>" required maxlength="190" placeholder="nadia@s.domain.com" class="input-monochrome">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-xs font-semibold text-[#171717] mb-1.5">Platform Role</label>
                    <select id="role" name="role" class="input-monochrome">
                        <option value="user" <?= $roleVal === 'user' ? 'selected' : '' ?>>User (Personal Workspace Only)</option>
                        <option value="superuser" <?= $roleVal === 'superuser' ? 'selected' : '' ?>>Superuser (Full Platform Control)</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-semibold text-[#171717] mb-1.5">Account Status</label>
                    <select id="status" name="status" class="input-monochrome">
                        <option value="active" <?= $statusVal === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusVal === 'inactive' ? 'selected' : '' ?>>Inactive (Login Disabled)</option>
                    </select>
                </div>
            </div>

            <div class="pt-3 border-t border-[#E5E5E5]">
                <label for="password" class="block text-xs font-semibold text-[#171717] mb-1.5">
                    <?= $isEdit ? 'Reset Password (Optional)' : 'Initial Password *' ?>
                </label>
                <input
                    type="password"
                    id="password"
                    name="<?= $isEdit ? 'new_password' : 'password' ?>"
                    <?= $isEdit ? '' : 'required' ?>
                    minlength="8"
                    placeholder="<?= $isEdit ? 'Leave blank to keep existing password' : 'Minimum 8 characters' ?>"
                    class="input-monochrome"
                >
                <p class="mt-1 text-[11px] text-[#737373]">Passwords are securely hashed using bcrypt (<code>password_hash</code>).</p>
            </div>

            <div class="pt-4 border-t border-[#E5E5E5] flex items-center justify-end gap-3">
                <a href="<?= e(url('/admin/users')) ?>" class="btn-secondary">Cancel</a>
                <button type="submit" data-loading-text="Saving..." class="btn-primary">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span><?= $isEdit ? 'Update User' : 'Create User' ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

