@extends('layouts.master')

@section('content')
@php
    $statusBlocks = [
        'Inquiry Status' => $inquiryByStatus,
        'Quotation Status' => $quotationByStatus,
        'Booking Status' => $bookingByStatus,
    ];
@endphp

<div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
    <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
        <div class="xl:col-span-9">
            <div class="sa-card p-5">
                @section('page_title', 'Super Admin Analytics')
                @section('page_subtitle', 'Data diperbarui saat halaman dibuka atau refresh manual')
                @section('page_actions')
                    <span class="text-xs text-slate-500 dark:text-slate-400">Last loaded: {{ now()->format('d M Y H:i') }}</span>
                @endsection

                <div class="mt-4 grid grid-cols-1 gap-3 xl:grid-cols-12">
                    <div class="xl:col-span-5 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                        <div class="mb-2 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Action Center</h2>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Prioritas saat ini</span>
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
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-medium text-slate-700 dark:text-slate-200">{{ $item['label'] }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $item['hint'] ?? '-' }}</p>
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
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Business Funnel</h2>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400">Inquiry -> Invoice</span>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-5">
                            @foreach(($funnel ?? []) as $stage)
                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $stage['label'] }}</p>
                                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((int) ($stage['value'] ?? 0)) }}</p>
                                    @if(!is_null($stage['conversion']))
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Conv: {{ number_format((float) $stage['conversion'], 1) }}%</p>
                                    @else
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Baseline</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="sa-card mt-3 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Module Control Center</h2>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Grouped by domain for full-system management</span>
                </div>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
                    @forelse(($moduleGroups ?? []) as $group)
                        <div class="flex h-full flex-col rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">{{ $group['name'] }}</h3>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ count($group['modules'] ?? []) }} modules</span>
                            </div>
                            <div class="space-y-3">
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
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                                    <i class="fa-solid fa-{{ $module['icon'] ?? 'puzzle-piece' }} mr-1 w-4 text-center"></i>{{ $module['name'] }}
                                                </p>
                                                <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $module['key'] }}</p>
                                            </div>
                                            <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $healthClass }}">
                                                {{ strtoupper((string) ($module['health'] ?? 'healthy')) }}
                                            </span>
                                        </div>

                                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                            <div class="rounded-lg bg-slate-50 px-2 py-2 dark:bg-slate-800">
                                                <p class="text-slate-500 dark:text-slate-400">{{ $module['metric']['label'] ?? 'Metric' }}</p>
                                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format((int) ($module['metric']['value'] ?? 0)) }}</p>
                                            </div>
                                            <div class="rounded-lg bg-slate-50 px-2 py-2 dark:bg-slate-800">
                                                <p class="text-slate-500 dark:text-slate-400">Role Coverage</p>
                                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ number_format((int) ($module['role_coverage'] ?? 0)) }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center gap-2">
                                            @if(!empty($module['route']) && \Illuminate\Support\Facades\Route::has($module['route']))
                                                <a href="{{ route($module['route']) }}"  class="inline-flex items-center rounded-lg bg-slate-700 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-slate-600">
                                                    Open
                                                </a>
                                            @endif
                                            <a href="{{ route('services.index') }}"  class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
                                                Manage
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-lg mb-6 border border-slate-200 px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            No module data.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 mt-3 lg:grid-cols-2">
                <div class="sa-card p-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Recent System History</h2>
                    <div class="mt-2 space-y-2 max-h-72 overflow-y-auto">
                        @forelse($recentSystemHistory as $entry)
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm dark:bg-slate-900">
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $entry['type'] }} - {{ $entry['title'] }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $entry['meta'] }} � {{ \Illuminate\Support\Carbon::parse($entry['updated_at'])->diffForHumans() }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No history yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="sa-card p-4">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Activity Log</h2>
                    <div class="mt-2 space-y-2 max-h-72 overflow-y-auto">
                        @forelse($activityLogs as $log)
                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-xs dark:bg-slate-900">
                                <p class="font-medium text-slate-700 dark:text-slate-200">{{ $log->event ?: ($log->log_name ?: '-') }}</p>
                                <p class="text-slate-500 dark:text-slate-400">{{ $log->description ?: '-' }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">No activity log.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <aside  class="xl:col-span-3">
            <div class="sa-card p-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-xl bg-orange-500/15 text-orange-500"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Super Admin</p>
                    </div>
                </div>
                <div class="mt-4 space-y-2 text-xs">
                    <div class="sa-mini"><span>Environment</span><b>{{ strtoupper($healthInfo['environment'] ?? '-') }}</b></div>
                    <div class="sa-mini"><span>DB Connection</span><b>{{ $healthInfo['database_connection'] ?? '-' }}</b></div>
                    <div class="sa-mini"><span>Queue Connection</span><b>{{ $healthInfo['queue_connection'] ?? '-' }}</b></div>
                    <div class="sa-mini"><span>Debug</span><b>{{ ($healthInfo['debug'] ?? false) ? 'ON' : 'OFF' }}</b></div>
                </div>
            </div>

            <div class="sa-card mt-3 p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Critical Monitoring</h3>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <div class="sa-critical">
                        <small>Failed Jobs</small>
                        <b class="text-rose-600 dark:text-rose-400">{{ $healthInfo['failed_jobs'] ?? 0 }}</b>
                    </div>
                    <div class="sa-critical">
                        <small>Overdue</small>
                        <b class="text-rose-600 dark:text-rose-400">{{ $operationalAlerts['followups_overdue'] ?? 0 }}</b>
                    </div>
                    <div class="sa-critical">
                        <small>Queue</small>
                        <b>{{ $healthInfo['queue_backlog'] ?? 0 }}</b>
                    </div>
                </div>
            </div>
            
            <div class="sa-card mt-3 p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Operational Alerts</h3>
                <div class="mt-2 space-y-2 text-xs">
                    <div class="sa-mini"><span>Pending Follow-ups</span><b>{{ $operationalAlerts['pending_followups'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Due Today</span><b>{{ $operationalAlerts['followups_due_today'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Expiring Quotations (7D)</span><b>{{ $operationalAlerts['quotations_expiring_7d'] ?? 0 }}</b></div>
                    <div class="sa-mini"><span>Upcoming Bookings (7D)</span><b>{{ $operationalAlerts['upcoming_bookings_7d'] ?? 0 }}</b></div>
                </div>
            </div>

            <div class="sa-card mt-3 p-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Status Summary</h3>
                <div class="mt-3 space-y-3">
                    @foreach($statusBlocks as $title => $rows)
                        <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</p>
                            <div class="mt-2 space-y-1">
                                @forelse($rows as $status => $total)
                                    <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300">
                                        <x-status-badge :status="$status" :label="ucfirst(str_replace('_', ' ', $status))" size="xs" />
                                        <b>{{ $total }}</b>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No data</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
{{-- Scripts are not changed --}}
@endpush

