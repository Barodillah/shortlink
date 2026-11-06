<?php
use App\Models\Shortlink;
?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-[#171717] tracking-tight">My Dashboard</h1>
            <p class="text-xs text-[#737373] mt-0.5">Overview of your personal shortlinks, human vs bot traffic, and top links.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= e(url('/analytics')) ?>" class="btn-secondary text-xs">
                <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                <span>Full Analytics</span>
            </a>
            <a href="<?= e(url('/shortlinks/create')) ?>" class="btn-primary text-xs">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>New Shortlink</span>
            </a>
        </div>
    </div>

    <!-- Quick Shorten Bar -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-4 sm:p-5">
        <form method="POST" action="<?= e(url('/shortlinks')) ?>" data-loading-form class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="active">
            <div class="flex-1 relative">
                <input
                    type="url"
                    name="destination_url"
                    required
                    placeholder="Paste a long URL to shorten (e.g., https://example.com/campaign/2026)..."
                    class="input-monochrome py-2.5"
                >
            </div>
            <div class="sm:w-52">
                <input
                    type="text"
                    name="custom_alias"
                    placeholder="Custom alias (optional)"
                    pattern="[a-zA-Z0-9_-]{2,64}"
                    class="input-monochrome py-2.5 font-mono text-xs"
                >
            </div>
            <button type="submit" data-loading-text="Shortening..." class="btn-primary py-2.5 shrink-0">
                <i data-lucide="zap" class="w-4 h-4"></i>
                <span>Shorten URL</span>
            </button>
        </form>
    </div>

    <!-- Metric Cards (5 KPIs required by PRD Section 7) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Total Shortlinks</span>
                <i data-lucide="link-2" class="w-4 h-4 text-[#171717]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['total_shortlinks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">All links created</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Active Links</span>
                <i data-lucide="check-circle" class="w-4 h-4 text-[#171717]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['active_shortlinks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Ready to redirect</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Total Clicks</span>
                <i data-lucide="mouse-pointer-click" class="w-4 h-4 text-[#E60012]"></i>
            </div>
            <p class="text-2xl font-bold text-[#171717]"><?= format_number($stats['total_clicks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">All recorded hits</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Human Clicks</span>
                <i data-lucide="user-check" class="w-4 h-4 text-[#E60012]"></i>
            </div>
            <p class="text-2xl font-bold text-[#E60012]"><?= format_number($stats['human_clicks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Verified visitors</p>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4 col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between text-[#737373] mb-2">
                <span class="text-xs font-medium">Bot Clicks</span>
                <i data-lucide="bot" class="w-4 h-4 text-[#737373]"></i>
            </div>
            <p class="text-2xl font-bold text-[#737373]"><?= format_number($stats['bot_clicks']) ?></p>
            <p class="text-[11px] text-[#737373] mt-1">Crawlers & previews</p>
        </div>
    </div>

    <!-- Click Trends Chart -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-sm font-semibold text-[#171717]">Click Trends (Last <?= (int) $days ?> Days)</h2>
                <p class="text-xs text-[#737373]">Daily comparison of Human Clicks vs Bot/Crawler Requests</p>
            </div>
            <div class="flex items-center gap-1.5">
                <?php foreach ([7 => '7D', 14 => '14D', 30 => '30D'] as $dVal => $dLabel): ?>
                    <a href="<?= e(url('/dashboard?days=' . $dVal)) ?>"
                       class="px-2.5 py-1 text-xs font-medium rounded border <?= (int) $days === $dVal ? 'bg-[#171717] text-white border-[#171717]' : 'bg-white text-[#737373] border-[#E5E5E5] hover:text-[#171717]' ?>">
                        <?= e($dLabel) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="h-64 w-full">
            <canvas id="userClickTrendsChart"></canvas>
        </div>
    </div>

    <!-- Top Performing Links & Recent Links -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Performing Links -->
        <div class="bg-white border border-[#E5E5E5] rounded-lg flex flex-col">
            <div class="px-5 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-[#171717]">Top Performing Links</h2>
                    <p class="text-xs text-[#737373]">Ranked by total click volume</p>
                </div>
                <a href="<?= e(url('/shortlinks?sort=clicks_desc')) ?>" class="text-xs font-medium text-[#E60012] hover:underline">View all</a>
            </div>
            <?php if (empty($topLinks)): ?>
                <div class="p-8 text-center text-sm text-[#737373]">
                    <i data-lucide="link-2-off" class="w-6 h-6 mx-auto mb-2 text-[#737373]"></i>
                    <p>No shortlinks created yet.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-[#E5E5E5]">
                    <?php foreach ($topLinks as $item): ?>
                        <?php $fullShort = short_url($item['short_code']); ?>
                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-[#F8F8F8] transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="<?= e(url('/shortlinks/' . $item['id'] . '/analytics')) ?>" class="font-mono text-xs font-semibold text-[#E60012] hover:underline truncate">
                                        /<?= e($item['short_code']) ?>
                                    </a>
                                    <?php if (!empty($item['title'])): ?>
                                        <span class="text-xs font-medium text-[#171717] truncate">&middot; <?= e($item['title']) ?></span>
                                    <?php endif; ?>
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
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Shortlinks -->
        <div class="bg-white border border-[#E5E5E5] rounded-lg flex flex-col">
            <div class="px-5 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-[#171717]">Recently Created</h2>
                    <p class="text-xs text-[#737373]">Your latest shortlinks</p>
                </div>
                <a href="<?= e(url('/shortlinks')) ?>" class="text-xs font-medium text-[#E60012] hover:underline">Manage all</a>
            </div>
            <?php if (empty($recentLinks)): ?>
                <div class="p-8 text-center text-sm text-[#737373]">
                    <p>You haven't created any shortlinks yet.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-[#E5E5E5]">
                    <?php foreach ($recentLinks as $item): ?>
                        <?php
                        $fullShort = short_url($item['short_code']);
                        $state = Shortlink::computeEffectiveState($item);
                        ?>
                        <div class="px-5 py-3.5 flex items-center justify-between gap-3 hover:bg-[#F8F8F8] transition-colors">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-[#171717]">/<?= e($item['short_code']) ?></span>
                                    <?php if ($state === 'active'): ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php elseif ($state === 'inactive'): ?>
                                        <span class="badge badge-inactive">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge badge-expired">Expired</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-xs text-[#737373] truncate mt-0.5"><?= e($item['destination_url']) ?></p>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button"
                                        data-qr-url="<?= e($fullShort) ?>"
                                        data-qr-code="<?= e($item['short_code']) ?>"
                                        data-qr-title="<?= e($item['title'] ?: '/' . $item['short_code']) ?>"
                                        class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                        title="QR Code">
                                    <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                                </button>
                                <a href="<?= e(url('/shortlinks/' . $item['id'] . '/analytics')) ?>"
                                   class="p-1.5 text-[#737373] hover:text-[#171717] border border-[#E5E5E5] rounded bg-white"
                                   title="Analytics">
                                    <i data-lucide="bar-chart-2" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('userClickTrendsChart');
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

