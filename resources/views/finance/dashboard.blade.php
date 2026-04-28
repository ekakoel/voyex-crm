@extends('layouts.master')

@section('content')
@php
    $t = 'finance_dashboard';
    $kpiCards = [];
    if ($canInvoices) {
        $kpiCards = [
            ['label' => ui_phrase("$t.cards.monthly_total"), 'value' => $monthlyTotal ?? 0, 'icon' => 'wallet', 'color' => 'emerald', 'format' => 'money'],
            ['label' => ui_phrase("$t.cards.pending_invoices"), 'value' => $pendingInvoices ?? 0, 'icon' => 'file-invoice', 'color' => 'amber'],
            ['label' => ui_phrase("$t.cards.overdue"), 'value' => $overdueInvoices ?? 0, 'icon' => 'triangle-exclamation', 'color' => 'rose'],
        ];
    }
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60" data-progressive-dashboard>
    @section('page_title', ui_phrase('finance_dashboard_page_title'))
    @section('page_subtitle', ui_phrase('finance_dashboard_page_subtitle'))
    @section('page_actions')
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('updated') }} {{ \App\Support\DateTimeDisplay::datetime(now()) }}</span>
    @endsection

    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12" data-progressive-group>
        @if($canInvoices)
        <section class="xl:col-span-8 space-y-3" data-progressive-group>
            <div class="sa-card p-5" data-progressive-item>
                <div class="dashboard-kpi-grid grid grid-cols-2 gap-3 lg:grid-cols-3">
                    @foreach($kpiCards as $card)
                        <div class="sa-kpi app-kpi-card" data-progressive-item>
                            <div class="flex items-center justify-between">
                                <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                                <span class="text-[10px] text-slate-400">{{ ui_phrase('finance_dashboard_live') }}</span>
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

        <aside  class="xl:col-span-4 space-y-3" data-progressive-group>
            <div class="sa-card p-4" data-progressive-item>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ui_phrase('finance_dashboard_recent_invoices_title') }}</h3>
                <div class="mt-3 space-y-2 text-xs">
                    @forelse($recentInvoices as $invoice)
                        <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-900" data-progressive-item>
                            <p class="font-medium text-slate-700 dark:text-slate-200">{{ $invoice->invoice_number }}</p>
                            <p class="text-slate-500 dark:text-slate-400">{{ ui_term((string) $invoice->status) }} • <x-money :amount="$invoice->total_amount" currency="IDR" /></p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('finance_dashboard_recent_invoices_empty') }}</p>
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








