@extends('layouts.master')

@section('page_title', ui_phrase('Reservation Dashboard'))
@section('page_subtitle', ($canBookings ?? false)
    ? ui_phrase('Track assigned inquiries, quotation progress, booking readiness, and upcoming trips.')
    : ui_phrase('Track assigned inquiries and quotation progress from one reservation workspace.'))
@section('page_actions')
    <div class="flex flex-wrap items-center gap-2">
        @if($canInquiries)
            <a href="{{ route('inquiries.index') }}" class="btn-secondary-sm">
                <i class="fa-solid fa-inbox mr-1"></i>{{ ui_phrase('My Inquiries') }}
            </a>
        @endif
        @if($canQuotations)
            <a href="{{ route('quotations.index') }}" class="btn-secondary-sm">
                <i class="fa-solid fa-file-invoice-dollar mr-1"></i>{{ ui_phrase('Quotations') }}
            </a>
        @endif
        @if($canBookings)
            <a href="{{ route('bookings.index') }}" class="btn-primary-sm">
                <i class="fa-solid fa-calendar-check mr-1"></i>{{ ui_phrase('Bookings') }}
            </a>
        @endif
    </div>
@endsection

@section('content')
@php
    $maxPipeline = max(array_map(fn ($stage) => (int) ($stage['value'] ?? 0), $pipelineStages ?? []) ?: [0]);
    $maxWorkbench = max(array_map(fn ($row) => (int) ($row['value'] ?? 0), $quotationWorkbench ?? []) ?: [0]);
    $toneClasses = [
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300',
        'rose' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
        'sky' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
        'slate' => 'border-slate-200 bg-white text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
        'teal' => 'border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-700 dark:bg-teal-900/20 dark:text-teal-300',
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60" data-progressive-dashboard>
    @if(($overdueInquiryCount ?? 0) > 0 || (($canBookings ?? false) && ($pendingClosureCount ?? 0) > 0))
        <div class="mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
            @if($canBookings ?? false)
                {{ ui_phrase('Attention needed: :inquiries overdue inquiry(s), :bookings booking(s) pending closure.', ['inquiries' => number_format((int) ($overdueInquiryCount ?? 0)), 'bookings' => number_format((int) ($pendingClosureCount ?? 0))]) }}
            @else
                {{ ui_phrase('Attention needed: :inquiries overdue inquiry(s).', ['inquiries' => number_format((int) ($overdueInquiryCount ?? 0))]) }}
            @endif
        </div>
    @endif

    <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4" data-progressive-group>
            @foreach($statsCards as $card)
                <div class="app-card app-kpi-card p-4" data-progressive-item>
                    <div class="flex h-full items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $card['label'] }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((int) ($card['value'] ?? 0)) }}</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $card['caption'] ?? '-' }}</p>
                        </div>
                        <div class="icon-kpi icon-kpi--{{ $card['color'] ?? 'slate' }}">
                            <i class="fa-solid fa-{{ $card['icon'] ?? 'chart-pie' }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
            <section class="space-y-3 xl:col-span-8" data-progressive-group>
                <div class="sa-card p-5" data-progressive-item>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Reservation Funnel') }}</h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Assigned work moving from inquiry to trip operation.') }}</p>
                        </div>
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ ui_phrase('Pipeline') }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-5">
                        @foreach($pipelineStages as $stage)
                            @php
                                $stageValue = (int) ($stage['value'] ?? 0);
                                $barWidth = $maxPipeline > 0 ? max(8, (int) round(($stageValue / $maxPipeline) * 100)) : 0;
                                $tone = $stage['color'] ?? 'slate';
                            @endphp
                            <div class="rounded-xl border p-3 {{ $toneClasses[$tone] ?? $toneClasses['slate'] }}" data-progressive-item>
                                <div class="flex items-center justify-between gap-2">
                                    <i class="fa-solid fa-{{ $stage['icon'] ?? 'circle' }}"></i>
                                    <span class="text-lg font-bold">{{ number_format($stageValue) }}</span>
                                </div>
                                <p class="mt-2 min-h-[32px] text-xs font-semibold">{{ $stage['label'] }}</p>
                                <div class="mt-3 h-1.5 rounded-full bg-white/70 dark:bg-slate-800">
                                    <div class="h-1.5 rounded-full bg-current" style="width: {{ $barWidth }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($canQuotations)
                    <div class="sa-card p-5" data-progressive-item>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Quotation Workbench') }}</h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Where your quotation workload currently sits.') }}</p>
                            </div>
                            <a href="{{ route('quotations.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">{{ ui_phrase('Open Quotation List') }}</a>
                        </div>
                        <div class="mt-4 space-y-3">
                            @foreach($quotationWorkbench as $row)
                                @php
                                    $value = (int) ($row['value'] ?? 0);
                                    $width = $maxWorkbench > 0 ? max(6, (int) round(($value / $maxWorkbench) * 100)) : 0;
                                    $tone = $row['color'] ?? 'slate';
                                @endphp
                                <div data-progressive-item>
                                    <div class="mb-1 flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $row['label'] }}</span>
                                        <span class="text-slate-500 dark:text-slate-400">{{ number_format($value) }}</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-200 dark:bg-slate-800">
                                        <div class="h-2.5 rounded-full {{ $tone === 'emerald' ? 'bg-emerald-500' : ($tone === 'amber' ? 'bg-amber-500' : ($tone === 'sky' ? 'bg-sky-500' : 'bg-slate-500')) }}" style="width: {{ $width }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($canBookings)
                    <div class="sa-card p-5" data-progressive-item>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Trips Next 14 Days') }}</h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Travel-date distribution for bookings in your pipeline.') }}</p>
                            </div>
                            <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                                <p>{{ ui_phrase('Bookings MTD') }}: <span class="font-semibold text-slate-800 dark:text-slate-100">{{ number_format((int) ($monthlyBookingCount ?? 0)) }}</span></p>
                                <p>{{ ui_phrase('Value MTD') }}: <span class="font-semibold text-slate-800 dark:text-slate-100"><x-money :amount="$monthlyBookedValue ?? 0" currency="IDR" /></span></p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                            @foreach($travelBars as $day)
                                <div class="flex min-h-[116px] w-16 shrink-0 flex-col items-center justify-end gap-1 rounded-lg border border-slate-200 bg-white px-1 py-2 dark:border-slate-700 dark:bg-slate-900" data-progressive-item>
                                    <span class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ $day['count'] }}</span>
                                    <div class="flex h-[82px] w-full items-end justify-center">
                                        <div class="w-4 rounded-t bg-sky-500 dark:bg-sky-400" style="height: {{ (int) ($day['height'] ?? 10) }}px"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400">{{ $day['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <aside class="space-y-3 xl:col-span-4" data-progressive-group>
                @if($canInquiries)
                    <div class="sa-card p-4" data-progressive-item>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Deadline Watch') }}</h3>
                            <a href="{{ route('inquiries.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">{{ ui_phrase('View all') }}</a>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse($deadlineWatch as $inquiry)
                                @php
                                    $deadlineLabel = \App\Support\InquiryDeadlineReminder::reminderLabel($inquiry->deadline);
                                    $daysUntil = \App\Support\InquiryDeadlineReminder::daysUntilDeadline($inquiry->deadline);
                                    $isUrgent = $daysUntil !== null && $daysUntil <= 0;
                                @endphp
                                <a href="{{ route('inquiries.show', $inquiry) }}" class="block rounded-xl border px-3 py-2 text-xs {{ $isUrgent ? 'border-rose-200 bg-rose-50 dark:border-rose-700 dark:bg-rose-900/20' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900' }}" data-progressive-item>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $inquiry->inquiry_number }}</p>
                                            <p class="text-slate-500 dark:text-slate-400">{{ $inquiry->customer?->name ?? $inquiry->customer?->company_name ?? '-' }}</p>
                                        </div>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isUrgent ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">{{ $deadlineLabel }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between gap-2 text-slate-500 dark:text-slate-400">
                                        <span>{{ ui_phrase('Priority') }}: {{ ui_phrase((string) ($inquiry->priority ?? '-')) }}</span>
                                        <span>{{ optional($inquiry->deadline)->format('Y-m-d') ?? '-' }}</span>
                                    </div>
                                </a>
                            @empty
                                <p class="rounded-xl border border-slate-200 bg-white px-3 py-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">{{ ui_phrase('No deadline reminders right now.') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if($canQuotations)
                    <div class="sa-card p-4" data-progressive-item>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Ready to Book') }}</h3>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ ui_phrase('Customer approved') }}</span>
                        </div>
                        <div class="mt-3 space-y-2">
                            @forelse($readyToBookQuotations as $quotation)
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs dark:border-emerald-700 dark:bg-emerald-900/20" data-progressive-item>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <a href="{{ route('quotations.show', $quotation) }}" class="font-semibold text-emerald-800 hover:underline dark:text-emerald-200">{{ $quotation->quotation_number ?: $quotation->order_number }}</a>
                                            <p class="text-emerald-700 dark:text-emerald-300">{{ $quotation->inquiry?->customer?->name ?? '-' }}</p>
                                        </div>
                                        <p class="font-semibold text-emerald-800 dark:text-emerald-200"><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></p>
                                    </div>
                                    @if($canBookings)
                                        <div class="mt-2">
                                            <a href="{{ route('bookings.create', ['quotation_id' => $quotation->id]) }}" class="btn-primary-sm">
                                                <i class="fa-solid fa-plus-circle mr-1"></i>{{ ui_phrase('Create Booking') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="rounded-xl border border-slate-200 bg-white px-3 py-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">{{ ui_phrase('No customer-approved quotation is waiting for booking.') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if($canBookings)
                    <div class="sa-card p-4" data-progressive-item>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Upcoming Trips') }}</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($upcomingTrips as $booking)
                                <a href="{{ route('bookings.show', $booking) }}" class="block rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800" data-progressive-item>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $booking->booking_number }}</p>
                                            <p class="text-slate-500 dark:text-slate-400">{{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}</p>
                                        </div>
                                        <x-ui.status-badge :status="(string) ($booking->status ?? '-')" size="xs" />
                                    </div>
                                    <p class="mt-2 text-slate-500 dark:text-slate-400">{{ ui_phrase('Travel Date') }}: {{ optional($booking->travel_date)->format('Y-m-d') ?? '-' }}</p>
                                </a>
                            @empty
                                <p class="rounded-xl border border-slate-200 bg-white px-3 py-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">{{ ui_phrase('No upcoming trips in the next 14 days.') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </aside>
        </div>

        @if($canInquiries)
            <div class="sa-card p-5" data-progressive-group>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('Assigned Inquiry Board') }}</h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Newest updates first') }}</span>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($recentInquiries as $inquiry)
                        <a href="{{ route('inquiries.show', $inquiry) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800" data-progressive-item>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $inquiry->inquiry_number }}</p>
                                    <p class="text-slate-500 dark:text-slate-400">{{ $inquiry->customer?->name ?? $inquiry->customer?->company_name ?? '-' }}</p>
                                </div>
                                <x-ui.status-badge :status="(string) ($inquiry->status ?? '-')" size="xs" />
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-2 text-slate-500 dark:text-slate-400">
                                <span>{{ ui_phrase('Priority') }}: {{ ui_phrase((string) ($inquiry->priority ?? '-')) }}</span>
                                <span>{{ ui_phrase('Deadline') }}: {{ optional($inquiry->deadline)->format('Y-m-d') ?? '-' }}</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('No assigned inquiry updates.') }}</p>
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
        const cards = document.querySelectorAll('.sa-card, .app-kpi-card');
        cards.forEach((el, idx) => {
            el.classList.add('sa-reveal');
            setTimeout(() => el.classList.add('is-in'), 26 * idx);
        });
    })();
</script>
@endpush
