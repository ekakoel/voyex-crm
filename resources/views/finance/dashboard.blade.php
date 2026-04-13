@extends('layouts.master')

@section('content')
@php
    $kpiCards = [];
    if ($canInvoices) {
        $kpiCards = [
            ['label' => 'Monthly Total', 'value' => $monthlyTotal ?? 0, 'icon' => 'wallet', 'color' => 'emerald', 'format' => 'money'],
            ['label' => 'Pending Invoices', 'value' => $pendingInvoices ?? 0, 'icon' => 'file-invoice', 'color' => 'amber'],
            ['label' => 'Overdue', 'value' => $overdueInvoices ?? 0, 'icon' => 'triangle-exclamation', 'color' => 'rose'],
        ];
    }
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    @section('page_title', 'Finance Dashboard')
    @section('page_subtitle', 'Monitor invoices and payment flow.')
    @section('page_actions')
        <span class="text-xs text-slate-500 dark:text-slate-400">Updated: {{ now()->format('d M Y H:i') }}</span>
    @endsection

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        @if($canInvoices)
        <section class="xl:col-span-8 space-y-3">
            <div class="sa-card p-5">
                <div class="dashboard-kpi-grid">
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
                                    {{ number_format($card['value']) }}
                                @endif
                            </b>
                        </div>
                    @endforeach
                </div>
            </div>

        </section>

        <aside  class="xl:col-span-4 space-y-3">
            <div class="sa-card p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent Invoices</h3>
                <div class="mt-3 space-y-2 text-xs">
                    @forelse($recentInvoices as $invoice)
                        <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-900">
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $invoice->invoice_number }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ ucfirst($invoice->status) }} • <x-money :amount="$invoice->total_amount" currency="IDR" /></p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">No recent invoices.</p>
                    @endforelse
                </div>
            </div>
        </aside>
        @endif
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

