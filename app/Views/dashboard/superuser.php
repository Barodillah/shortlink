<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-[#171717] tracking-tight">Global Platform Dashboard</h1>
                <span class="badge badge-expired">Superuser</span>
            </div>
            <p class="text-xs text-[#737373] mt-0.5">Real-time platform metrics across all users, shortlinks, and redirect traffic.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(url('/admin/users/create')) ?>" class="btn-secondary text-xs">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Add User</span>
            </a>
            <a href="<?= e(url('/shortlinks/create')) ?>" class="btn-primary text-xs">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Create Shortlink</span>
            </a>
        </div>
    </div>

    <!-- 6 Global KPI Cards (PRD Section 7: Total Users, Total Shortlinks, Total Clicks, Human Clicks, Bot Clicks, Active Shortlinks) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Total Users</span>
                <i data-lucide="users" class="w-4 h-4 text-[#171717]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['total_users']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1"><?= format_number($stats['active_users']) ?> active accounts</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Total Shortlinks</span>
                <i data-lucide="link-2" class="w-4 h-4 text-[#171717]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['total_shortlinks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Platform-wide</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Active Links</span>
                <i data-lucide="check-circle" class="w-4 h-4 text-[#171717]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['active_shortlinks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Currently live</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Total Clicks</span>
                <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-[#E60012]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['total_clicks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">All platform traffic</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Human Clicks</span>
                <i data-lucide="user-check" class="w-4 h-4 text-[#E60012]"></i>
            </div>
            <p class="text-2xl font-bold text-[#E60012]"><?= format_number($stats['human_clicks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Verified visitors</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Bot Clicks</span>
                <i data-lucide="bot" class="w-4 h-4 text-[#737373]"></i>
            </div>
            <p class="text-2xl font-bold text-[#737373]"><?= format_number($stats['bot_clicks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Automated crawlers</p>
        </div>
    </div>

    <!-- Global Click Trends Chart -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-sm font-semibold text-[#171717]">Platform Click Trends (Last <?= (int) $days ?> Days)</h2>
                <p class="text-xs text-[#737373]">Aggregated daily Human Clicks vs Bot Clicks across all users</p>
            </div>
            <div class="flex items-center gap-1.5">
                <?php foreach ([7 => '7D', 14 => '14D', 30 => '30D'] as $dVal => $dLabel): ?>
                    <a href="<?= e(url('/admin/dashboard?days=' . $dVal)) ?>"
                       class="px-2.5 py-1 text-xs font-medium rounded border <?= (int) $days === $dVal ? 'bg-[#171717] text-white border-[#171717]' : 'bg-white text-[#737373] border-[#E5E5E5] hover:text-[#171717]' ?>">
                        <?= e($dLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="h-64 w-full">
            <canvas id="globalClickTrendsChart"></canvas>
        </div>
    </div>

    <!-- Top Performing Links & Top Users -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Top Performing Links (7 cols) -->
        <div class="lg:col-span-7 bg-white border border-[#E5E5E5] rounded-lg flex flex-col">
            <div class="px-5 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-[#171717]">Top Performing Shortlinks</h2>
                    <p class="text-xs text-[#737373]">Highest traffic shortlinks across the platform</p>
                </div>
                <a href="<?= e(url('/admin/shortlinks?sort=clicks_desc')) ?>" class="text-xs font-medium text-[#E60012] hover:underline">All Shortlinks</a>
            </div>
            <div class="divide-y divide-[#E5E5E5]">
                <?php if (empty($topLinks)): ?>
                    <div class="p-8 text-center text-sm text-[#737373]">No shortlinks available.</div>
                <?php else: ?>
                    <?php foreach ($topLinks as $item): ?>
                        <?php $fullShort = short_url($item['short_code']); ?>
                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-[#F8F8F8] transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="<?= e(url('/shortlinks/' . $item['id'] . '/analytics')) ?>" class="font-mono text-xs font-semibold text-[#E60012] hover:underline">
                                        /<?= e($item['short_code']) ?>
                                    </a>
                                    <span class="text-xs text-[#171717] font-medium truncate"><?= e($item['title'] ?: 'Untitled') ?></span>
                                    <span class="text-[11px] text-[#737373] hidden sm:inline">&middot; by <?= e($item['owner_name']) ?></span>
                                </div>
                                <p class="text-xs text-[#737373] truncate mt-0.5"><?= e($item['destination_url']) ?></p>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <span class="block text-sm font-bold text-[#171717]"><?= format_number((int) $item['total_clicks']) ?></span>
                                    <span class="block text-[10px] text-[#737373]"><?= format_number((int) $item['human_clicks']) ?> human</span>
                                </div>
                                <button type="button" data-copy-text="<?= e($fullShort) ?>" class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white" title="Copy">
                                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Users (5 cols) -->
        <div class="lg:col-span-5 bg-white border border-[#E5E5E5] rounded-lg flex flex-col">
            <div class="px-5 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-[#171717]">Top Users</h2>
                    <p class="text-xs text-[#737373]">Users ranked by total link clicks</p>
                </div>
                <a href="<?= e(url('/admin/users')) ?>" class="text-xs font-medium text-[#E60012] hover:underline">Manage Users</a>
            </div>
            <div class="divide-y divide-[#E5E5E5]">
                <?php foreach ($topUsers as $u): ?>
                    <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-[#F8F8F8] transition-colors">
                        <div class="min-w-0">
                            <a href="<?= e(url('/admin/users/' . $u['id'])) ?>" class="text-xs font-semibold text-[#171717] hover:text-[#E60012] truncate block">
                                <?= e($u['name']) ?>
                            </a>
                            <p class="text-[11px] text-[#737373] truncate"><?= e($u['email']) ?></p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="block text-xs font-bold text-[#171717]"><?= format_number((int) $u['total_clicks']) ?> clicks</span>
                            <span class="block text-[11px] text-[#737373]"><?= format_number((int) $u['total_links']) ?> links</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity Logs -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg">
        <div class="px-5 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[#171717]">Recent Platform Activity</h2>
                <p class="text-xs text-[#737373]">Latest security & operational events</p>
            </div>
            <a href="<?= e(url('/admin/logs')) ?>" class="text-xs font-medium text-[#E60012] hover:underline">Full Activity Log</a>
        </div>
        <div class="table-container">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#E5E5E5] bg-[#F8F8F8] text-[11px] font-semibold uppercase tracking-wider text-[#737373]">
                        <th class="py-2.5 px-4">Time</th>
                        <th class="py-2.5 px-4">User</th>
                        <th class="py-2.5 px-4">Action</th>
                        <th class="py-2.5 px-4">Description</th>
                        <th class="py-2.5 px-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5] text-xs">
                    <?php foreach ($recentLogs as $log): ?>
                        <tr class="hover:bg-[#F8F8F8]">
                            <td class="py-2.5 px-4 whitespace-nowrap text-[#737373]"><?= e(time_ago($log['created_at'])) ?></td>
                            <td class="py-2.5 px-4 whitespace-nowrap font-medium text-[#171717]">
                                <?= e($log['user_name'] ?? 'System / Guest') ?>
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap">
                                <code class="px-1.5 py-0.5 bg-[#F8F8F8] border border-[#E5E5E5] rounded text-[11px] font-mono text-[#171717]">
                                    <?= e($log['action']) ?>
                                </code>
                            </td>
                            <td class="py-2.5 px-4 text-[#737373] max-w-md truncate"><?= e($log['description']) ?></td>
                            <td class="py-2.5 px-4 whitespace-nowrap font-mono text-[11px] text-[#737373]"><?= e($log['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('globalClickTrendsChart');
    if (!ctx || typeof Chart === 'undefined') return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trends['labels']) ?>,
            datasets: [
                {
                    label: 'Human Clicks',
                    data: <?= json_encode($trends['human']) ?>,
                    borderColor: '#E60012',
                    backgroundColor: 'rgba(230, 0, 18, 0.06)',
                    borderWidth: 2,
                    tension: 0.25,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#E60012'
                },
                {
                    label: 'Bot / Crawler Hits',
                    data: <?= json_encode($trends['bot']) ?>,
                    borderColor: '#737373',
                    backgroundColor: 'transparent',
                    borderDash: [4, 4],
                    borderWidth: 1.5,
                    tension: 0.25,
                    fill: false,
                    pointRadius: 2,
                    pointBackgroundColor: '#737373'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: { boxWidth: 10, usePointStyle: true, font: { family: 'Inter', size: 11 } }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#737373' } },
                y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Inter', size: 11 }, color: '#737373' }, grid: { color: '#F0F0F0' } }
            }
        }
    });
});
</script>

