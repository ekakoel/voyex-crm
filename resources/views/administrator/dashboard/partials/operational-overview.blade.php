@if(!empty($operationalKpis))
    <div>
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.administrator_dashboard.sections.operational_overview') }}</h2>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.administrator_dashboard.operational_kpis.sales_booking_metrics') }}</span>
        </div>
        <div class="dashboard-kpi-grid mt-3 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach($operationalKpis as $card)
                <div class="sa-kpi sa-kpi-sm app-kpi-card" data-progressive-item>
                    <div class="flex items-center justify-between">
                        <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                    </div>
                    <p>{{ $card['label'] }}</p>
                    <b>{{ number_format($card['value']) }}</b>
                </div>
            @endforeach
        </div>
    </div>
@else
    <p class="text-sm text-slate-500 dark:text-slate-400" data-progressive-item>{{ __('ui.administrator_dashboard.operational_kpis.no_access') }}</p>
@endif
