@extends('layouts.master')

@section('content')
@php
    $primaryKpiCards = [
        ['label' => 'Users', 'value' => $systemCounts['users'] ?? 0, 'icon' => 'users', 'color' => 'indigo'],
        ['label' => 'Customers', 'value' => $systemCounts['customers'] ?? 0, 'icon' => 'address-book', 'color' => 'emerald'],
        ['label' => 'Inquiries', 'value' => $systemCounts['inquiries'] ?? 0, 'icon' => 'circle-question', 'color' => 'sky'],
        ['label' => 'Quotations', 'value' => $systemCounts['quotations'] ?? 0, 'icon' => 'file-lines', 'color' => 'amber'],
        ['label' => 'Bookings', 'value' => $systemCounts['bookings'] ?? 0, 'icon' => 'calendar-check', 'color' => 'violet'],
        ['label' => 'Itineraries', 'value' => $systemCounts['itineraries'] ?? 0, 'icon' => 'route', 'color' => 'cyan'],
    ];
    $secondaryKpiCards = [
        ['label' => 'Invoices', 'value' => $systemCounts['invoices'] ?? 0, 'icon' => 'file-invoice-dollar', 'color' => 'amber'],
        ['label' => 'Vendors', 'value' => $systemCounts['vendors'] ?? 0, 'icon' => 'handshake', 'color' => 'teal'],
        ['label' => 'Activities', 'value' => $systemCounts['activities'] ?? 0, 'icon' => 'person-hiking', 'color' => 'lime'],
        ['label' => 'Attractions', 'value' => $systemCounts['tourist_attractions'] ?? 0, 'icon' => 'landmark', 'color' => 'rose'],
    ];

    $statusBlocks = [
        'Inquiry Status' => $inquiryByStatus,
        'Quotation Status' => $quotationByStatus,
        'Booking Status' => $bookingByStatus,
    ];

    $initialBars = [
        $systemCounts['inquiries'] ?? 0,
        $systemCounts['quotations'] ?? 0,
        $systemCounts['bookings'] ?? 0,
        $systemCounts['itineraries'] ?? 0,
        $systemCounts['vendors'] ?? 0,
        $systemCounts['activities'] ?? 0,
        $operationalAlerts['pending_followups'] ?? 0,
        $operationalAlerts['followups_overdue'] ?? 0,
        $healthInfo['queue_backlog'] ?? 0,
        $healthInfo['failed_jobs'] ?? 0,
    ];

    $mixLabels = ['Inquiries', 'Quotations', 'Bookings', 'Itineraries'];
    $mixValues = [
        (int) ($systemCounts['inquiries'] ?? 0),
        (int) ($systemCounts['quotations'] ?? 0),
        (int) ($systemCounts['bookings'] ?? 0),
        (int) ($systemCounts['itineraries'] ?? 0),
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        

        <section class="xl:col-span-7">
            <div class="sa-card p-5">
                <div class="flex flex-col gap-1 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Super Admin Analytics</h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Data diperbarui saat halaman dibuka atau refresh manual</p>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Last loaded: {{ now()->format('d M Y H:i') }}</p>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Core Business KPIs</p>
                </div>
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($primaryKpiCards as $card)
                        <div class="sa-kpi">
                            <div class="flex items-center justify-between">
                                <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                                <span class="text-[10px] text-slate-400">live</span>
                            </div>
                            <p>{{ $card['label'] }}</p>
                            <b>{{ number_format($card['value']) }}</b>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($secondaryKpiCards as $card)
                        <div class="sa-mini-kpi">
                            <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                            <div>
                                <p>{{ $card['label'] }}</p>
                                <b>{{ number_format($card['value']) }}</b>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-12">
                    <div class="lg:col-span-9 rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 text-xs dark:border-slate-700 dark:bg-slate-900" id="periodButtons">
                                <button type="button" class="sa-period-btn is-active" data-period="7">7D</button>
                                <button type="button" class="sa-period-btn" data-period="30">30D</button>
                                <button type="button" class="sa-period-btn" data-period="90">90D</button>
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Refresh browser untuk update data terbaru</span>
                        </div>
                        <div class="h-44 w-full"><canvas id="superadminTrendChart"></canvas></div>
                        <div id="trendLegend" class="mt-2 flex flex-wrap items-center gap-2 text-xs"></div>
                        <div class="mt-3 h-32 w-full"><canvas id="superadminBarsChart"></canvas></div>
                    </div>
                    <div class="lg:col-span-3 space-y-2">
                        <div class="sa-mini"><span>Modules Enabled</span><b>{{ $moduleStats['enabled'] ?? 0 }}</b></div>
                        <div class="sa-mini"><span>Modules Disabled</span><b>{{ $moduleStats['disabled'] ?? 0 }}</b></div>
                        <div class="sa-mini"><span>Total Roles</span><b>{{ $rolesAndPermissions['roles'] ?? 0 }}</b></div>
                        <div class="sa-mini"><span>Total Permissions</span><b>{{ $rolesAndPermissions['permissions'] ?? 0 }}</b></div>
                        <div class="sa-mini"><span>Queue Backlog</span><b>{{ $healthInfo['queue_backlog'] ?? 0 }}</b></div>
                        <div class="sa-mini"><span>Failed Jobs</span><b class="text-rose-600 dark:text-rose-400">{{ $healthInfo['failed_jobs'] ?? 0 }}</b></div>
                        <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                            <p class="text-xs text-slate-500 dark:text-slate-400">System Mix</p>
                            <div class="mx-auto mt-2 h-28 w-28"><canvas id="superadminMixChart"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
                <div class="sa-card p-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent System History</h2>
                    <div class="mt-2 space-y-2 max-h-72 overflow-y-auto">
                        @forelse($recentSystemHistory as $entry)
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900">
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $entry['type'] }} - {{ $entry['title'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $entry['meta'] }} • {{ \Illuminate\Support\Carbon::parse($entry['updated_at'])->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No history yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="sa-card p-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Activity Log</h2>
                    <div class="mt-2 space-y-2 max-h-72 overflow-y-auto">
                        @forelse($activityLogs as $log)
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs dark:bg-slate-900">
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $log->event ?: ($log->log_name ?: '-') }}</p>
                                <p class="text-slate-500 dark:text-slate-400">{{ $log->description ?: '-' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No activity log.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
        
        <aside class="xl:col-span-3">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Critical Monitoring</h3>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div class="sa-critical">
                        <small>Failed Jobs</small>
                        <b class="text-rose-600 dark:text-rose-400">{{ $healthInfo['failed_jobs'] ?? 0 }}</b>
                    </div>
                    <div class="sa-critical">
                        <small>Overdue</small>
                        <b class="text-rose-600 dark:text-rose-400">{{ $operationalAlerts['followups_overdue'] ?? 0 }}</b>
                    </div>
                    <div class="sa-critical">
                        <small>Queue</small>
                        <b>{{ $healthInfo['queue_backlog'] ?? 0 }}</b>
                    </div>
                </div>
            </div>

            <div class="sa-card p-4 mt-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Status Summary</h3>
                <div class="mt-3 space-y-3">
                    @foreach($statusBlocks as $title => $rows)
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</p>
                            <div class="mt-2 space-y-1">
                                @forelse($rows as $status => $total)
                                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <x-status-badge :status="$status" :label="ucfirst(str_replace('_', ' ', $status))" size="xs" />
                                        <b>{{ $total }}</b>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No data</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sa-card p-4 mt-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Upcoming Follow-ups</h3>
                <div class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                    @forelse($upcomingFollowUps as $followUp)
                        <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs dark:bg-slate-900">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $followUp->inquiry->inquiry_number ?? '-' }}</p>
                            <p class="text-slate-500 dark:text-slate-400">Due {{ optional($followUp->due_date)->format('Y-m-d') ?? '-' }} • {{ $followUp->channel ?: '-' }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No upcoming follow-ups.</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card p-4 mt-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Expiring Quotations</h3>
                <div class="mt-2 space-y-2 max-h-40 overflow-y-auto">
                    @forelse($expiringQuotations as $quotation)
                        <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs dark:bg-slate-900">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $quotation->quotation_number }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ ucfirst($quotation->status) }} • {{ optional($quotation->validity_date)->format('Y-m-d') ?? '-' }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No expiring quotations.</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card p-4 mt-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Failed Jobs</h3>
                <div class="mt-2 space-y-2 max-h-36 overflow-y-auto">
                    @forelse($failedJobs->take(5) as $job)
                        <div class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                            #{{ $job->id }} • {{ \Illuminate\Support\Carbon::parse($job->failed_at)->diffForHumans() }}
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No failed jobs.</p>
                    @endforelse
                </div>
            </div>
        </aside>
        <aside class="xl:col-span-2">
            <div class="sa-card p-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-orange-500/15 text-orange-500 grid place-items-center"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Super Admin</p>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-xs">
                    <div class="sa-mini"><span>Environment</span><b>{{ strtoupper($healthInfo['environment'] ?? '-') }}</b></div>
                    <div class="sa-mini"><span>DB Connection</span><b>{{ $healthInfo['database_connection'] ?? '-' }}</b></div>
                    <div class="sa-mini"><span>Queue Connection</span><b>{{ $healthInfo['queue_connection'] ?? '-' }}</b></div>
                    <div class="sa-mini"><span>Debug</span><b>{{ ($healthInfo['debug'] ?? false) ? 'ON' : 'OFF' }}</b></div>
                </div>
            </div>

            <div class="sa-card p-4 mt-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Operational Alerts</h3>
                <div class="mt-2 space-y-2 text-xs">
                    <div class="sa-mini"><span>Pending Follow-ups</span><b>{{ $operationalAlerts['pending_followups'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Due Today</span><b>{{ $operationalAlerts['followups_due_today'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Overdue</span><b class="text-rose-600 dark:text-rose-400">{{ $operationalAlerts['followups_overdue'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Expiring Quotations (7D)</span><b>{{ $operationalAlerts['quotations_expiring_7d'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Upcoming Bookings (7D)</span><b>{{ $operationalAlerts['upcoming_bookings_7d'] ?? 0 }}</b></div>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const trendEl = document.getElementById('superadminTrendChart');
        const barsEl = document.getElementById('superadminBarsChart');
        const mixEl = document.getElementById('superadminMixChart');
        const periodButtonsWrap = document.getElementById('periodButtons');
        const trendLegendEl = document.getElementById('trendLegend');
        const trendEndpoint = @json(route('superadmin.dashboard.trend'));
        const initialBars = @json($initialBars);
        const initialMixLabels = @json($mixLabels);
        const initialMixValues = @json($mixValues);

        let trendChart = null;
        let barsChart = null;
        let mixChart = null;
        let currentPeriod = 7;

        function renderTrendLegend() {
            if (!trendLegendEl || !trendChart) return;
            const datasets = trendChart.data.datasets || [];
            trendLegendEl.innerHTML = datasets.map((dataset, i) => {
                const cls = trendChart.isDatasetVisible(i) ? '' : 'is-off';
                return `<button type="button" class="sa-legend-btn ${cls}" data-ds="${i}"><span class="dot" style="background:${dataset.borderColor}"></span>${dataset.label}</button>`;
            }).join('');
            trendLegendEl.querySelectorAll('.sa-legend-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const index = Number(btn.dataset.ds);
                    trendChart.setDatasetVisibility(index, !trendChart.isDatasetVisible(index));
                    trendChart.update();
                    renderTrendLegend();
                });
            });
        }

        function drawTrendChart(labels, trend) {
            if (!trendEl) return;
            const ctx = trendEl.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 180);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0.02)');
            if (trendChart) trendChart.destroy();

            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: 'Inquiries', data: trend.inquiries || [], borderColor: '#6366f1', backgroundColor: gradient, fill: true, pointRadius: 2, tension: 0.35 },
                        { label: 'Quotations', data: trend.quotations || [], borderColor: '#f97316', backgroundColor: 'transparent', fill: false, pointRadius: 2, tension: 0.35 },
                        { label: 'Bookings', data: trend.bookings || [], borderColor: '#10b981', backgroundColor: 'transparent', fill: false, pointRadius: 2, tension: 0.35 },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => `Date: ${items[0]?.label || '-'}`,
                                label: (context) => `${context.dataset.label}: ${context.parsed.y || 0}`,
                                footer: (items) => `Total: ${items.reduce((sum, item) => sum + (item.parsed.y || 0), 0)}`
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 8 } },
                        y: { grid: { color: 'rgba(148,163,184,.2)' }, ticks: { color: '#94a3b8', precision: 0 } }
                    }
                }
            });
            renderTrendLegend();
        }

        function drawBarsChart(values) {
            if (!barsEl) return;
            if (barsChart) barsChart.destroy();
            barsChart = new Chart(barsEl.getContext('2d'), {
                type: 'bar',
                data: { labels: values.map((_, i) => String(i + 1).padStart(2, '0')), datasets: [{ data: values, backgroundColor: '#f97316', borderRadius: 8, borderSkipped: false, maxBarThickness: 18 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => `Metric ${items[0]?.label || '-'}`,
                                label: (context) => `Value: ${context.parsed.y || 0}`
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                        y: { display: false, beginAtZero: true }
                    }
                }
            });
        }

        function drawMixChart(labels, values) {
            if (!mixEl) return;
            if (mixChart) mixChart.destroy();
            mixChart = new Chart(mixEl.getContext('2d'), {
                type: 'doughnut',
                data: { labels, datasets: [{ data: values, backgroundColor: ['#6366f1', '#f97316', '#10b981', '#e11d48'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
            });
        }

        async function fetchTrendData() {
            const response = await fetch(`${trendEndpoint}?period=${currentPeriod}`, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Failed to fetch');
            return await response.json();
        }

        async function reloadCharts() {
            try {
                const data = await fetchTrendData();
                drawTrendChart(data.labels || [], data.trend || {});
                drawBarsChart(data.bars || initialBars);
                drawMixChart(data.mix?.labels || initialMixLabels, data.mix?.values || initialMixValues);
            } catch (e) {
                if (!trendChart) drawTrendChart(['N/A'], { inquiries: [0], quotations: [0], bookings: [0] });
                if (!barsChart) drawBarsChart(initialBars);
                if (!mixChart) drawMixChart(initialMixLabels, initialMixValues);
            }
        }

        if (periodButtonsWrap) {
            periodButtonsWrap.querySelectorAll('.sa-period-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    currentPeriod = Number(btn.dataset.period || 7);
                    periodButtonsWrap.querySelectorAll('.sa-period-btn').forEach((x) => x.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    await reloadCharts();
                });
            });
        }

        const cards = document.querySelectorAll('.sa-card, .sa-kpi, .sa-mini-kpi, .sa-mini, .sa-critical, .sa-period-btn, .sa-legend-btn');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 40 * idx);
        });

        reloadCharts();
    })();
</script>
@endpush
