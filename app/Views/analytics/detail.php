<?php
use App\Models\Shortlink;

$fullShortUrl   = short_url($shortlink['short_code']);
$effectiveState = Shortlink::computeEffectiveState($shortlink);
$totalFiltered  = array_sum(array_column($byDevice, 'count')) ?: 1;
?>
<div class="space-y-6">
    <!-- Link Header Card -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg p-5">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-lg font-bold text-[#E60012]">/<?= e($shortlink['short_code']) ?></span>
                    <?php if ($effectiveState === 'active'): ?>
                        <span class="badge badge-active">Active</span>
                    <?php elseif ($effectiveState === 'inactive'): ?>
                        <span class="badge badge-inactive">Inactive</span>
                    <?php else: ?>
                        <span class="badge badge-expired">Expired / Cap Reached</span>
                    <?php endif; ?>
                    <?php if (!empty($shortlink['title'])): ?>
                        <span class="text-sm font-semibold text-[#171717]">&middot; <?= e($shortlink['title']) ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-[#737373] break-all">
                    Destination: <a href="<?= e($shortlink['destination_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-[#171717] underline"><?= e($shortlink['destination_url']) ?></a>
                </p>
                <p class="text-[11px] text-[#737373]">
                    Owner: <strong class="text-[#171717]"><?= e($shortlink['owner_name']) ?></strong> &middot; Created <?= e(date('d M Y H:i', strtotime((string) $shortlink['created_at']))) ?>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" data-copy-text="<?= e($fullShortUrl) ?>" class="btn-secondary text-xs">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copy Link</span>
                </button>
                <button type="button"
                        data-qr-url="<?= e($fullShortUrl) ?>"
                        data-qr-code="<?= e($shortlink['short_code']) ?>"
                        data-qr-title="<?= e($shortlink['title'] ?: '/' . $shortlink['short_code']) ?>"
                        class="btn-secondary text-xs">
                    <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                    <span>QR Code</span>
                </button>
                <a href="<?= e(url('/shortlinks/' . $shortlink['id'] . '/edit')) ?>" class="btn-secondary text-xs">
                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    <span>Edit</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="text-sm font-semibold text-[#171717]">Performance Breakdown</h2>
        <form method="GET" action="<?= e(url('/shortlinks/' . $shortlink['id'] . '/analytics')) ?>" class="flex items-center gap-2">
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

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">All-Time Total Clicks</span>
            <p class="text-2xl font-bold text-[#171717] mt-1"><?= format_number((int) $shortlink['total_clicks']) ?></p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">All-Time Human Clicks</span>
            <p class="text-2xl font-bold text-[#E60012] mt-1"><?= format_number((int) $shortlink['human_clicks']) ?></p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">All-Time Bot Clicks</span>
            <p class="text-2xl font-bold text-[#737373] mt-1"><?= format_number((int) $shortlink['bot_clicks']) ?></p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-lg p-4">
            <span class="text-xs font-medium text-[#737373]">Unique IPs (<?= (int) $days ?>D)</span>
            <p class="text-2xl font-bold text-[#171717] mt-1"><?= format_number($periodSummary['unique_visitors']) ?></p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white border border-[#E5E5E5] rounded-lg p-5">
            <h3 class="text-sm font-semibold text-[#171717]">Clicks by Date (Last <?= (int) $days ?> Days)</h3>
            <p class="text-xs text-[#737373] mb-4">Daily human clicks vs bot hits for /<?= e($shortlink['short_code']) ?></p>
            <div class="h-64">
                <canvas id="detailDateChart"></canvas>
            </div>
        </div>
        <div class="lg:col-span-4 bg-white border border-[#E5E5E5] rounded-lg p-5 flex flex-col justify-between">
            <div>
                <h3 class="text-sm font-semibold text-[#171717]">Clicks by Device</h3>
                <p class="text-xs text-[#737373] mb-4">Device type distribution</p>
                <div class="h-44">
                    <canvas id="detailDeviceChart"></canvas>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-[#E5E5E5] space-y-1.5 text-xs">
                <?php foreach ($byDevice as $dev): ?>
                    <?php $pct = round(((int) $dev['count'] / $totalFiltered) * 100, 1); ?>
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-[#171717]"><?= e($dev['label']) ?></span>
                        <span class="font-mono text-[#737373]"><?= format_number((int) $dev['count']) ?> (<?= $pct ?>%)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 4 Breakdown Panels -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php
        $panels = [
            ['title' => 'Clicks by Browser', 'rows' => $byBrowser],
            ['title' => 'Clicks by Operating System', 'rows' => $byOs],
            ['title' => 'Clicks by Country', 'rows' => $byCountry],
            ['title' => 'Clicks by Referrer', 'rows' => $byReferrer],
        ];
        ?>
        <?php foreach ($panels as $panel): ?>
            <?php $panelTotal = max(1, array_sum(array_column($panel['rows'], 'count'))); ?>
            <div class="bg-white border border-[#E5E5E5] rounded-lg p-5">
                <h3 class="text-sm font-semibold text-[#171717] mb-3"><?= e($panel['title']) ?></h3>
                <?php if (empty($panel['rows'])): ?>
                    <p class="text-xs text-[#737373] py-4 text-center">No clicks recorded.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($panel['rows'] as $idx => $r): ?>
                            <?php $pct = min(100, round(((int) $r['count'] / $panelTotal) * 100, 1)); ?>
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-[#171717]"><?= e($r['label']) ?></span>
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

    <!-- UTM Performance -->
    <div class="bg-white border border-[#E5E5E5] rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-[#E5E5E5]">
            <h3 class="text-sm font-semibold text-[#171717]">UTM Performance</h3>
            <p class="text-xs text-[#737373]">Campaign attribution for /<?= e($shortlink['short_code']) ?></p>
        </div>
        <?php if (empty($utmRows)): ?>
            <div class="p-6 text-center text-xs text-[#737373]">No UTM tags recorded for this shortlink.</div>
        <?php else: ?>
            <div class="table-container">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#E5E5E5] bg-[#F8F8F8] text-[11px] font-semibold uppercase tracking-wider text-[#737373]">
                            <th class="py-2.5 px-4">Source</th>
                            <th class="py-2.5 px-4">Medium</th>
                            <th class="py-2.5 px-4">Campaign</th>
                            <th class="py-2.5 px-4">Term</th>
                            <th class="py-2.5 px-4">Content</th>
                            <th class="py-2.5 px-4 text-right">Clicks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E5E5] text-xs">
                        <?php foreach ($utmRows as $u): ?>
                            <tr class="hover:bg-[#F8F8F8]">
                                <td class="py-2.5 px-4 font-mono text-[#171717]"><?= e($u['utm_source']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#737373]"><?= e($u['utm_medium']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#171717]"><?= e($u['utm_campaign']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#737373]"><?= e($u['utm_term']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-[#737373]"><?= e($u['utm_content']) ?></td>
                                <td class="py-2.5 px-4 text-right font-bold text-[#171717]"><?= format_number((int) $u['clicks']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    var dateCtx = document.getElementById('detailDateChart');
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

    var devCtx = document.getElementById('detailDeviceChart');
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
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { family: 'Inter', size: 11 } } } }
            }
        });
    }
});
</script>

