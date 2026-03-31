@extends('layouts.master')

@section('page_title', 'Airports')
@section('page_subtitle', 'Airport detail information.')
@section('page_actions')
    <a href="{{ route('airports.index') }}" class="btn-ghost">Back</a>
    <a href="{{ route('airports.edit', $airport) }}" class="btn-primary">Edit</a>
    <button type="button" class="btn-outline airport-detail-print-hide" onclick="window.print()">Print</button>
@endsection

@section('content')
    @php($isActive = ! $airport->trashed())

    <div class="space-y-6 module-page module-page--airports">
        <div class="module-grid-8-4 airport-detail-print-grid">
            <div class="module-grid-main space-y-4">
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Airport Information</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $airport->code }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Airport Name</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Destination</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->destination?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                            <div class="mt-1"><x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Country</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->country ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Timezone</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->timezone ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Property</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Property</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->location ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">City / Province</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim(($airport->city ?? '') . (($airport->city && $airport->province) ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Latitude</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->latitude ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Longitude</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->longitude ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Address</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $airport->address ?: '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Google Maps URL</p>
                            @if (filled($airport->google_maps_url))
                                <a href="{{ $airport->google_maps_url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-sm text-sky-600 hover:underline dark:text-sky-300">Open map</a>
                            @else
                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">-</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    @include('modules.airports.partials._location-map', [
                        'mapTitle' => 'Location on Map (open map)',
                        'mapHeightClass' => 'h-[320px]',
                        'latValue' => $airport->latitude,
                        'lngValue' => $airport->longitude,
                        'interactive' => false,
                    ])
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Notes</h3>
                    <div class="mt-2 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $airport->notes ? nl2br(e($airport->notes)) : '-' !!}</div>
                </div>
            </div>

            <aside class="module-grid-side space-y-4 airport-detail-print-hide">
                <div class="app-card p-5 space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Quick Actions</p>
                    <a href="{{ route('airports.edit', $airport) }}" class="btn-primary w-full justify-center">Edit Airport</a>
                    <form action="{{ route('airports.toggle-status', $airport->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? 'Deactivate this airport?' : 'Activate this airport?' }}')"
                            class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }} w-full justify-center"
                        >
                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                    <button type="button" class="btn-outline w-full justify-center" onclick="window.print()">Print Detail</button>
                </div>

                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Summary</p>
                    @php
                        $summaryFields = [
                            'Code' => $airport->code ?? null,
                            'Airport' => $airport->name ?? null,
                            'Destination' => $airport->destination?->name ?? null,
                            'Location' => $airport->location ?? null,
                            'City' => $airport->city ?? null,
                            'Province' => $airport->province ?? null,
                            'Country' => $airport->country ?? null,
                            'Timezone' => $airport->timezone ?? null,
                            'Address' => $airport->address ?? null,
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
                            <dt class="text-xs text-slate-500 dark:text-slate-400">Status</dt>
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


