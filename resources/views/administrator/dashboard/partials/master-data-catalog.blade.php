@if(!empty($masterDataKpis))
    <div>
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Master Data Catalog') }}</h2>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Total records for each catalog') }}</span>
        </div>
        <div class="dashboard-kpi-grid mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach($masterDataKpis as $card)
                <a href="{{ isset($card['route']) && Route::has($card['route']) ? route($card['route']) : '#' }}" class="app-kpi-card block rounded-xl border border-slate-200 bg-slate-50 p-3 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-slate-800" data-progressive-item>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-{{ $card['icon'] }} text-slate-500"></i>
                        <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $card['label'] }}</p>
                    </div>
                    <p class="mt-2 text-right text-2xl font-bold text-slate-800 dark:text-slate-100">{{ number_format($card['value']) }}</p>
                </a>
            @endforeach
        </div>
    </div>
@else
    <p class="text-sm text-slate-500 dark:text-slate-400" data-progressive-item>{{ __('No access to master data KPI.') }}</p>
@endif
