@extends('layouts.master')

@section('page_title', 'Manager Dashboard')
@section('page_subtitle', 'Lead team execution, control approval queue, and keep pipeline healthy.')
@section('page_actions')
    <div class="flex items-center gap-2">
        @if($canInquiries)
            <a href="{{ route('inquiries.create') }}" class="btn-primary-sm">
                <i class="fa-solid fa-plus-circle mr-1"></i>New Inquiry
            </a>
        @endif
        @if($canQuotations)
            <a href="{{ route('quotations.index') }}" class="btn-secondary-sm">
                View Quotations
            </a>
        @endif
        <span class="text-xs text-slate-500 dark:text-slate-400">Updated: <x-local-time :value="now()" /></span>
    </div>
@endsection

@section('content')
<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @if(($needsManagerApprovalCount ?? 0) > 0)
        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            {{ number_format((int) $needsManagerApprovalCount) }} quotation(s) are waiting for your approval.
        </div>
    @endif

    <div class="space-y-3">
        <x-index-stats :cards="$statsCards ?? []" class="dashboard-kpi-grid" />

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
            <section class="xl:col-span-8 space-y-3">
                @if($canQuotations)
                    <div class="sa-card p-5">
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
                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-700 dark:bg-amber-900/20">
                                <p class="text-amber-700 dark:text-amber-300">My Approval Queue</p>
                                <p class="mt-1 text-lg font-semibold text-amber-800 dark:text-amber-200">{{ number_format((int) ($needsManagerApprovalCount ?? 0)) }}</p>
                                <p class="text-amber-700 dark:text-amber-300">Need your approval</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                                <p class="text-slate-500 dark:text-slate-400">Step 2: Final Approval</p>
                                <p class="mt-1 text-lg font-semibold text-slate-800 dark:text-slate-100">{{ number_format((int) ($needsDirectorApprovalCount ?? 0)) }}</p>
                                <p class="text-slate-500 dark:text-slate-400">Already has 1 non-creator approval</p>
                            </div>
                        </div>
                    </div>

                    <div class="sa-card p-5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Manager Approval Queue</h2>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Prioritized by validity date</span>
                        </div>
                        <div class="mt-3 space-y-2 text-xs">
                            @forelse($managerApprovalQueue as $quotation)
                                <div class="rounded-xl border border-slate-200 bg-white px-3 py-3 dark:border-slate-700 dark:bg-slate-900">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $quotation->quotation_number }}</p>
                                            <p class="text-slate-500 dark:text-slate-400">{{ $quotation->inquiry?->customer?->name ?? 'Customer not set' }}</p>
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
                                <p class="text-xs text-slate-500 dark:text-slate-400">No quotation is currently waiting for manager approval.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if($canInquiries || $canQuotations || $canItineraries || $canBookings)
                    <div class="sa-card p-5">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Team Funnel</h2>
                        <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                            @forelse($funnel as $stage)
                                <div class="app-card p-3 text-center">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $stage['label'] }}</p>
                                    <p class="mt-1 text-xl font-bold text-slate-800 dark:text-slate-100">
                                        @if(str_contains($stage['label'], 'Conversion'))
                                            {{ number_format((float) $stage['value'], 1) }}%
                                        @else
                                            {{ number_format($stage['value']) }}
                                        @endif
                                    </p>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 dark:text-slate-400">No funnel data available.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </section>

            <aside class="xl:col-span-4 space-y-3">
                @if($canQuotations)
                    <div class="sa-card p-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Quotation Status Snapshot</h3>
                        <div class="mt-3 space-y-2 text-xs">
                            @forelse(($quotationStatusCounts ?? collect())->sortKeys() as $status => $count)
                                <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::headline((string) $status) }}</span>
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ number_format((int) $count) }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 dark:text-slate-400">No quotation status data.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if($canInquiries)
                    <div class="sa-card p-4">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Inquiry Status Distribution</h3>
                        <div class="mt-3 grid grid-cols-1 gap-2 text-xs">
                            @forelse($inquiryByStatus as $status => $total)
                                <div class="app-card px-3 py-2 flex items-center justify-between">
                                    <x-status-badge :status="$status" :label="ucfirst(str_replace('_', ' ', $status))" size="xs" />
                                    <b class="text-slate-700 dark:text-slate-200">{{ number_format($total) }}</b>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500 dark:text-slate-400">No inquiry data found.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if($canInquiries)
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
                                <p class="text-xs text-slate-500 dark:text-slate-400">No upcoming follow-ups.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </aside>
        </div>

        @if($canInquiries)
            <div class="sa-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent Team Inquiries</h2>
                    <a href="{{ route('inquiries.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</a>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse($recentInquiries as $inquiry)
                        <a href="{{ route('inquiries.show', $inquiry) }}" class="block app-card px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/50">
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
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const cards = document.querySelectorAll('.sa-card');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 30 * idx);
        });
    })();
</script>
@endpush

