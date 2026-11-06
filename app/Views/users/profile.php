<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-xl font-bold text-[#171717] tracking-tight">Profile & Security</h1>
        <p class="text-xs text-[#737373] mt-0.5">Manage your personal account details and update your password. Your existing shortlinks will remain active when you update your profile.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Update Profile Card -->
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-6">
            <div class="pb-4 mb-4 border-b border-[#E5E5E5]">
                <h2 class="text-sm font-semibold text-[#171717]">Personal Information</h2>
                <p class="text-xs text-[#737373]">Update your display name and email address.</p>
            </div>

            <form method="POST" action="<?= e(url('/profile/update')) ?>" data-loading-form class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="name" class="block text-xs font-semibold text-[#171717] mb-1.5">Full Name</label>
                    <input type="text" id="name" name="name" value="<?= e($user['name']) ?>" required class="input-monochrome">
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-[#171717] mb-1.5">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required class="input-monochrome">
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="p-3 bg-[#F8F8F8] border border-[#E5E5E5] rounded">
                        <span class="block text-[11px] text-[#737373]">Account Role</span>
                        <span class="text-xs font-semibold text-[#171717] uppercase"><?= e($user['role']) ?></span>
                    </div>
                    <div class="p-3 bg-[#F8F8F8] border border-[#E5E5E5] rounded">
                        <span class="block text-[11px] text-[#737373]">Owned Shortlinks</span>
                        <span class="text-xs font-semibold text-[#171717]"><?= format_number($stats['total_shortlinks']) ?> links</span>
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" data-loading-text="Saving..." class="btn-dark w-full">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Save Profile</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Card -->
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-6">
            <div class="pb-4 mb-4 border-b border-[#E5E5E5]">
                <h2 class="text-sm font-semibold text-[#171717]">Change Password</h2>
                <p class="text-xs text-[#737373]">Use a strong password of at least 8 characters.</p>
            </div>

            <form method="POST" action="<?= e(url('/profile/password')) ?>" data-loading-form class="space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label for="current_password" class="block text-xs font-semibold text-[#171717] mb-1.5">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="input-monochrome">
                </div>

                <div>
                    <label for="new_password" class="block text-xs font-semibold text-[#171717] mb-1.5">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password" class="input-monochrome">
                </div>

                <div>
                    <label for="confirm_password" class="block text-xs font-semibold text-[#171717] mb-1.5">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" class="input-monochrome">
                </div>

                <div class="pt-3">
                    <button type="submit" data-loading-text="Updating..." class="btn-primary w-full">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                        <span>Update Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

