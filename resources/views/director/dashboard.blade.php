@extends('layouts.master')

@section('page_title', 'Director Dashboard')
@section('page_subtitle', 'Strategic overview for approvals, pipeline health, and business performance.')
@section('page_actions')
    <span class="text-xs text-slate-500 dark:text-slate-400">Updated: <x-local-time :value="now()" /></span>
@endsection

@section('content')
@php
    $kpiCards = [];
    if ($canBookings) {
        $kpiCards[] = [
            'label' => 'Revenue (MTD)',
            'value' => (float) ($monthlyRevenue ?? 0),
            'caption' => 'Previous month: ' . \App\Support\Currency::format((float) ($previousMonthlyRevenue ?? 0), 'IDR'),
            'icon' => 'wallet',
            'color' => 'emerald',
            'format' => 'money',
        ];
        $kpiCards[] = [
            'label' => 'Revenue Growth',
            'value' => (float) ($revenueGrowthPercent ?? 0),
            'caption' => 'Compared to previous month',
            'icon' => 'chart-line',
            'color' => ((float) ($revenueGrowthPercent ?? 0) >= 0) ? 'indigo' : 'rose',
            'suffix' => '%',
            'decimals' => 2,
        ];
    }
    if ($canQuotations) {
        $kpiCards[] = [
            'label' => 'Director Action Queue',
            'value' => (int) ($needsDirectorApprovalCount ?? 0),
            'caption' => 'Pending approvals for you',
            'icon' => 'file-circle-check',
            'color' => 'amber',
        ];
        $kpiCards[] = [
            'label' => 'Total Quotations',
            'value' => (int) ($totalQuotation ?? 0),
            'caption' => 'All quotation records',
            'icon' => 'file-invoice-dollar',
            'color' => 'sky',
        ];
    }
    if ($canInquiries) {
        $kpiCards[] = [
            'label' => 'Inquiries (MTD)',
            'value' => (int) ($inquiriesThisMonth ?? 0),
            'caption' => 'Total inquiries: ' . number_format((int) ($totalInquiry ?? 0)),
            'icon' => 'inbox',
            'color' => 'violet',
        ];
    }
    if ($canBookings && $canInquiries) {
        $kpiCards[] = [
            'label' => 'Inquiry to Booking',
            'value' => (float) ($conversionRate ?? 0),
            'caption' => 'Bookings: ' . number_format((int) ($totalBooking ?? 0)),
            'icon' => 'bullseye',
            'color' => 'teal',
            'suffix' => '%',
            'decimals' => 2,
        ];
    }
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60" data-progressive-dashboard>
    @if (($needsDirectorApprovalCount ?? 0) > 0)
        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            {{ number_format((int) $needsDirectorApprovalCount) }} quotation(s) are waiting for your approval.
        </div>
    @endif

    <div class="dashboard-kpi-grid" data-progressive-group>
        @foreach($kpiCards as $card)
            <div class="sa-card p-4" data-progressive-item>
                <div class="flex items-center justify-between">
                    <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                    <span class="text-[10px] text-slate-400 uppercase">KPI</span>
                </div>
                <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                <b class="mt-1 block text-xl text-slate-900 dark:text-slate-100">
                    @if(($card['format'] ?? null) === 'money')
                        <x-money :amount="$card['value']" currency="IDR" />
                    @else
                        {{ number_format((float) $card['value'], (int) ($card['decimals'] ?? 0), '.', ',') }}{{ $card['suffix'] ?? '' }}
                    @endif
                </b>
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $card['caption'] ?? '-' }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-12" data-progressive-group>
        <section class="xl:col-span-8 space-y-3" data-progressive-group>
            @if($canQuotations)
                <div class="sa-card p-5" data-progressive-item>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Approval Pipeline</h2>
                        <a href="{{ route('quotations.index', ['status' => 'pending', 'needs_my_approval' => 1]) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">
                            Open My Approval List
                        </a>
                    </div>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3 text-xs">
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                            <p class="text-slate-500 dark:text-slate-400">Step 1: First Approval</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">{{ number_format((int) ($needsReservationApprovalCount ?? 0)) }}</p>
                            <p class="text-slate-500 dark:text-slate-400">No non-creator approval yet</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                            <p class="text-slate-500 dark:text-slate-400">Step 2: Final Approval</p>
                            <p class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">{{ number_format((int) ($needsManagerApprovalCount ?? 0)) }}</p>
                            <p class="text-slate-500 dark:text-slate-400">Already has 1 non-creator approval</p>
                        </div>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20">
                            <p class="text-amber-700 dark:text-amber-300">My Approval Queue</p>
                            <p class="mt-1 text-lg font-semibold text-amber-800 dark:text-amber-200">{{ number_format((int) ($needsDirectorApprovalCount ?? 0)) }}</p>
                            <p class="text-amber-700 dark:text-amber-300">Need your approval</p>
                        </div>
                    </div>
                </div>

                <div class="sa-card p-5" data-progressive-item>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Director Approval Queue</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Prioritized by validity date</span>
                    </div>
                    <div class="mt-3 space-y-2 text-xs">
                        @forelse($pendingApprovals as $quotation)
                            <div class="rounded-xl border border-slate-200 bg-white px-3 py-3 dark:border-slate-700 dark:bg-slate-900" data-progressive-item>
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $quotation->quotation_number }}</p>
                                        <p class="text-slate-500 dark:text-slate-400">
                                            {{ $quotation->inquiry?->customer?->name ?? 'Customer not set' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-slate-800 dark:text-slate-100"><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></p>
                                        <p class="text-slate-500 dark:text-slate-400">Validity: {{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="inline-flex items-center rounded-lg border border-indigo-300 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20">
                                        Review Quotation
                                    </a>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-slate-400">No quotation is currently waiting for director approval.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if($canBookings)
                <div class="sa-card p-5" data-progressive-item>
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Revenue Trend ({{ now()->year }})</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Based on booking creation date</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6 xl:grid-cols-12">
                        @foreach($monthlyData as $row)
                            @php
                                $value = (float) ($row['total'] ?? 0);
                                $barHeight = ($maxMonthlyRevenue ?? 0) > 0 ? max(8, (int) round(($value / $maxMonthlyRevenue) * 70)) : 8;
                            @endphp
                            <div class="rounded-lg border border-slate-200 bg-white px-2 py-2 dark:border-slate-700 dark:bg-slate-900" data-progressive-item>
                                <p class="text-[10px] font-semibold text-slate-600 dark:text-slate-300">{{ $row['label'] }}</p>
                                <div class="mt-2 h-[74px] flex items-end">
                                    <div class="w-full rounded-sm bg-indigo-500/80 dark:bg-indigo-400/70" style="height: {{ $barHeight }}px"></div>
                                </div>
                                <p class="mt-2 text-[10px] text-slate-500 dark:text-slate-400"><x-money :amount="$value" currency="IDR" /></p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        <aside class="xl:col-span-4 space-y-3" data-progressive-group>
            @if($canQuotations)
                <div class="sa-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Quotation Status Snapshot</h3>
                    <div class="mt-3 space-y-2 text-xs">
                        @forelse(($quotationStatusCounts ?? collect())->sortKeys() as $status => $count)
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900" data-progressive-item>
                                <span class="font-medium text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::headline((string) $status) }}</span>
                                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format((int) $count) }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-slate-400">No quotation status data.</p>
                        @endforelse
                    </div>
                </div>
            @endif

            @if($canBookings)
                <div class="sa-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Upcoming Bookings</h3>
                    <div class="mt-3 space-y-2 text-xs">
                        @forelse($upcomingBookings as $booking)
                            <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900" data-progressive-item>
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $booking->booking_number }}</p>
                                <p class="text-slate-500 dark:text-slate-400">{{ optional($booking->travel_date)->format('Y-m-d') ?? '-' }} • {{ \Illuminate\Support\Str::headline((string) ($booking->status ?? '-')) }}</p>
                                <p class="text-slate-500 dark:text-slate-400">{{ $booking->quotation?->inquiry?->customer?->name ?? 'Customer not set' }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-slate-400">No upcoming bookings.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const cards = document.querySelectorAll('.sa-card, .sa-kpi, .sa-mini');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 28 * idx);
        });
    })();
</script>
@endpush
