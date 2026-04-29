@extends('layouts.master')

@section('page_title', ui_phrase('transfers show page title'))
@section('page_subtitle', ui_phrase('transfers show page subtitle'))
@section('page_actions')
    <a href="{{ route('island-transfers.index') }}" class="btn-ghost">{{ ui_phrase('transfers back') }}</a>
    <a href="{{ route('island-transfers.edit', $islandTransfer) }}" class="btn-primary">{{ ui_phrase('transfers edit') }}</a>
@endsection

@section('content')
    @php
        $isActive = ! $islandTransfer->trashed();
        $gallery = is_array($islandTransfer->gallery_images) ? array_values($islandTransfer->gallery_images) : [];
        $galleryItems = collect($gallery)
            ->map(function (string $path): ?array {
                $thumbUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($path);
                $fullUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($path);
                if (! $thumbUrl && ! $fullUrl) {
                    return null;
                }

                return [
                    'thumb_url' => $thumbUrl ?: $fullUrl,
                    'full_url' => $fullUrl ?: $thumbUrl,
                ];
            })
            ->filter()
            ->values();
    @endphp
    <div class="module-page module-page--island-transfers">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
                @include('modules.activities.partials._vendor-info', ['vendor' => $islandTransfer->vendor])

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Gallery') }}</h3>
                    @if ($galleryItems->isNotEmpty())
                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($galleryItems as $item)
                                <a href="{{ $item['full_url'] }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                    <img src="{{ $item['thumb_url'] }}" alt="Island transfer image" class="h-28 w-full object-cover">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('no gallery') }}</p>
                    @endif
                </div>

                <div class="app-card p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers transfer name') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $islandTransfer->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers type') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ ui_phrase(match ((string) $islandTransfer->transfer_type) {
                                'fastboat' => 'Fastboat',
                                'ferry' => 'Ferry',
                                'speedboat' => 'Speedboat',
                                'boat' => 'Boat',
                                default => (string) $islandTransfer->transfer_type,
                            }) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers duration') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ ui_phrase('transfers duration short', ['minutes' => (int) ($islandTransfer->duration_minutes ?? 0)]) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers distance') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ ui_phrase('transfers distance short', ['distance' => number_format((float) ($islandTransfer->distance_km ?? 0), 2, '.', '')]) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers capacity') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $islandTransfer->capacity_min ?? '-' }} - {{ $islandTransfer->capacity_max ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers status') }}</p>
                            <div class="mt-1">
                                <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers contract rate') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($islandTransfer->contract_rate ?? 0)" currency="IDR" /></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers markup') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">
                                @if (($islandTransfer->markup_type ?? 'fixed') === 'percent')
                                    {{ rtrim(rtrim(number_format((float) ($islandTransfer->markup ?? 0), 2, '.', ''), '0'), '.') }}%
                                @else
                                    <x-money :amount="(float) ($islandTransfer->markup ?? 0)" currency="IDR" />
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('transfers publish rate') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($islandTransfer->publish_rate ?? 0)" currency="IDR" /></p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('transfers route details') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-700 dark:bg-sky-900/20">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-800 dark:text-sky-200">{{ ui_phrase('transfers departure point') }}</p>
                            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $islandTransfer->departure_point_name ?: '-' }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ ui_phrase('transfers lat') }}: {{ $islandTransfer->departure_latitude ?: '-' }} | {{ ui_phrase('transfers lng') }}: {{ $islandTransfer->departure_longitude ?: '-' }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-700 dark:bg-emerald-900/20">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-200">{{ ui_phrase('transfers arrival point') }}</p>
                            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $islandTransfer->arrival_point_name ?: '-' }}</p>
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                {{ ui_phrase('transfers lat') }}: {{ $islandTransfer->arrival_latitude ?: '-' }} | {{ ui_phrase('transfers lng') }}: {{ $islandTransfer->arrival_longitude ?: '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                @if (!empty($islandTransfer->notes))
                    <div class="app-card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('transfers notes') }}</h3>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $islandTransfer->notes }}</p>
                    </div>
                @endif
            </div>

            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $islandTransfer])

                <div class="app-card p-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('transfers quick actions') }}</p>
                    <a href="{{ route('island-transfers.edit', $islandTransfer) }}" class="btn-primary mb-3 w-full justify-center">{{ ui_phrase('transfers edit transfer') }}</a>
                    <form action="{{ route('island-transfers.toggle-status', $islandTransfer->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? ui_phrase('transfers confirm deactivate') : ui_phrase('transfers confirm activate') }}')"
                            class="{{ $isActive ? 'btn-muted' : 'btn-primary' }} w-full justify-center"
                        >
                            {{ $isActive ? ui_phrase('transfers deactivate') : ui_phrase('transfers activate') }}
                        </button>
                    </form>
                </div>

                @include('modules.island-transfers.partials._route-map', [
                    'mapTitle' => 'Island Transfer Route Map (open map)',
                    'interactive' => false,
                    'departureLat' => $islandTransfer->departure_latitude,
                    'departureLng' => $islandTransfer->departure_longitude,
                    'arrivalLat' => $islandTransfer->arrival_latitude,
                    'arrivalLng' => $islandTransfer->arrival_longitude,
                    'routeGeoJson' => $islandTransfer->route_geojson,
                ])
            </aside>
        </div>
    </div>
@endsection
