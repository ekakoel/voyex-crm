@if(!empty($operationalKpis))
    <div>
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Operational Overview</h2>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">Sales & booking metrics</span>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($operationalKpis as $card)
                <div class="sa-kpi sa-kpi-sm" data-progressive-item>
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
    <p class="text-sm text-slate-500 dark:text-slate-400" data-progressive-item>No access to operational KPI.</p>
@endif
