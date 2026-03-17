@extends('layouts.master')

@section('content')
@php
    $kpiCards = [
        ['label' => 'Team Revenue (This Month)', 'value' => $kpis['monthly_revenue'] ?? 0, 'icon' => 'wallet', 'color' => 'emerald', 'format' => 'money'],
        ['label' => 'Conversion Rate', 'value' => $kpis['conversion_rate'] ?? 0, 'icon' => 'chart-line', 'color' => 'indigo', 'suffix' => '%'],
        ['label' => 'Pending Approvals', 'value' => $kpis['pending_quotations'] ?? 0, 'icon' => 'file-circle-check', 'color' => 'amber'],
        ['label' => 'Overdue Follow-ups', 'value' => $kpis['overdue_followups'] ?? 0, 'icon' => 'calendar-times', 'color' => 'rose'],
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Manager Dashboard')
    @section('page_subtitle', 'Monitor team performance and manage daily approvals.')
    @section('page_actions')
        <div class="flex items-center gap-2">
            <a href="{{ route('inquiries.create') }}"  class="btn-primary-sm">
                <i class="fa-solid fa-plus-circle mr-2"></i>New Inquiry
            </a>
             <a href="{{ route('quotations.index') }}"  class="btn-secondary-sm">
                View All Quotations
            </a>
        </div>
    @endsection

    <div class="space-y-3">
        <div class="sa-card p-5">
             <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($kpiCards as $card)
                    <div class="sa-kpi sa-kpi-sm">
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

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="sa-card p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Team Sales Funnel</h2>
                <div class="mt-3 grid grid-cols-3 gap-3">
                    @foreach($funnel as $stage)
                        <div class="rounded-xl border border-slate-200 p-3 text-center dark:border-slate-700">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $stage['label'] }}</p>
                            <p class="mt-1 text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($stage['value']) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="sa-card p-5">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Inquiry Status Distribution</h2>
                 <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
                    @forelse($inquiryByStatus as $status => $total)
                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
                            <x-status-badge :status="$status" :label="ucfirst(str_replace('_', ' ', $status))" size="xs" />
                            <b class="text-slate-700 dark:text-slate-200">{{ number_format($total) }}</b>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No inquiry data found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Action Center: Pending Approvals</h3>
                <div class="mt-3 space-y-2">
                    @forelse($pendingQuotationsList as $quotation)
                        <a href="{{ route('quotations.show', $quotation) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-slate-700 dark:text-slate-200">{{ $quotation->quotation_number }}</p>
                                <x-status-badge :status="$quotation->status" />
                            </div>
                            <p class="text-slate-500 dark:text-slate-400">
                                Customer: {{ $quotation->inquiry?->customer?->name ?? 'N/A' }}
                            </p>
                        </a>
                    @empty
                        <div class="rounded-lg mb-6 border border-dashed border-slate-200 p-4 text-center dark:border-slate-700">
                             <p class="text-xs font-medium text-slate-600 dark:text-slate-300">No pending approvals. Great job!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Action Center: Follow-ups</h3>
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
                                        Overdue
                                    </span>
                                @endif
                            </div>
                            <p class="{{ $isOverdue ? 'text-rose-600 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400' }}">
                                Due {{ optional($followUp->due_date)->format('d M Y') ?? '-' }}
                            </p>
                        </a>
                    @empty
                         <div class="rounded-lg mb-6 border border-dashed border-slate-200 p-4 text-center dark:border-slate-700">
                             <p class="text-xs font-medium text-slate-600 dark:text-slate-300">No upcoming follow-ups.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="sa-card p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent Team Inquiries</h2>
                <a href="{{ route('inquiries.index') }}"  class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</a>
            </div>
            <div class="mt-3 space-y-2">
                @forelse($recentInquiries as $inquiry)
                    <a href="{{ route('inquiries.show', $inquiry) }}"  class="block rounded-xl border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800/50">
                        <div class="flex items-center justify-between text-xs">
                            <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $inquiry->inquiry_number }}</p>
                            <x-status-badge :status="$inquiry->status" />
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400">Assigned to: {{ $inquiry->assignedUser->name ?? 'N/A' }}</span>
                            <span class="text-slate-500 dark:text-slate-400">{{ $inquiry->created_at->format('d M Y') }}</span>
                        </div>
                    </a>
                @empty
                    <p class="text-center text-xs text-slate-500 dark:text-slate-400">No recent inquiries found.</p>
                @endforelse
            </div>
        </div>
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


