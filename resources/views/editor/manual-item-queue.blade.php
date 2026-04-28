@extends('layouts.master')

@section('page_title', 'Item Validation Queue')
@section('page_subtitle', 'Review and validate manual items created from Itinerary Day Planner.')
@section('page_actions')
    <a href="{{ route('itineraries.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
@endsection

@section('content')
    @php
        $pageItems = $logs->getCollection();
        $totalPending = (int) $logs->total();
        $currentPageTotal = (int) $pageItems->count();
        $typeCounts = $pageItems
            ->map(function ($log) {
                $properties = is_array($log->properties) ? $log->properties : [];
                return strtoupper((string) ($properties['item_type'] ?? 'UNKNOWN'));
            })
            ->countBy()
            ->sortDesc();
        $latestLog = $pageItems->first();
    @endphp

    <div class="space-y-6 module-page module-page--manual-item-validation">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
            <div class="min-w-0 space-y-4 xl:col-span-8">
                <div class="app-card p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Pending Validation</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Review manual items created by other users, then mark as validated.</p>
                        </div>
                        <span class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700 dark:border-cyan-700 dark:bg-cyan-900/20 dark:text-cyan-300">
                            {{ number_format($totalPending) }} item(s)
                        </span>
                    </div>
                </div>

                @if ($logs->isEmpty())
                    <div class="app-card p-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300">
                            No pending manual items to validate.
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($logs as $log)
                            @php
                                $properties = is_array($log->properties) ? $log->properties : [];
                                $itemType = strtoupper((string) ($properties['item_type'] ?? 'UNKNOWN'));
                                $itemName = (string) ($properties['item_name'] ?? '-');
                                $creatorName = (string) ($properties['creator_name'] ?? '-');
                                $editUrl = (string) ($properties['edit_url'] ?? '');
                            @endphp

                            <div class="app-card p-4">
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                                    <div class="min-w-0 space-y-3 lg:col-span-8">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                                                {{ $itemType }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Log #{{ (int) $log->id }}
                                            </span>
                                        </div>

                                        <div class="space-y-1">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $itemName }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                                Created by <span class="font-medium">{{ $creatorName }}</span>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <x-local-time :value="$log->created_at" />
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-start justify-start gap-2 lg:col-span-4 lg:justify-end">
                                        @if ($editUrl !== '')
                                            <a href="{{ $editUrl }}" class="btn-outline-sm" target="_blank" rel="noopener">Open Item</a>
                                        @endif
                                        <form method="POST" action="{{ route('itineraries.manual-item-validation-queue.validate', $log) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-primary-sm">Mark as Validated</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="app-card p-4">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

            <aside class="min-w-0 space-y-4 xl:col-span-4">
                <div class="app-card p-5 xl:sticky xl:top-24">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Queue Overview</h4>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 dark:border-cyan-700 dark:bg-cyan-900/20">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Total Pending</p>
                            <p class="mt-1 text-lg font-semibold text-cyan-800 dark:text-cyan-200">{{ number_format($totalPending) }}</p>
                        </div>
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 dark:border-indigo-700 dark:bg-indigo-900/20">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Shown On Page</p>
                            <p class="mt-1 text-lg font-semibold text-indigo-800 dark:text-indigo-200">{{ number_format($currentPageTotal) }}</p>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item Type Distribution</h5>
                        <div class="mt-2 space-y-2">
                            @forelse ($typeCounts as $type => $count)
                                <div class="flex items-center justify-between rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900/30">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">{{ $type }}</span>
                                    <span class="rounded-full bg-gray-200 px-2 py-0.5 font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ (int) $count }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-gray-500 dark:text-gray-400">No items in current page.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <h5 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latest Item</h5>
                        @if ($latestLog)
                            @php
                                $latestProps = is_array($latestLog->properties) ? $latestLog->properties : [];
                            @endphp
                            <p class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-100">{{ (string) ($latestProps['item_name'] ?? '-') }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ strtoupper((string) ($latestProps['item_type'] ?? 'UNKNOWN')) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><x-local-time :value="$latestLog->created_at" /></p>
                        @else
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">No pending item.</p>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
