@extends('layouts.master')
@section('page_title', 'System Modules')
@section('page_subtitle', 'Manage module availability with a modern control center.')
@section('content')
    @php
        $enabledCount = (int) ($modules->where('is_enabled', true)->count());
        $disabledCount = (int) ($modules->where('is_enabled', false)->count());
        $totalCount = (int) $modules->count();
    @endphp

    <div class="sa-wrap rounded-3xl border border-slate-200/80 bg-slate-100/70 p-3 dark:border-slate-700 dark:bg-slate-900/60">
        @if (session('success'))
            <div class="rounded-lg mb-4 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg mb-4 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
            <section class="xl:col-span-9 space-y-3">
                <div class="sa-card p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Module Control Center') }}</h2>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('Toggle access availability per module') }}</span>
                    </div>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @forelse ($modules as $module)
                            @php
                                $enabled = (bool) $module->is_enabled;
                                $stateClass = $enabled
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/20 dark:text-rose-300';
                                $cardStyle = $enabled ? '' : 'background-color: #ffcece;';
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700 grid content-between" @if($cardStyle) style="{{ $cardStyle }}" @endif>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $module->name }}</p>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $module->key }}</p>
                                    </div>
                                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold {{ $stateClass }}">
                                        {{ $enabled ? 'ENABLED' : 'DISABLED' }}
                                    </span>
                                </div>

                                <p class="mt-3 min-h-[40px] text-xs text-slate-600 dark:text-slate-300">
                                    {{ $module->description ?: 'No module description.' }}
                                </p>

                                <form method="POST" action="{{ route('services.toggle', $module) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="{{ $enabled ? 'btn-secondary-sm' : 'btn-primary-sm' }}">
                                        {{ $enabled ? 'Disable Module' : 'Enable Module' }}
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="rounded-xl border border-slate-200 px-3 py-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400 md:col-span-2 xl:col-span-3">
                                No module data available yet. Run migrations and seeders first.
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="xl:col-span-3 space-y-3">
                <div class="sa-card p-4">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ __('Snapshot') }}</h3>
                    <div class="mt-3 space-y-2 text-xs">
                        <div class="sa-mini"><span>{{ __('Total Modules') }}</span><b>{{ number_format($totalCount) }}</b></div>
                        <div class="sa-mini"><span>{{ __('Enabled') }}</span><b class="text-emerald-600 dark:text-emerald-400">{{ number_format($enabledCount) }}</b></div>
                        <div class="sa-mini"><span>{{ __('Disabled') }}</span><b class="text-rose-600 dark:text-rose-400">{{ number_format($disabledCount) }}</b></div>
                    </div>
                </div>

                <div class="sa-card p-4 text-xs text-slate-500 dark:text-slate-400">
                    <p class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Usage Tips') }}</p>
                    <p class="mt-2">{{ __('Disable module only when unused, because related menu and workflow access will be hidden.') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
