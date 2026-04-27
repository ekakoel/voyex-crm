@extends('layouts.master')

@section('content')
@php
    $statusBlocks = [
        __('ui.superadmin.status.inquiry') => $inquiryByStatus,
        __('ui.superadmin.status.quotation') => $quotationByStatus,
    ];
    if ($bookingsModuleEnabled ?? false) {
        $statusBlocks[__('ui.superadmin.status.booking')] = $bookingByStatus;
    }

    $legacyGroupNameMap = [
        'CRM & Sales' => 'ui.superadmin.group.crm_sales',
        'Product & Reservation' => 'ui.superadmin.group.product_reservation',
        'System Administration' => 'ui.superadmin.group.system_administration',
        'Other Modules' => 'ui.superadmin.group.other_modules',
    ];

    $legacyMetricLabelMap = [
        'Customers' => 'ui.superadmin.metric.customers',
        'Pending Follow-ups' => 'ui.superadmin.metric.pending_followups',
        'Itineraries' => 'ui.superadmin.metric.itineraries',
        'Expiring (7D)' => 'ui.superadmin.metric.expiring_7d',
        'Upcoming (7D)' => 'ui.superadmin.metric.upcoming_7d',
        'Invoices' => 'ui.superadmin.metric.invoices',
        'Vendors' => 'ui.superadmin.metric.vendors',
        'Destinations' => 'ui.superadmin.metric.destinations',
        'Activities' => 'ui.superadmin.metric.activities',
        'Food & Beverage' => 'ui.superadmin.metric.food_beverage',
        'Hotels' => 'ui.superadmin.metric.hotels',
        'Airports' => 'ui.superadmin.metric.airports',
        'Transports' => 'ui.superadmin.metric.transports',
        'Attractions' => 'ui.superadmin.metric.attractions',
        'Disabled Modules' => 'ui.superadmin.metric.disabled_modules',
        'Roles' => 'ui.superadmin.metric.roles',
        'Users' => 'ui.superadmin.metric.users',
        'Currencies' => 'ui.superadmin.metric.currencies',
        'Data Volume' => 'ui.superadmin.metric.data_volume',
    ];

    $translateSuperadminKeyOrText = function (?string $value) use ($legacyGroupNameMap, $legacyMetricLabelMap): string {
        $value = trim((string) $value);
        if ($value === '') {
            return __('ui.superadmin.common.na');
        }

        if (isset($legacyGroupNameMap[$value])) {
            return __($legacyGroupNameMap[$value]);
        }

        if (isset($legacyMetricLabelMap[$value])) {
            return __($legacyMetricLabelMap[$value]);
        }

        return __($value);
    };

    $translateStatus = function (?string $status): string {
        $status = (string) $status;
        $key = 'ui.superadmin.status_value.'.strtolower($status);
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return __(\Illuminate\Support\Str::headline($status));
    };

    $normalizeActivityToken = function (?string $value): string {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return '';
        }

        if (str_contains($value, '\\')) {
            $value = \Illuminate\Support\Str::afterLast($value, '\\');
        }

        $value = \Illuminate\Support\Str::snake($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', (string) $value) ?? '';
        $value = preg_replace('/_+/', '_', (string) $value) ?? '';

        return trim((string) $value, '_');
    };

    $translateActivityToken = function (string $group, ?string $value) use ($normalizeActivityToken): string {
        $normalized = $normalizeActivityToken($value);
        if ($normalized === '') {
            return __('ui.common.unknown');
        }

        $key = 'ui.superadmin.shared.'.$group.'.'.$normalized;
        $translated = __($key);
        if ($translated !== $key) {
            return $translated;
        }

        return __(\Illuminate\Support\Str::headline($normalized));
    };

    $translateActivityEntity = fn (?string $value): string => function_exists('ui_entity')
        ? ui_entity($value)
        : $translateActivityToken('entities', $value);
    $translateActivityAction = fn (?string $value): string => function_exists('ui_action')
        ? ui_action($value)
        : $translateActivityToken('actions', $value);
@endphp

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
        <div class="xl:col-span-9">
            <div class="sa-card p-5">
                @section('page_title', __('ui.superadmin.page_title'))
                @section('page_subtitle', __('ui.superadmin.page_subtitle'))
                @section('page_actions')
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.last_loaded') }} {{ \App\Support\DateTimeDisplay::datetime(now()) }}</span>
                @endsection

                <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-12">
                    <div class="xl:col-span-5 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.action_center.title') }}</h2>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.action_center.current_priority') }}</span>
                        </div>
                        <div class="space-y-2">
                            @foreach(($actionCenter ?? []) as $item)
                                @php
                                    $badgeClass = match ($item['severity'] ?? 'info') {
                                        'critical' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
                                        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                                        'ok' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                                        default => 'bg-sky-100 text-sky-700 dark:bg-sky-900/20 dark:text-sky-300',
                                    };
                                @endphp
                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                    <div class="flex items-start justify-between gap-5">
                                        <div>
                                            <p class="text-xs font-medium text-slate-700 dark:text-slate-200">{{ isset($item['label_key']) ? __((string) $item['label_key']) : $translateSuperadminKeyOrText((string) ($item['label'] ?? '')) }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ isset($item['hint_key']) ? __((string) $item['hint_key']) : $translateSuperadminKeyOrText((string) ($item['hint'] ?? '')) }}</p>
                                        </div>
                                        <div class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $badgeClass }}">
                                            {{ number_format((int) ($item['value'] ?? 0)) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="xl:col-span-7 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.funnel.title') }}</h2>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.funnel.subtitle') }}</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                            @foreach(($funnel ?? []) as $stage)
                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ isset($stage['label_key']) ? __((string) $stage['label_key']) : $translateSuperadminKeyOrText((string) ($stage['label'] ?? '')) }}</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((int) ($stage['value'] ?? 0)) }}</p>
                                    @if(!is_null($stage['conversion']))
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.funnel.conv') }} {{ number_format((float) $stage['conversion'], 1) }}%</p>
                                    @else
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ __('ui.superadmin.funnel.baseline') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="sa-card mt-5 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.module_control.title') }}</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.module_control.subtitle') }}</span>
                </div>
                <div class="space-y-4">
                    @forelse(($moduleGroups ?? []) as $group)
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">{{ isset($group['name_key']) ? __((string) $group['name_key']) : $translateSuperadminKeyOrText((string) ($group['name'] ?? '')) }}</h3>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.module_control.module_count', ['count' => count($group['modules'] ?? [])]) }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                                @foreach(($group['modules'] ?? []) as $module)
                                    @php
                                        $healthClass = match ($module['health'] ?? 'healthy') {
                                            'critical' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300',
                                            'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
                                            'inactive' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                                            default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
                                        };
                                    @endphp
                                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                        <div class="flex items-start justify-between gap-5">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                                    @php
                                                        $moduleNameKey = $module['name_key'] ?? 'ui.superadmin.module_names.'.(string) ($module['key'] ?? '');
                                                        $moduleNameTranslated = __((string) $moduleNameKey);
                                                        if ($moduleNameTranslated === $moduleNameKey) {
                                                            $moduleNameTranslated = $translateSuperadminKeyOrText((string) ($module['name'] ?? $module['key'] ?? ''));
                                                        }
                                                    @endphp
                                                    <i class="fa-solid fa-{{ $module['icon'] ?? 'puzzle-piece' }} mr-1 w-4 text-center"></i>{{ $moduleNameTranslated }}
                                                </p>
                                                <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $module['key'] }}</p>
                                            </div>
                                            <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $healthClass }}">
                                                {{ __('ui.superadmin.health.' . (string) ($module['health'] ?? 'healthy')) }}
                                            </span>
                                        </div>

                                        <div class="mt-5 grid grid-cols-2 gap-2 text-xs">
                                            <div class="rounded-lg bg-slate-50 px-2 py-2 dark:bg-slate-800">
                                                <p class="text-slate-500 dark:text-slate-400">{{ isset($module['metric']['label_key']) ? __((string) $module['metric']['label_key']) : $translateSuperadminKeyOrText((string) ($module['metric']['label'] ?? 'Data Volume')) }}</p>
                                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format((int) ($module['metric']['value'] ?? 0)) }}</p>
                                            </div>
                                            <div class="rounded-lg bg-slate-50 px-2 py-2 dark:bg-slate-800">
                                                <p class="text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.module_control.role_coverage') }}</p>
                                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format((int) ($module['role_coverage'] ?? 0)) }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-5 flex items-center gap-2">
                                            @if(($module['is_enabled'] ?? false) && !empty($module['route']) && \Illuminate\Support\Facades\Route::has($module['route']))
                                                <a href="{{ route($module['route']) }}"  class="inline-flex items-center rounded-lg bg-slate-700 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-slate-600">
                                                    {{ __('ui.superadmin.common.open') }}
                                                </a>
                                            @endif
                                            <a href="{{ route('services.index') }}"  class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                                                {{ __('ui.superadmin.common.manage') }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg mb-6 border border-slate-200 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            {{ __('ui.superadmin.module_control.no_module_data') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 mt-5 lg:grid-cols-2">
                <div class="sa-card p-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.history.title') }}</h2>
                    <div class="mt-2 space-y-2 max-h-72 overflow-y-auto">
                        @forelse($recentSystemHistory as $entry)
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900">
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ __($entry['type_key'] ?? 'ui.superadmin.common.na') }} - {{ $entry['title'] }}</p>
                                @php
                                    $metaParams = $entry['meta_params'] ?? [];
                                    if (isset($metaParams['status'])) {
                                        $metaParams['status'] = $translateStatus((string) $metaParams['status']);
                                    }
                                @endphp
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __($entry['meta_key'] ?? 'ui.superadmin.common.na', $metaParams) }} - {{ \App\Support\DateTimeDisplay::datetime($entry['updated_at']) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.history.empty') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="sa-card p-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.activity_log.title') }}</h2>
                    <div class="mt-2 space-y-2 max-h-72 overflow-y-auto">
                        @forelse($activityLogs as $log)
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs dark:bg-slate-900">
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $translateActivityEntity((string) ($log->module ?? '-')) . ' / ' . $translateActivityAction((string) ($log->action ?? '-')) }}</p>
                                <p class="text-slate-500 dark:text-slate-400">{{ $translateActivityEntity((string) ($log->subject_type ?? '-')) . ' | ' . __('ui.superadmin.activity_log.user_no', ['id' => ($log->user_id ?? '-')]) }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.activity_log.empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <aside  class="xl:col-span-3">
            <div class="sa-card p-4">
                <div class="flex items-center gap-5">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-orange-500/15 text-orange-500"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.profile.role') }}</p>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-xs">
                    <div class="sa-mini"><span>{{ __('ui.superadmin.profile.environment') }}</span><b>{{ strtoupper($healthInfo['environment'] ?? '-') }}</b></div>
                    <div class="sa-mini"><span>{{ __('ui.superadmin.profile.db_connection') }}</span><b>{{ $healthInfo['database_connection'] ?? '-' }}</b></div>
                    <div class="sa-mini"><span>{{ __('ui.superadmin.profile.queue_connection') }}</span><b>{{ $healthInfo['queue_connection'] ?? '-' }}</b></div>
                    <div class="sa-mini"><span>{{ __('ui.superadmin.profile.debug') }}</span><b>{{ ($healthInfo['debug'] ?? false) ? __('ui.superadmin.common.on') : __('ui.superadmin.common.off') }}</b></div>
                </div>
            </div>

            <div class="sa-card mt-5 p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.critical.title') }}</h3>
                <div class="mt-5 grid grid-cols-3 gap-2">
                    <div class="sa-critical">
                        <small>{{ __('ui.superadmin.critical.failed_jobs') }}</small>
                        <b class="text-rose-600 dark:text-rose-400">{{ $healthInfo['failed_jobs'] ?? 0 }}</b>
                    </div>
                    <div class="sa-critical">
                        <small>{{ __('ui.superadmin.critical.overdue') }}</small>
                        <b class="text-rose-600 dark:text-rose-400">{{ $operationalAlerts['followups_overdue'] ?? 0 }}</b>
                    </div>
                    <div class="sa-critical">
                        <small>{{ __('ui.superadmin.common.queue') }}</small>
                        <b>{{ $healthInfo['queue_backlog'] ?? 0 }}</b>
                    </div>
                </div>
            </div>
            
            <div class="sa-card mt-5 p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.operational_alerts.title') }}</h3>
                <div class="mt-2 space-y-2 text-xs">
                    <div class="sa-mini"><span>{{ __('ui.superadmin.operational_alerts.pending_followups') }}</span><b>{{ $operationalAlerts['pending_followups'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>{{ __('ui.superadmin.operational_alerts.due_today') }}</span><b>{{ $operationalAlerts['followups_due_today'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>{{ __('ui.superadmin.operational_alerts.expiring_7d') }}</span><b>{{ $operationalAlerts['quotations_expiring_7d'] ?? 0 }}</b></div>
                    @if($bookingsModuleEnabled ?? false)
                    <div class="sa-mini"><span>{{ __('ui.superadmin.operational_alerts.upcoming_bookings_7d') }}</span><b>{{ $operationalAlerts['upcoming_bookings_7d'] ?? 0 }}</b></div>
                    @endif
                </div>
            </div>

            <div class="sa-card mt-5 p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('ui.superadmin.status.summary') }}</h3>
                <div class="mt-5 space-y-3">
                    @foreach($statusBlocks as $title => $rows)
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</p>
                            <div class="mt-2 space-y-1">
                                @forelse($rows as $status => $total)
                                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <x-status-badge :status="$status" :label="$translateStatus((string) $status)" size="xs" />
                                        <b>{{ $total }}</b>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.superadmin.status.no_data') }}</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

@endsection

@push('scripts')
{{-- Scripts are not changed --}}
@endpush
