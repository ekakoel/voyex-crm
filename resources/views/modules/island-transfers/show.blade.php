@extends('layouts.master')

@section('page_title', __('ui.modules.island_transfers.show_page_title'))
@section('page_subtitle', __('ui.modules.island_transfers.show_page_subtitle'))
@section('page_actions')
    <a href="{{ route('island-transfers.index') }}" class="btn-ghost">{{ __('ui.modules.island_transfers.back') }}</a>
    <a href="{{ route('island-transfers.edit', $islandTransfer) }}" class="btn-primary">{{ __('ui.modules.island_transfers.edit') }}</a>
@endsection

@section('content')
    @php($isActive = ! $islandTransfer->trashed())
    <div class="module-page module-page--island-transfers">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.transfer_name') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $islandTransfer->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.type') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ __('ui.modules.island_transfers.types.' . (string) $islandTransfer->transfer_type) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.duration') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ __('ui.modules.island_transfers.duration_short', ['minutes' => (int) ($islandTransfer->duration_minutes ?? 0)]) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.capacity') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $islandTransfer->capacity_min ?? '-' }} - {{ $islandTransfer->capacity_max ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.status') }}</p>
                            <div class="mt-1">
                                <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.contract_rate') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($islandTransfer->contract_rate ?? 0)" currency="IDR" /></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.markup') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">
                                @if (($islandTransfer->markup_type ?? 'fixed') === 'percent')
                                    {{ rtrim(rtrim(number_format((float) ($islandTransfer->markup ?? 0), 2, '.', ''), '0'), '.') }}%
                                @else
                                    <x-money :amount="(float) ($islandTransfer->markup ?? 0)" currency="IDR" />
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.publish_rate') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($islandTransfer->publish_rate ?? 0)" currency="IDR" /></p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.island_transfers.route_details') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-700 dark:bg-sky-900/20">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-200">{{ __('ui.modules.island_transfers.departure_point') }}</p>
                            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $islandTransfer->departure_point_name ?: '-' }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('ui.modules.island_transfers.lat') }}: {{ $islandTransfer->departure_latitude ?: '-' }} | {{ __('ui.modules.island_transfers.lng') }}: {{ $islandTransfer->departure_longitude ?: '-' }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">{{ __('ui.modules.island_transfers.arrival_point') }}</p>
                            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $islandTransfer->arrival_point_name ?: '-' }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ __('ui.modules.island_transfers.lat') }}: {{ $islandTransfer->arrival_latitude ?: '-' }} | {{ __('ui.modules.island_transfers.lng') }}: {{ $islandTransfer->arrival_longitude ?: '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.island_transfers.route_geojson_optional') }}</h3>
                    <pre class="mt-3 overflow-auto rounded-md bg-gray-100 p-3 text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $islandTransfer->route_geojson ? json_encode($islandTransfer->route_geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-' }}</pre>
                </div>

                @if (!empty($islandTransfer->notes))
                    <div class="app-card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.island_transfers.notes') }}</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $islandTransfer->notes }}</p>
                    </div>
                @endif
            </div>

            <aside class="module-grid-side">
                @include('modules.island-transfers.partials._route-map', [
                    'mapTitle' => 'Island Transfer Route Map (open map)',
                    'interactive' => false,
                    'departureLat' => $islandTransfer->departure_latitude,
                    'departureLng' => $islandTransfer->departure_longitude,
                    'arrivalLat' => $islandTransfer->arrival_latitude,
                    'arrivalLng' => $islandTransfer->arrival_longitude,
                    'routeGeoJson' => $islandTransfer->route_geojson,
                ])
                <div class="app-card p-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.modules.island_transfers.quick_actions') }}</p>
                    <a href="{{ route('island-transfers.edit', $islandTransfer) }}" class="btn-primary mb-3 w-full justify-center">{{ __('ui.modules.island_transfers.edit_transfer') }}</a>
                    <form action="{{ route('island-transfers.toggle-status', $islandTransfer->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? __('ui.modules.island_transfers.confirm_deactivate') : __('ui.modules.island_transfers.confirm_activate') }}')"
                            class="{{ $isActive ? 'btn-muted' : 'btn-primary' }} w-full justify-center"
                        >
                            {{ $isActive ? __('ui.modules.island_transfers.deactivate') : __('ui.modules.island_transfers.activate') }}
                        </button>
                    </form>
                </div>
                @include('modules.activities.partials._vendor-info', ['vendor' => $islandTransfer->vendor])
                @include('partials._audit-info', ['record' => $islandTransfer])
            </aside>
        </div>
    </div>
@endsection
