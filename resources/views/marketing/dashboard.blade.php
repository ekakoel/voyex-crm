@extends('layouts.master')

@section('content')
@php
    $t = 'marketing_dashboard';
    $kpiCards = [];
    if ($canBookings) {
        $kpiCards[] = ['label' => ui_phrase("$t.cards.my_revenue_this_month"), 'value' => $kpis['monthly_revenue'] ?? 0, 'icon' => 'wallet', 'color' => 'emerald', 'format' => 'money'];
    }
    if ($canBookings && $canInquiries) {
        $kpiCards[] = ['label' => ui_phrase("$t.cards.my_conversion_rate"), 'value' => $kpis['conversion_rate'] ?? 0, 'icon' => 'chart-line', 'color' => 'indigo', 'suffix' => '%'];
    }
    if ($canInquiries) {
        $kpiCards[] = ['label' => ui_phrase("$t.cards.my_active_inquiries"), 'value' => $kpis['active_inquiries'] ?? 0, 'icon' => 'circle-question', 'color' => 'sky'];
        $kpiCards[] = ['label' => ui_phrase("$t.cards.overdue_followups"), 'value' => $kpis['overdue_followups'] ?? 0, 'icon' => 'calendar-times', 'color' => 'rose'];
    }
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', ui_phrase('marketing_dashboard_page_title'))
    @section('page_subtitle', ui_phrase('marketing_dashboard_page_subtitle'))
    @section('page_actions')
        <div class="flex items-center gap-2">
            @if($canCustomers)
                <a href="{{ route('customers.create') }}"  class="btn-secondary-sm">
                    <i class="fa-solid fa-user-plus mr-2"></i>{{ ui_phrase('new_customer') }}
                </a>
            @endif
            @if($canInquiries)
                <a href="{{ route('inquiries.create') }}"  class="btn-primary-sm">
                    <i class="fa-solid fa-plus-circle mr-2"></i>{{ ui_phrase('new_inquiry') }}
                </a>
            @endif
        </div>
    @endsection

    <div class="space-y-3">
        <div class="sa-card p-5">
             <div class="dashboard-kpi-grid grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach($kpiCards as $card)
                    <div class="sa-kpi sa-kpi-sm app-kpi-card">
                        <div class="flex items-center justify-between">
                            <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                        </div>
                        <p>{{ $card['label'] }}</p>
                        <b>
                            @if(($card['format'] ?? null) === 'money')
                                <x-money :amount="$card['value']" />
                            @else
                                {{ number_format($card['value']) }}{{ $card['suffix'] ?? '' }}
                            @endif
                        </b>
                    </div>
                @endforeach
            </div>
        </div>

        @if($canInquiries || $canQuotations || $canBookings)
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div class="sa-card p-5">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('marketing_dashboard_sales_funnel_title') }}</h2>
                    <div class="mt-3 grid grid-cols-3 gap-3">
                        @forelse($funnel as $stage)
                            <div class="rounded-xl border border-slate-200 p-3 text-center dark:border-slate-700">
                                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $stage['label'] }}</p>
                                <p class="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($stage['value']) }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('marketing_dashboard_sales_funnel_empty') }}</p>
                        @endforelse
                    </div>
                </div>
                @if($canInquiries)
                    <div class="sa-card p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('marketing_dashboard_inquiry_status_title') }}</h2>
                         <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
                            @forelse($inquiryByStatus as $status => $total)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    <x-status-badge :status="$status" :label="ui_term((string) $status)" size="xs" />
                                    <b class="text-slate-700 dark:text-slate-200">{{ number_format($total) }}</b>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('marketing_dashboard_inquiry_status_empty') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($canInquiries)
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div class="sa-card p-4">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('marketing_dashboard_followups_title') }}</h3>
                    <div class="mt-3 space-y-2">
                        @forelse($upcomingFollowUps as $followUp)
                             @php
                                $isOverdue = $followUp->due_date->isPast();
                            @endphp
                            <a href="{{ route('inquiries.show', $followUp->inquiry_id) }}" 
                               class="block rounded-lg px-3 py-2 text-xs {{ $isOverdue ? 'bg-rose-50 dark:bg-rose-900/20' : 'bg-slate-50 dark:bg-slate-800/50' }} hover:bg-slate-100 dark:hover:bg-slate-800">
                                <div class="flex items-center justify-between">
                                    <p class="font-bold text-slate-700 dark:text-slate-200">{{ $followUp->inquiry->inquiry_number ?? '-' }}</p>
                                    @if($isOverdue)
                                         <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-1 text-[11px] font-semibold text-rose-600 dark:bg-rose-900/50 dark:text-rose-300">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            {{ ui_phrase('overdue') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="{{ $isOverdue ? 'text-rose-600 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400' }}">
                                    {{ ui_phrase('due') }} {{ \App\Support\DateTimeDisplay::date(optional($followUp->due_date)) }}
                                </p>
                            </a>
                        @empty
                             <div class="rounded-lg mb-6 border border-dashed border-slate-200 p-4 text-center dark:border-slate-700">
                                 <p class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ ui_phrase('marketing_dashboard_followups_empty') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                 <div class="sa-card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('marketing_dashboard_recent_inquiries_title') }}</h2>
                        <a href="{{ route('inquiries.index') }}"  class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">{{ ui_phrase('view_all') }}</a>
                    </div>
                    <div class="mt-3 space-y-2">
                        @forelse($recentInquiries as $inquiry)
                            <a href="{{ route('inquiries.show', $inquiry) }}"  class="block rounded-xl border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800/50">
                                <div class="flex items-center justify-between text-xs">
                                    <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $inquiry->inquiry_number }}</p>
                                    <x-status-badge :status="$inquiry->status" />
                                </div>
                                 <div class="mt-1 flex items-center justify-between text-xs">
                                    <span class="text-slate-500 dark:text-slate-400">{{ ui_phrase('customer_label') }} {{ $inquiry->customer->name ?? ui_phrase('na') }}</span>
                                    <span class="text-slate-500 dark:text-slate-400">{{ \App\Support\DateTimeDisplay::date($inquiry->created_at) }}</span>
                                </div>
                            </a>
                        @empty
                            <p class="text-center text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('marketing_dashboard_recent_inquiries_empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const cards = document.querySelectorAll('.sa-card, .sa-kpi');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 35 * idx);
        });
    })();
</script>
@endpush



