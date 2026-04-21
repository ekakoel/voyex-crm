@extends('layouts.master')

@section('content')
@php
    $kpiCards = [];
    if ($canBookings) {
        $kpiCards[] = [
            'label' => 'Bookings (This Month)',
            'value' => $bookingCountMonth ?? 0,
            'icon' => 'calendar-check',
            'color' => 'emerald',
            'breakdown' => $bookingStatusBreakdown ?? [],
        ];
    }
    if ($canQuotations && $canBookings) {
        $kpiCards[] = ['label' => 'Quotations Ready to Book', 'value' => $kpis['ready_to_book'] ?? 0, 'icon' => 'file-circle-check', 'color' => 'amber'];
    }
    if ($canBookings) {
        $kpiCards[] = ['label' => 'Upcoming Trips', 'value' => $kpis['upcoming_trips'] ?? 0, 'icon' => 'plane-departure', 'color' => 'sky'];
        $kpiCards[] = ['label' => 'Trips Pending Closure', 'value' => $kpis['pending_closure'] ?? 0, 'icon' => 'calendar-times', 'color' => 'rose'];
    }
    if ($canInquiries) {
        $kpiCards[] = [
            'label' => 'Inquiries',
            'value' => $inquiryCount ?? 0,
            'icon' => 'circle-question',
            'color' => 'sky',
            'statusCounts' => $inquiryStatusCounts ?? [],
        ];
    }
    if ($canItineraries) {
        $kpiCards[] = [
            'label' => 'Itineraries',
            'value' => $itineraryCount ?? 0,
            'icon' => 'route',
            'color' => 'indigo',
            'statusCounts' => $itineraryStatusCounts ?? [],
        ];
    }
    if ($canQuotations) {
        $kpiCards[] = [
            'label' => 'Quotations',
            'value' => $quotationCount ?? 0,
            'icon' => 'file-invoice-dollar',
            'color' => 'teal',
            'statusCounts' => $quotationStatusCounts ?? [],
        ];
    }

    $statusBadges = [
        'draft' => ['label' => 'D', 'bg' => 'bg-slate-100', 'text' => 'text-slate-700'],
        'processed' => ['label' => 'PR', 'bg' => 'bg-sky-100', 'text' => 'text-sky-700'],
        'pending' => ['label' => 'P', 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
        'approved' => ['label' => 'A', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
        'rejected' => ['label' => 'R', 'bg' => 'bg-rose-100', 'text' => 'text-rose-700'],
        'final' => ['label' => 'F', 'bg' => 'bg-violet-100', 'text' => 'text-violet-700'],
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60" data-progressive-dashboard>
    @section('page_title', 'Reservation Dashboard')
    @section('page_subtitle', 'Manage approved quotations and monitor upcoming travel schedules.')
    @section('page_actions')
        @if($canBookings)
             <a href="{{ route('bookings.index') }}"  class="btn-primary-sm">
                <i class="fa-solid fa-calendar-check mr-2"></i>View All Bookings
            </a>
        @endif
    @endsection

    <div class="space-y-3">
    <div class="dashboard-kpi-grid grid grid-cols-2 gap-3 lg:grid-cols-4" data-progressive-group>
            @foreach($kpiCards as $card)
                <div class="app-card app-kpi-card p-4" data-progressive-item>
                    <div class="flex items-center justify-between h-full relative">
                        <div class="data-card">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $card['label'] }}</p>
                            @if(! empty($card['breakdown'] ?? null))
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($card['breakdown'] as $item)
                                        @php($itemCount = (int) ($item['count'] ?? 0))
                                        @if($itemCount > 0)
                                            <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $item['bg'] ?? 'bg-slate-100' }} {{ $item['text'] ?? 'text-slate-700' }}">
                                                <span>{{ $item['label'] ?? '-' }}</span>
                                                <span>{{ $itemCount }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @elseif(! empty($card['statusCounts'] ?? null))
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($statusBadges as $statusKey => $badge)
                                        @php($statusTotal = (int) ($card['statusCounts'][$statusKey] ?? 0))
                                        @if($statusTotal > 0)
                                            <span class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $badge['bg'] }} {{ $badge['text'] }}">
                                                <span>{{ $badge['label'] }}</span>
                                                <span>{{ $statusTotal }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @elseif(! empty($card['caption'] ?? null))
                                <p class="mt-1 text-[11px] font-medium text-slate-500">{{ $card['caption'] }}</p>
                            @endif
                            <p class="mt-2 text-2xl font-semibold text-gray-900">
                                @if(($card['format'] ?? null) === 'money')
                                    <x-money :amount="$card['value']" />
                                @else
                                    {{ number_format($card['value']) }}
                                @endif
                            </p>
                            
                        </div>
                        <div class="icon-kpi icon-kpi--{{ $card['color'] ?? 'slate' }}">
                            <i class="fa-solid fa-{{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($canBookings)
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3" data-progressive-group>
                <div class="app-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Booking Insights (This Month)') }}</h3>
                    <div class="mt-3 space-y-2 text-xs text-slate-600 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span>{{ __('Top Destination') }}</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-100">{{ $topDestinationSummary ?? '?' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('Avg SLA (Approval ? Booking)') }}</span>
                            <span class="font-semibold text-slate-700 dark:text-slate-100">
                                {{ $slaDaysAvg !== null ? number_format($slaDaysAvg, 1) . ' days' : '?' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('Overdue to Close') }}</span>
                            <span class="font-semibold text-rose-600 dark:text-rose-300">{{ number_format($overdueCloseCount ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                <div class="app-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Weekly Booking Trend') }}</h3>
                    @php($maxWeekly = max(array_column($weeklyBookingTrend ?? [], 'count') ?: [0]))
                    <div class="mt-3 space-y-2">
                        @if(! empty($weeklyBookingTrend ?? []))
                            @foreach($weeklyBookingTrend as $week)
                                @php($pct = $maxWeekly > 0 ? ($week['count'] / $maxWeekly) * 100 : 0)
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <span>{{ $week['label'] }}</span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-100">{{ $week['count'] }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="h-2 rounded-full bg-teal-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('No booking data this period.') }}</p>
                        @endif
                    </div>
                </div>

                <div class="app-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Booking Performance (This Month)') }}</h3>
                    <div class="mt-3 space-y-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('By Staff') }}</p>
                            <div class="mt-2 space-y-1">
                                @if(! empty($bookingByStaff ?? []))
                                    @foreach($bookingByStaff as $row)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-600 dark:text-slate-300">{{ $row->name }}</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-100">{{ $row->total }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('No booking data.') }}</p>
                                @endif
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Top Customers') }}</p>
                            <div class="mt-2 space-y-1">
                                @if(! empty($topCustomers ?? []))
                                    @foreach($topCustomers as $row)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-600 dark:text-slate-300">{{ $row->name }}</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-100"><x-money :amount="$row->total_value" /></span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('No booking data.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($canQuotations && $canBookings)
            <div class="sa-card p-5" data-progressive-group>
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Action Center: Approved Quotations to Book') }}</h2>
                     <a href="{{ route('quotations.index', ['status' => 'approved']) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</a>
                </div>
                <div class="mt-3 flow-root">
                    <div class="-my-2 overflow-x-auto">
                        <div class="inline-block min-w-full py-2 align-middle">
                            <div class="relative overflow-hidden app-card">
                                 <table class="app-table min-w-full table-fixed divide-y divide-slate-200 dark:divide-slate-700">
                                    <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                                        @if(! empty($readyToBookQuotations ?? []))
                                            @foreach($readyToBookQuotations as $quotation)
                                            <tr data-progressive-item>
                                                <td class="whitespace-nowrap px-3 py-2 text-sm font-medium text-slate-900 dark:text-slate-200">
                                                    <a href="{{ route('quotations.show', $quotation) }}"  class="font-bold hover:text-indigo-600">
                                                        {{ $quotation->quotation_number }}
                                                    </a>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                                        {{ $quotation->inquiry?->customer?->name ?? 'N/A' }}
                                                    </p>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-500 dark:text-slate-300">
                                                    <p class="font-semibold text-slate-700 dark:text-slate-200"><x-money :amount="$quotation->final_amount" /></p>
                                                    <p class="text-xs">{{ __('Final Amount') }}</p>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-500 dark:text-slate-300">
                                                     <p class="font-semibold text-slate-700 dark:text-slate-200">{{ \App\Support\DateTimeDisplay::date($quotation->updated_at) }}</p>
                                                     <p class="text-xs">{{ __('Approved Date') }}</p>
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2 text-right text-sm">
                                                    <a href="{{ route('bookings.create', ['quotation_id' => $quotation->id]) }}" class="btn-primary-sm">
                                                        <i class="fa-solid fa-plus-circle mr-2"></i>Create Booking
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="4" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                                    No approved quotations ready for booking.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                 </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($canBookings)
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2" data-progressive-group>
                <div class="sa-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Upcoming Trips (Next 30 Days)') }}</h3>
                    <div class="mt-3 space-y-2">
                        @if(! empty($upcomingTrips ?? []))
                            @foreach($upcomingTrips as $booking)
                                <a href="{{ route('bookings.show', $booking) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800" data-progressive-item>
                                    <div class="flex items-center justify-between">
                                        <p class="font-bold text-slate-700 dark:text-slate-200">{{ $booking->booking_number }}</p>
                                        <span class="font-semibold text-slate-600 dark:text-slate-300">{{ \App\Support\DateTimeDisplay::date($booking->travel_date) }}</span>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400">
                                        Customer: {{ $booking->quotation?->inquiry?->customer?->name ?? 'N/A' }}
                                    </p>
                                </a>
                            @endforeach
                        @else
                            <p class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">{{ __('No upcoming trips.') }}</p>
                        @endif
                    </div>
                </div>
                <div class="sa-card p-4" data-progressive-item>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Recent Active Bookings') }}</h3>
                    <div class="mt-3 space-y-2">
                        @if(! empty($recentBookings ?? []))
                            @foreach($recentBookings as $booking)
                                <a href="{{ route('bookings.show', $booking) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800" data-progressive-item>
                                    <div class="flex items-center justify-between">
                                        <p class="font-bold text-slate-700 dark:text-slate-200">{{ $booking->booking_number }}</p>
                                        <x-status-badge :status="$booking->status" />
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400">
                                        Confirmed on {{ \App\Support\DateTimeDisplay::date($booking->created_at) }}
                                    </p>
                                </a>
                            @endforeach
                        @else
                            <p class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">{{ __('No recent bookings.') }}</p>
                        @endif
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



