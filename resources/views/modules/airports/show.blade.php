@extends('layouts.master')

@section('page_title', ui_phrase('page title'))
@section('page_subtitle', ui_phrase('show page subtitle'))
@section('page_actions')
    <a href="{{ route('airports.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
    <a href="{{ route('airports.edit', $airport) }}" class="btn-primary">{{ ui_phrase('Edit') }}</a>
@endsection

@section('content')
    @php($isActive = ! $airport->trashed())
    @php($airportCoverUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($airport->cover ?? null, ['airports/covers', 'airports/cover'], 'public', 360, 240, false))

    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-8-4 airport-detail-print-grid">
            <div class="module-grid-main">
                @if (filled($airportCoverUrl))
                    <div class="app-card p-5">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Cover Image') }}</h3>
                        <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <img src="{{ $airportCoverUrl }}" alt="{{ ui_phrase('Airport cover image') }}" class="aspect-[16/9] w-full object-cover">
                        </div>
                    </div>
                @endif
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Airport Information') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Code') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $airport->code }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Airport Name') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Destination') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->destination?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</p>
                            <div class="mt-1"><x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Country') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->country ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Timezone') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->timezone ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Property') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Property') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->location ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('City / Province') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim(($airport->city ?? '') . (($airport->city && $airport->province) ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Latitude') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->latitude ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Longitude') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->longitude ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Address') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->address ?: '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Google Maps URL') }}</p>
                            @if (filled($airport->google_maps_url))
                                <a href="{{ $airport->google_maps_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm text-sky-600 hover:underline dark:text-sky-300">{{ ui_phrase('Open map') }}</a>
                            @else
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">-</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    @include('modules.airports.partials._location-map', [
                        'mapTitle' => ui_phrase('location on map'),
                        'mapHeightClass' => 'h-[320px]',
                        'latValue' => $airport->latitude,
                        'lngValue' => $airport->longitude,
                        'interactive' => false,
                    ])
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Notes') }}</h3>
                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $airport->notes ? nl2br(e($airport->notes)) : '-' !!}</div>
                </div>
            </div>

            <aside class="module-grid-side airport-detail-print-hide">
                <div class="app-card p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('Quick Actions') }}</p>
                    <a href="{{ route('airports.edit', $airport) }}" class="btn-primary w-full justify-center">{{ ui_phrase('Edit Airport') }}</a>
                    <x-ui.confirm-action
                        :action="route('airports.toggle-status', $airport->id)"
                        method="PATCH"
                        :modal-name="'airports-show-toggle-' . $airport->id"
                        :title="$isActive ? ui_phrase('Deactivate') . ' ' . ui_phrase('Airport') : ui_phrase('Activate') . ' ' . ui_phrase('Airport')"
                        :message="$isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate')"
                        :impact-title="__('confirm.what_will_happen')"
                        :impact-items="[
                            $isActive ? ui_phrase('Airport will be set as inactive and hidden from active options.') : ui_phrase('Airport will be set as active and available for selection.'),
                        ]"
                        :notice-message="__('confirm.notification_after_action')"
                        :confirm-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                        :trigger-label="$isActive ? ui_phrase('Deactivate') : ui_phrase('Activate')"
                        :trigger-class="$isActive ? 'btn-muted-sm w-full justify-center' : 'btn-primary-sm w-full justify-center'"
                        confirm-class="btn-primary-sm"
                    />
                </div>

                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('Summary') }}</p>
                    @php
                        $summaryFields = [
                            ui_phrase('Code') => $airport->code ?? null,
                            ui_phrase('Airport') => $airport->name ?? null,
                            ui_phrase('Destination') => $airport->destination?->name ?? null,
                            ui_phrase('Location') => $airport->location ?? null,
                            ui_phrase('City') => $airport->city ?? null,
                            ui_phrase('Province') => $airport->province ?? null,
                            ui_phrase('Country') => $airport->country ?? null,
                            ui_phrase('Timezone') => $airport->timezone ?? null,
                            ui_phrase('Address') => $airport->address ?? null,
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
                            <dt class="text-xs text-slate-500 dark:text-slate-400">{{ ui_phrase('Status') }}</dt>
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

