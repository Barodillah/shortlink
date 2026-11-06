<?php
$baseRoute = !empty($isGlobal) ? '/admin/analytics' : '/analytics';
$totalFiltered = array_sum(array_column($byDevice, 'count')) ?: 1;
?>
<div class="space-y-6">
    <!-- Header & Filters -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-[#171717] tracking-tight">
                    <?= !empty($isGlobal) ? 'Global Platform Analytics' : 'My Link Analytics' ?>
                </h1>
                <?php if (!empty($isGlobal)): ?>
                    <span class="badge badge-expired">Superuser</span>
                <?php endif; ?>
            </div>
            <p class="text-xs text-[#737373] mt-0.5">
                Detailed breakdown of clicks by date, device, browser, operating system, country, referrer, and UTM campaigns.
            </p>
        </div>

        <form method="GET" action="<?= e(url($baseRoute)) ?>" class="flex flex-wrap items-center gap-2">
            <?php if (!empty($isGlobal) && !empty($usersList)): ?>
                <select name="user_id" onchange="this.form.submit()" class="input-monochrome w-auto text-xs py-1.5">
                    <option value="">All Users</option>
                    <?php foreach ($usersList as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= (int) ($filterUserId ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <select name="traffic" onchange="this.form.submit()" class="input-monochrome w-auto text-xs py-1.5">
                <option value="human" <?= ($botFilter ?? 'human') === 'human' ? 'selected' : '' ?>>Human Clicks Only</option>
                <option value="all" <?= ($botFilter ?? '') === 'all' ? 'selected' : '' ?>>All Traffic (Human + Bot)</option>
                <option value="bot" <?= ($botFilter ?? '') === 'bot' ? 'selected' : '' ?>>Bot / Crawlers Only</option>
            </select>

            <select name="days" onchange="this.form.submit()" class="input-monochrome w-auto text-xs py-1.5">
                <option value="7" <?= (int) $days === 7 ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="14" <?= (int) $days === 14 ? 'selected' : '' ?>>Last 14 Days</option>
                <option value="30" <?= (int) $days === 30 ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="90" <?= (int) $days === 90 ? 'selected' : '' ?>>Last 90 Days</option>
            </select>
        </form>
    </div>

    <!-- Period Summary KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">Total Clicks (<?= (int) $days ?>D)</span>
            <p class="text-2xl font-bold text-[#171717] mt-1"><?= format_number($periodSummary['total_clicks']) ?></p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">Human Clicks (<?= (int) $days ?>D)</span>
            <p class="text-2xl font-bold text-[#E60012] mt-1"><?= format_number($periodSummary['human_clicks']) ?></p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">Bot Clicks (<?= (int) $days ?>D)</span>
            <p class="text-2xl font-bold text-[#737373] mt-1"><?= format_number($periodSummary['bot_clicks']) ?></p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">Unique IPs (<?= (int) $days ?>D)</span>
            <p class="text-2xl font-bold text-[#171717] mt-1"><?= format_number($periodSummary['unique_visitors']) ?></p>
        </div>
    </div>

    <!-- Clicks by Date & Device Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white border border-[#E5E5E5] rounded-lg p-5">
            <h2 class="text-sm font-semibold text-[#171717]">Clicks by Date (Last <?= (int) $days ?> Days)</h2>
            <p class="text-xs text-[#737373] mb-4">Daily human vs automated bot traffic</p>
            <div class="h-64">
                <canvas id="analyticsDateChart"></canvas>
            </div>
        </div>

        <div class="lg:col-span-4 bg-white border border-[#E5E5E5] rounded-lg p-5 flex flex-col justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[#171717]">Clicks by Device</h2>
                <p class="text-xs text-[#737373] mb-4">Distribution across device classes</p>
                <div class="h-44">
                    <canvas id="analyticsDeviceChart"></canvas>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#E5E5E5] space-y-1.5 text-xs">
                <?php if (empty($byDevice)): ?>
                    <p class="text-[#737373] text-center py-2">No device data in this period.</p>
                <?php else: ?>
                    <?php foreach ($byDevice as $dev): ?>
                        <?php $pct = round(((int) $dev['count'] / $totalFiltered) * 100, 1); ?>
                        <div class="flex items-center justify-between">
                            <span class="text-[#171717] font-medium"><?= e($dev['label']) ?></span>
                            <span class="text-[#737373] font-mono"><?= format_number((int) $dev['count']) ?> (<?= $pct ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 4 Breakdown Grids: Browser, OS, Country, Referrer -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php
        $panels = [
            ['title' => 'Clicks by Browser', 'subtitle' => 'Top visitor web browsers', 'icon' => 'compass', 'rows' => $byBrowser],
            ['title' => 'Clicks by Operating System', 'subtitle' => 'Visitor OS platforms', 'icon' => 'cpu', 'rows' => $byOs],
            ['title' => 'Clicks by Country', 'subtitle' => 'Geographic origin of clicks', 'icon' => 'map-pin', 'rows' => $byCountry],
            ['title' => 'Clicks by Referrer Domain', 'subtitle' => 'Traffic acquisition sources', 'icon' => 'share-2', 'rows' => $byReferrer],
        ];
        ?>
        <?php foreach ($panels as $panel): ?>
            <?php $panelTotal = max(1, array_sum(array_column($panel['rows'], 'count'))); ?>
            <div class="bg-white border border-[#E5E5E5] rounded-lg p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-[#171717]"><?= e($panel['title']) ?></h3>
                        <p class="text-xs text-[#737373]"><?= e($panel['subtitle']) ?></p>
                    </div>
                    <i data-lucide="<?= e($panel['icon']) ?>" class="w-4 h-4 text-[#737373]"></i>
                </div>
                <?php if (empty($panel['rows'])): ?>
                    <p class="text-xs text-[#737373] py-6 text-center">No click data recorded for this filter.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($panel['rows'] as $idx => $r): ?>
                            <?php $pct = min(100, round(((int) $r['count'] / $panelTotal) * 100, 1)); ?>
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-[#171717] truncate"><?= e($r['label']) ?></span>
                                    <span class="font-mono text-[#737373]"><?= format_number((int) $r['count']) ?> (<?= $pct ?>%)</span>
                                </div>
                                <div class="w-full h-1.5 bg-[#F8F8F8] rounded overflow-hidden">
                                    <div class="h-full <?= $idx === 0 ? 'bg-[#E60012]' : 'bg-[#171717]' ?>" style="width: <?= $pct ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- UTM Campaign Performance Table -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-[#E5E5E5] flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-[#171717]">UTM Campaign Performance</h2>
                <p class="text-xs text-[#737373]">Tracked marketing parameters (utm_source, utm_medium, utm_campaign, utm_term, utm_content)</p>
            </div>
            <i data-lucide="megaphone" class="w-4 h-4 text-[#E60012]"></i>
        </div>
        <?php if (empty($utmRows)): ?>
            <div class="p-8 text-center text-xs text-[#737373]">
                No UTM parameter clicks recorded within the selected time window.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E5E5E5] bg-[#F8F8F8] text-[11px] font-semibold uppercase tracking-wider text-[#737373]">
                            <th class="py-2.5 px-4">UTM Source</th>
                            <th class="py-2.5 px-4">UTM Medium</th>
                            <th class="py-2.5 px-4">UTM Campaign</th>
                            <th class="py-2.5 px-4">UTM Term</th>
                            <th class="py-2.5 px-4">UTM Content</th>
                            <th class="py-2.5 px-4 text-right">Clicks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E5E5] text-xs">
                        <?php foreach ($utmRows as $utm): ?>
                            <tr class="hover:bg-[#F8F8F8]">
                                <td class="py-2.5 px-4 font-mono font-medium text-[#171717]"><?= e($utm['utm_source']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#737373]"><?= e($utm['utm_medium']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#171717]"><?= e($utm['utm_campaign']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#737373]"><?= e($utm['utm_term']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#737373]"><?= e($utm['utm_content']) ?></td>
                                <td class="py-2.5 px-4 text-right font-bold text-[#171717]"><?= format_number((int) $utm['clicks']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Clicks Log -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-[#E5E5E5]">
            <h2 class="text-sm font-semibold text-[#171717]">Recent Click Stream</h2>
            <p class="text-xs text-[#737373]">Latest individual redirect events</p>
        </div>
        <div class="table-container">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#E5E5E5] bg-[#F8F8F8] text-[11px] font-semibold uppercase tracking-wider text-[#737373]">
                        <th class="py-2.5 px-4">Time</th>
                        <th class="py-2.5 px-4">Shortlink</th>
                        <th class="py-2.5 px-4">Visitor Type</th>
                        <th class="py-2.5 px-4">Device / Browser / OS</th>
                        <th class="py-2.5 px-4">Location</th>
                        <th class="py-2.5 px-4">Referrer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5] text-xs">
                    <?php if (empty($recentClicks)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[#737373]">No clicks recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentClicks as $c): ?>
                            <tr class="hover:bg-[#F8F8F8]">
                                <td class="py-2.5 px-4 whitespace-nowrap text-[#737373]"><?= e(time_ago($c['clicked_at'])) ?></td>
                                <td class="py-2.5 px-4 whitespace-nowrap font-mono font-semibold text-[#E60012]">
                                    <a href="<?= e(url('/shortlinks/' . $c['shortlink_id'] . '/analytics')) ?>" class="hover:underline">
                                        /<?= e($c['short_code']) ?>
                                    </a>
                                </td>
                                <td class="py-2.5 px-4 whitespace-nowrap">
                                    <?php if ((int) $c['is_bot'] === 1): ?>
                                        <span class="badge badge-inactive">Bot</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">Human</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2.5 px-4 whitespace-nowrap text-[#171717]">
                                    <?= e($c['device_type']) ?> &middot; <?= e($c['browser']) ?> &middot; <?= e($c['os']) ?>
                                </td>
                                <td class="py-2.5 px-4 whitespace-nowrap text-[#737373]">
                                    <?= e($c['city']) ?>, <?= e($c['country']) ?>
                                </td>
                                <td class="py-2.5 px-4 whitespace-nowrap font-mono text-[11px] text-[#737373]">
                                    <?= e($c['referrer_domain'] ?: 'Direct') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    var dateCtx = document.getElementById('analyticsDateChart');
    if (dateCtx) {
        new Chart(dateCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($clicksByDate['labels']) ?>,
                datasets: [
                    {
                        label: 'Human Clicks',
                        data: <?= json_encode($clicksByDate['human']) ?>,
                        borderColor: '#E60012',
                        backgroundColor: 'rgba(230, 0, 18, 0.06)',
                        borderWidth: 2,
                        tension: 0.25,
                        fill: true,
                        pointRadius: 3
                    },
                    {
                        label: 'Bot Clicks',
                        data: <?= json_encode($clicksByDate['bot']) ?>,
                        borderColor: '#737373',
                        borderDash: [4, 4],
                        borderWidth: 1.5,
                        tension: 0.25,
                        fill: false,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, font: { family: 'Inter', size: 11 } } } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#737373' } },
                    y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Inter', size: 11 }, color: '#737373' }, grid: { color: '#F0F0F0' } }
                }
            }
        });
    }

    var devCtx = document.getElementById('analyticsDeviceChart');
    if (devCtx) {
        var devLabels = <?= json_encode(array_column($byDevice, 'label')) ?>;
        var devCounts = <?= json_encode(array_map('intval', array_column($byDevice, 'count'))) ?>;
        new Chart(devCtx, {
            type: 'doughnut',
            data: {
                labels: devLabels.length ? devLabels : ['No Data'],
                datasets: [{
                    data: devCounts.length ? devCounts : [1],
                    backgroundColor: devCounts.length ? ['#E60012', '#171717', '#737373', '#D4D4D4'] : ['#F0F0F0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { family: 'Inter', size: 11 } } }
                }
            }
        });
    }
});
</script>

