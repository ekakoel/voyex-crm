@extends('layouts.master')

@section('page_title', 'Editor Dashboard')
@section('page_subtitle', 'Fokus pada item yang perlu divalidasi dan update data konten.')
@section('page_actions')
    @if ($canManualQueue)
        <a href="{{ route('itineraries.manual-item-validation-queue') }}" class="btn-primary">Open Item Validation Queue</a>
    @endif
@endsection

@section('content')
    @php
        $catalogSummary = [];
        if ($canDestinations) {
            $catalogSummary[] = ['label' => 'Destinations', 'value' => (int) ($catalogCounts['destinations'] ?? 0)];
        }
        if ($canVendors) {
            $catalogSummary[] = ['label' => 'Vendors', 'value' => (int) ($catalogCounts['vendors'] ?? 0)];
        }
        if ($canActivities) {
            $catalogSummary[] = ['label' => 'Activities', 'value' => (int) ($catalogCounts['activities'] ?? 0)];
        }
        if ($canAttractions) {
            $catalogSummary[] = ['label' => 'Attractions', 'value' => (int) ($catalogCounts['attractions'] ?? 0)];
        }
        if ($canFoodBeverages) {
            $catalogSummary[] = ['label' => 'F&B', 'value' => (int) ($catalogCounts['food_beverages'] ?? 0)];
        }
    @endphp

    <div class="space-y-6 module-page module-page--editor-dashboard">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Need Validation</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format((int) ($pendingManualItemsCount ?? 0)) }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Manual item queue yang masih pending.</p>
            </div>

            <div class="app-card p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Validated Today</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ number_format((int) ($myValidatedTodayCount ?? 0)) }}
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Item yang sudah kamu validasi hari ini.</p>
            </div>

            <div class="app-card p-5 md:col-span-2 xl:col-span-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Priority</p>
                @if (($pendingManualItemsCount ?? 0) > 0)
                    <p class="mt-2 text-sm font-medium text-amber-700 dark:text-amber-300">
                        Ada {{ number_format((int) $pendingManualItemsCount) }} item menunggu validasi. Prioritaskan item terbaru lebih dulu.
                    </p>
                @else
                    <p class="mt-2 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                        Tidak ada antrean validasi saat ini.
                    </p>
                @endif
            </div>
        </div>

        <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
            <section class="min-w-0 space-y-4 xl:col-span-8">
                <div class="app-card p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Recent Pending Items</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Item manual dari day planner yang perlu dilengkapi dan divalidasi.</p>
                        </div>
                        @if ($canManualQueue)
                            <a href="{{ route('itineraries.manual-item-validation-queue') }}" class="btn-outline-sm">View Queue</a>
                        @endif
                    </div>

                    @if (($recentPendingManualItems ?? collect())->isEmpty())
                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300">
                            Tidak ada pending item saat ini.
                        </div>
                    @else
                        <div class="mt-4 space-y-3">
                            @foreach ($recentPendingManualItems as $log)
                                @php
                                    $properties = is_array($log->properties) ? $log->properties : [];
                                    $itemType = strtoupper((string) ($properties['item_type'] ?? 'UNKNOWN'));
                                    $itemName = (string) ($properties['item_name'] ?? '-');
                                    $creatorName = (string) ($properties['creator_name'] ?? '-');
                                    $editUrl = (string) ($properties['edit_url'] ?? '');
                                @endphp
                                <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="inline-flex items-center rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-indigo-700 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                                                {{ $itemType }}
                                            </span>
                                            <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">{{ $itemName }}</p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><x-local-time :value="$log->created_at" /></p>
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-xs text-gray-600 dark:text-gray-300">Created by {{ $creatorName }}</p>
                                        @if ($editUrl !== '')
                                            <a href="{{ $editUrl }}" target="_blank" rel="noopener" class="btn-ghost-sm">Open Item</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="app-card p-5">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Recently Validated by You</h2>
                    @if (($recentlyValidatedByMe ?? collect())->isEmpty())
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Belum ada item yang kamu validasi hari ini.</p>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach ($recentlyValidatedByMe as $log)
                                @php
                                    $properties = is_array($log->properties) ? $log->properties : [];
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm dark:border-gray-700">
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ (string) ($properties['item_name'] ?? '-') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ strtoupper((string) ($properties['item_type'] ?? 'UNKNOWN')) }}</p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><x-local-time :value="$log->updated_at" /></p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <aside class="min-w-0 space-y-4 xl:col-span-4">
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Content Scope</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ringkasan data master yang biasanya perlu editor jaga kualitasnya.</p>

                    <div class="mt-4 space-y-2">
                        @forelse ($catalogSummary as $item)
                            <div class="flex items-center justify-between rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900/30">
                                <span class="text-gray-600 dark:text-gray-300">{{ $item['label'] }}</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ number_format((int) $item['value']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada modul konten yang bisa diakses.</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
