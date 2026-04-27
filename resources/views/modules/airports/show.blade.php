@extends('layouts.master')

@section('page_title', __('ui.modules.airports.page_title'))
@section('page_subtitle', __('ui.modules.airports.show_page_subtitle'))
@section('page_actions')
    <a href="{{ route('airports.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
    <a href="{{ route('airports.edit', $airport) }}" class="btn-primary">{{ __('ui.common.edit') }}</a>
@endsection

@section('content')
    @php($isActive = ! $airport->trashed())
    @php($airportCoverUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($airport->cover ?? null, ['airports/covers', 'airports/cover'], 'public', 360, 240, false))

    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-8-4 airport-detail-print-grid">
            <div class="module-grid-main space-y-4">
                @if (filled($airportCoverUrl))
                    <div class="app-card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Cover Image') }}</h3>
                        <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <img src="{{ $airportCoverUrl }}" alt="{{ __('Airport cover image') }}" class="aspect-[16/9] w-full object-cover">
                        </div>
                    </div>
                @endif
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.airports.airport_information') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.code') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $airport->code }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.airport_name') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.destination') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->destination?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}</p>
                            <div class="mt-1"><x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.country') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->country ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.timezone') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->timezone ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.airports.property') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.property') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->location ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.city_province') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim(($airport->city ?? '') . (($airport->city && $airport->province) ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.latitude') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->latitude ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.longitude') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->longitude ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.address') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->address ?: '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.google_maps_url') }}</p>
                            @if (filled($airport->google_maps_url))
                                <a href="{{ $airport->google_maps_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm text-sky-600 hover:underline dark:text-sky-300">{{ __('ui.modules.airports.open_map') }}</a>
                            @else
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">-</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    @include('modules.airports.partials._location-map', [
                        'mapTitle' => __('ui.modules.airports.location_on_map'),
                        'mapHeightClass' => 'h-[320px]',
                        'latValue' => $airport->latitude,
                        'lngValue' => $airport->longitude,
                        'interactive' => false,
                    ])
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.notes') }}</h3>
                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $airport->notes ? nl2br(e($airport->notes)) : '-' !!}</div>
                </div>
            </div>

            <aside class="module-grid-side space-y-4 airport-detail-print-hide">
                <div class="app-card p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.quick_actions') }}</p>
                    <a href="{{ route('airports.edit', $airport) }}" class="btn-primary w-full justify-center">{{ __('ui.modules.airports.edit_airport') }}</a>
                    <form action="{{ route('airports.toggle-status', $airport->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? __('ui.modules.airports.confirm_deactivate') : __('ui.modules.airports.confirm_activate') }}')"
                            class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }} w-full justify-center"
                        >
                            {{ $isActive ? __('ui.common.deactivate') : __('ui.common.activate') }}
                        </button>
                    </form>
                </div>

                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.summary') }}</p>
                    @php
                        $summaryFields = [
                            __('ui.common.code') => $airport->code ?? null,
                            __('ui.modules.airports.airport') => $airport->name ?? null,
                            __('ui.common.destination') => $airport->destination?->name ?? null,
                            __('ui.common.location') => $airport->location ?? null,
                            __('ui.modules.airports.city') => $airport->city ?? null,
                            __('ui.modules.airports.province') => $airport->province ?? null,
                            __('ui.common.country') => $airport->country ?? null,
                            __('ui.modules.airports.timezone') => $airport->timezone ?? null,
                            __('ui.modules.airports.address') => $airport->address ?? null,
                        ];
                    @endphp
                    <dl class="mt-3 space-y-2">
                        @foreach ($summaryFields as $label => $value)
                            @continue(! filled($value))
                            <div class="grid grid-cols-[110px_1fr] gap-2">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $value }}</dd>
                            </div>
                        @endforeach
                        <div class="grid grid-cols-[110px_1fr] gap-2">
                            <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.common.status') }}</dt>
                            <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">
                                <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </dd>
                        </div>
                    </dl>
                </div>

                @include('partials._audit-info', ['record' => $airport])
            </aside>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @media print {
            .airport-detail-print-hide,
            .app-sidebar,
            .app-topbar,
            .app-page-header__actions,
            .page-spinner {
                display: none !important;
            }

            .airport-detail-print-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            .app-card {
                box-shadow: none !important;
                border-color: #d1d5db !important;
            }
        }
    </style>
@endpush
