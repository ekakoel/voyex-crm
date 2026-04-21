@extends('layouts.master')

@section('page_title', 'Service Map')
@section('page_subtitle', 'Full map of services with valid coordinates.')
@section('page_actions')
    <a href="{{ route('services.index') }}" class="btn-ghost">{{ __('Back to Services') }}</a>
@endsection

@section('content')
    @php
        $legend = [
            ['type' => 'destination', 'label' => 'Destinations', 'icon' => 'fa-map-location-dot', 'color' => '#2563eb', 'count' => $stats['destinations'] ?? 0],
            ['type' => 'vendor', 'label' => 'Vendors', 'icon' => 'fa-handshake', 'color' => '#16a34a', 'count' => $stats['vendors'] ?? 0],
            ['type' => 'activity', 'label' => 'Activities', 'icon' => 'fa-person-hiking', 'color' => '#ea580c', 'count' => $stats['activities'] ?? 0],
            ['type' => 'food-beverage', 'label' => 'F&B', 'icon' => 'fa-utensils', 'color' => '#dc2626', 'count' => $stats['foodBeverages'] ?? 0],
            ['type' => 'hotel', 'label' => 'Hotels', 'icon' => 'fa-bed', 'color' => '#7c3aed', 'count' => $stats['hotels'] ?? 0],
            ['type' => 'airport', 'label' => 'Airports', 'icon' => 'fa-plane-departure', 'color' => '#0f766e', 'count' => $stats['airports'] ?? 0],
            ['type' => 'transport', 'label' => 'Transports', 'icon' => 'fa-bus', 'color' => '#be123c', 'count' => $stats['transports'] ?? 0],
            ['type' => 'tourist-attraction', 'label' => 'Attractions', 'icon' => 'fa-landmark', 'color' => '#4f46e5', 'count' => $stats['attractions'] ?? 0],
        ];
    @endphp

    <div class="space-y-6 module-page module-page--service-map" data-service-map-page>
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Summary') }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ number_format($stats['total'] ?? 0) }} point(s)</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ __('Auto-loaded from service modules that have latitude and longitude.') }}</p>
                </div>
                <div class="app-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Legend') }}</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($legend as $item)
                            <label class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                <span class="inline-flex items-center gap-2">
                                    <input type="checkbox" class="rounded border-slate-300" data-map-type-toggle value="{{ $item['type'] }}" checked>
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-white" style="background: {{ $item['color'] }};">
                                        <i class="fa-solid {{ $item['icon'] }} text-[11px]"></i>
                                    </span>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ number_format((int) ($item['count'] ?? 0)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </aside>
            <div class="module-grid-main">
                <div class="app-card overflow-hidden p-0">
                    <div id="service-map-canvas" class="h-[75vh] w-full"></div>
                    <script type="application/json" id="service-map-markers">@json($markers)</script>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .service-map-marker {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.92);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.28);
            font-size: 12px;
        }
    </style>
@endpush
