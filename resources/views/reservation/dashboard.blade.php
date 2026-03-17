@extends('layouts.master')

@section('content')
@php
    $kpiCards = [
        ['label' => 'Quotations Ready to Book', 'value' => $kpis['ready_to_book'] ?? 0, 'icon' => 'file-circle-check', 'color' => 'amber'],
        ['label' => 'Upcoming Trips', 'value' => $kpis['upcoming_trips'] ?? 0, 'icon' => 'plane-departure', 'color' => 'sky'],
        ['label' => 'Trips Pending Closure', 'value' => $kpis['pending_closure'] ?? 0, 'icon' => 'calendar-times', 'color' => 'rose'],
        ['label' => 'Total Booked Value', 'value' => $kpis['total_booked_value'] ?? 0, 'icon' => 'wallet', 'color' => 'emerald', 'format' => 'money'],
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Reservation Dashboard')
    @section('page_subtitle', 'Manage approved quotations and monitor upcoming travel schedules.')
    @section('page_actions')
         <a href="{{ route('bookings.index') }}"  class="btn-primary-sm">
            <i class="fa-solid fa-calendar-check mr-2"></i>View All Bookings
        </a>
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
                                {{ number_format($card['value']) }}
                            @endif
                        </b>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sa-card p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Action Center: Approved Quotations to Book</h2>
                 <a href="{{ route('quotations.index', ['status' => 'approved']) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</a>
            </div>
            <div class="mt-3 flow-root">
                <div class="-my-2 overflow-x-auto">
                    <div class="inline-block min-w-full py-2 align-middle">
                        <div class="relative overflow-hidden app-card">
                             <table class="app-table min-w-full table-fixed divide-y divide-slate-200 dark:divide-slate-700">
                                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-900">
                                    @forelse($readyToBookQuotations as $quotation)
                                    <tr>
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
                                            <p class="text-xs">Final Amount</p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 text-sm text-slate-500 dark:text-slate-300">
                                             <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $quotation->updated_at->format('d M, Y') }}</p>
                                             <p class="text-xs">Approved Date</p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2 text-right text-sm">
                                            <a href="{{ route('bookings.create', ['quotation_id' => $quotation->id]) }}" class="btn-primary-sm">
                                                <i class="fa-solid fa-plus-circle mr-2"></i>Create Booking
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                                            No approved quotations ready for booking.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Upcoming Trips (Next 30 Days)</h3>
                <div class="mt-3 space-y-2">
                    @forelse($upcomingTrips as $booking)
                        <a href="{{ route('bookings.show', $booking) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-slate-700 dark:text-slate-200">{{ $booking->booking_number }}</p>
                                <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $booking->travel_date->format('d M, Y') }}</span>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400">
                                Customer: {{ $booking->quotation?->inquiry?->customer?->name ?? 'N/A' }}
                            </p>
                        </a>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">No upcoming trips.</p>
                    @endforelse
                </div>
            </div>
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recently Confirmed Bookings</h3>
                <div class="mt-3 space-y-2">
                    @forelse($recentBookings as $booking)
                        <a href="{{ route('bookings.show', $booking) }}"  class="block rounded-lg bg-slate-50 px-3 py-2 text-xs hover:bg-slate-100 dark:bg-slate-800/50 dark:hover:bg-slate-800">
                            <div class="flex items-center justify-between">
                                <p class="font-bold text-slate-700 dark:text-slate-200">{{ $booking->booking_number }}</p>
                                <x-status-badge :status="$booking->status" />
                            </div>
                            <p class="text-slate-500 dark:text-slate-400">
                                Confirmed on {{ $booking->created_at->format('d M, Y') }}
                            </p>
                        </a>
                    @empty
                        <p class="py-4 text-center text-xs text-slate-500 dark:text-slate-400">No recent bookings.</p>
                    @endforelse
                </div>
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


