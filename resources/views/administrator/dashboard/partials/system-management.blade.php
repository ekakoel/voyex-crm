@if(!empty($systemKpis))
    <div>
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.administrator_dashboard.sections.system_management') }}</h2>
            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.administrator_dashboard.system_kpis.users_roles_modules') }}</span>
        </div>
        <div class="dashboard-kpi-grid mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($systemKpis as $card)
                <a href="{{ isset($card['route']) && Route::has($card['route']) ? route($card['route']) : '#' }}" class="sa-kpi sa-kpi-sm app-kpi-card" data-progressive-item>
                    <div class="flex items-center justify-between">
                        <span class="sa-dot sa-{{ $card['color'] }}"><i class="fa-solid fa-{{ $card['icon'] }}"></i></span>
                    </div>
                    <p>{{ $card['label'] }}</p>
                    <b>{{ number_format($card['value']) }}</b>
                </a>
            @endforeach
        </div>
    </div>
@else
    <p class="text-sm text-slate-500 dark:text-slate-400" data-progressive-item>{{ __('ui.administrator_dashboard.system_kpis.no_access') }}</p>
@endif
