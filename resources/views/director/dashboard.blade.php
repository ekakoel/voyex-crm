@extends('layouts.master')

@section('content')
@php
    $kpiCards = [
        ['label' => 'Monthly Revenue', 'value' => $monthlyRevenue ?? 0, 'icon' => 'wallet', 'color' => 'emerald', 'format' => 'money'],
        ['label' => 'Conversion Rate', 'value' => $conversionRate ?? 0, 'icon' => 'chart-line', 'color' => 'indigo', 'suffix' => '%'],
        ['label' => 'Pending Approvals', 'value' => count($pendingApprovals ?? []), 'icon' => 'file-circle-check', 'color' => 'amber'],
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Director Dashboard')
    @section('page_subtitle', 'Ringkasan strategis untuk approval dan performa bisnis.')
    @section('page_actions')
        <span class="text-xs text-slate-500 dark:text-slate-400">Updated: {{ now()->format('d M Y H:i') }}</span>
    @endsection

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        <section class="xl:col-span-8 space-y-3">
            <div class="sa-card p-5">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($kpiCards as $card)
                        <div class="sa-kpi">
                            <div class="flex items-center justify-between">
                                <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                                <span class="text-[10px] text-slate-400">live</span>
                            </div>
                            <p>{{ $card['label'] }}</p>
                            <b>
                                @if(($card['format'] ?? null) === 'money')
                                    <x-money :amount="$card['value']" currency="IDR" />
                                @else
                                    {{ number_format($card['value']) }}{{ $card['suffix'] ?? '' }}
                                @endif
                            </b>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sa-card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pending Approvals</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Quotation approvals</span>
                </div>
                <div class="mt-3 space-y-2 text-xs">
                    @forelse($pendingApprovals as $quotation)
                        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $quotation->quotation_number }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ ucfirst($quotation->status) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No pending approvals.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <aside  class="xl:col-span-4 space-y-3">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Upcoming Bookings</h3>
                <div class="mt-3 space-y-2 text-xs">
                    @forelse($upcomingBookings as $booking)
                        <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-900">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $booking->booking_number }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ optional($booking->travel_date)->format('Y-m-d') ?? '-' }} • {{ ucfirst($booking->status ?? '-') }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No upcoming bookings.</p>
                    @endforelse
                </div>
            </div>
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
            setTimeout(() => el.classList.add('is-in'), 35 * idx);
        });
    })();
</script>
@endpush

