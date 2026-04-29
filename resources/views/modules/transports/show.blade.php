@extends('layouts.master')

@section('page_title', ui_phrase('show page title'))
@section('page_subtitle', ui_phrase('show page subtitle'))
@section('page_actions')
    <a href="{{ route('transports.edit', $transport) }}" class="btn-primary">{{ ui_phrase('Edit') }}</a>
    <a href="{{ route('transports.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    @php
        $isActive = ! $transport->trashed() && (bool) ($transport->is_active ?? false);
        $transportStatus = $isActive ? 'active' : 'inactive';
        $images = is_array($transport->images) ? array_values($transport->images) : [];

        $galleryItems = collect($images)
            ->map(function ($path, $index) {
                $thumbnailUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl((string) $path);
                $fullUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl((string) $path) ?: $thumbnailUrl;
                if (! filled($thumbnailUrl) && ! filled($fullUrl)) {
                    return null;
                }

                return [
                    'thumbnail_url' => $thumbnailUrl,
                    'full_url' => $fullUrl,
                    'label' => ui_phrase('Vehicle') . ' #' . ($index + 1),
                ];
            })
            ->filter()
            ->values();

        $firstGalleryImage = $galleryItems->first()['full_url'] ?? null;

        $renderRichText = function (?string $value): string {
            $content = trim((string) $value);
            if ($content === '') {
                return '-';
            }

            $content = strip_tags($content, '<p><br><ul><ol><li><strong><b><em><i><u><blockquote><h1><h2><h3><h4><h5><h6><a><span>');
            $content = str_ireplace('javascript:', '', $content);

            return $content;
        };
    @endphp

    <div class="space-y-5 module-page module-page--transports">
        <div class="module-grid-9-3 transport-detail-print-grid">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Gallery') }}</h3>
                    @if ($galleryItems->isNotEmpty())
                        <div class="mt-3">
                            <button
                                type="button"
                                class="mb-3 block w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                data-transport-gallery-open="1"
                            >
                                <img id="transport-gallery-main-image" src="{{ $firstGalleryImage }}" alt="{{ ui_phrase('Transport Unit Detail') }}" class="h-72 w-full object-cover object-center md:h-[28rem]">
                            </button>
                            <div class="grid grid-cols-3 gap-2 md:grid-cols-6">
                                @foreach ($galleryItems as $index => $item)
                                    <button
                                        type="button"
                                        class="relative overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
                                        data-transport-gallery-thumb="{{ $index }}"
                                        data-transport-gallery-src="{{ $item['full_url'] }}"
                                        title="{{ $item['label'] }}"
                                    >
                                        <img src="{{ $item['thumbnail_url'] ?: $item['full_url'] }}" alt="{{ $item['label'] }}" class="h-16 w-full object-cover" loading="lazy" decoding="async">
                                        <span class="pointer-events-none absolute bottom-1 left-1 right-1 truncate rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-medium text-white">
                                            {{ $item['label'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('no gallery') }}</p>
                    @endif
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Transport Unit Detail') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Code') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->code ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Name') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Type') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->transport_type ? ucfirst(str_replace('_', ' ', (string) $transport->transport_type)) : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Vendor') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->vendor?->name ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Vehicle') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->brand_model ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Capacity') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ ui_phrase('seats luggage', ['seats' => (int) ($transport->seat_capacity ?? 0), 'luggage' => $transport->luggage_capacity ?? '-']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Transmission') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->transmission ? ucfirst((string) $transport->transmission) : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Fuel Type') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $transport->fuel_type ? ucfirst(str_replace('_', ' ', (string) $transport->fuel_type)) : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('AC/Driver') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">
                                {{ $transport->air_conditioned ? 'AC' : ui_phrase('Non-AC') }} | {{ $transport->with_driver ? ui_phrase('With Driver') : ui_phrase('Without Driver') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}</p>
                            <div class="mt-1"><x-status-badge :status="$transportStatus" size="xs" /></div>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Rates') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Contract Rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if ($transport->contract_rate !== null)
                                    <x-money :amount="(float) $transport->contract_rate" currency="IDR" />
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('attractions markup') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if (($transport->markup_type ?? 'fixed') === 'percent')
                                    {{ rtrim(rtrim(number_format((float) ($transport->markup ?? 0), 2, '.', ''), '0'), '.') }}%
                                @else
                                    <x-money :amount="(float) ($transport->markup ?? 0)" currency="IDR" />
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('attractions publish') }} {{ ui_phrase('Rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if ($transport->publish_rate !== null)
                                    <x-money :amount="(float) $transport->publish_rate" currency="IDR" />
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Overtime Rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if ($transport->overtime_rate !== null)
                                    <x-money :amount="(float) $transport->overtime_rate" currency="IDR" />
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Description & Policy') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Description') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($transport->description) !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Includes') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($transport->inclusions) !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Excludes') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($transport->exclusions) !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Cancellation Policy') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($transport->cancellation_policy) !!}</div>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Notes') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($transport->notes) !!}</div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="module-grid-side transport-detail-print-hide">
                <div class="app-card p-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('Quick Actions') }}</p>
                    <a href="{{ route('transports.edit', $transport) }}" class="mb-3 btn-primary w-full justify-center">{{ ui_phrase('Edit') }}</a>
                    <form action="{{ route('transports.toggle-status', $transport->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate') }}')"
                            class="{{ $isActive ? 'btn-muted' : 'btn-primary' }} mb-3  w-full justify-center"
                        >
                            {{ $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate') }}
                        </button>
                    </form>
                </div>

                @include('modules.activities.partials._vendor-info', ['vendor' => $transport->vendor])
                @include('partials._audit-info', ['record' => $transport])
            </aside>
        </div>
    </div>

    @if ($galleryItems->isNotEmpty())
        <div id="transport-gallery-lightbox" class="fixed inset-0 z-[100] hidden bg-black/85 p-4">
            <div class="mx-auto flex h-full w-full max-w-6xl items-center justify-center">
                <button type="button" class="absolute right-5 top-5 rounded-md border border-white/30 px-3 py-1 text-xs font-semibold text-white" data-transport-gallery-close="1">{{ ui_phrase('Close') }}</button>
                <button type="button" class="absolute left-4 rounded-md border border-white/30 px-3 py-2 text-xs font-semibold text-white" data-transport-gallery-prev="1">{{ ui_phrase('Prev') }}</button>
                <img id="transport-gallery-lightbox-image" src="{{ $firstGalleryImage }}" alt="{{ ui_phrase('Transport Unit Detail') }}" class="max-h-[90vh] max-w-full rounded-lg object-contain">
                <button type="button" class="absolute right-4 rounded-md border border-white/30 px-3 py-2 text-xs font-semibold text-white" data-transport-gallery-next="1">{{ ui_phrase('Next') }}</button>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        @media print {
            .transport-detail-print-hide,
            .app-sidebar,
            .app-topbar,
            .app-page-header__actions,
            .page-spinner {
                display: none !important;
            }

            .transport-detail-print-grid {
                grid-template-columns: minmax(0, 1fr) !important;
            }

            .app-card {
                box-shadow: none !important;
                border-color: #d1d5db !important;
            }
        }
    </style>
@endpush

@if ($galleryItems->isNotEmpty())
    @push('scripts')
        <script>
            (function () {
                const mainImage = document.getElementById('transport-gallery-main-image');
                const lightbox = document.getElementById('transport-gallery-lightbox');
                const lightboxImage = document.getElementById('transport-gallery-lightbox-image');
                if (!mainImage || !lightbox || !lightboxImage) return;

                const thumbs = Array.from(document.querySelectorAll('[data-transport-gallery-thumb]'));
                const sources = thumbs.map((btn) => btn.getAttribute('data-transport-gallery-src')).filter(Boolean);
                if (!sources.length) return;

                let currentIndex = 0;

                const setImage = (index) => {
                    if (!sources.length) return;
                    currentIndex = (index + sources.length) % sources.length;
                    const src = sources[currentIndex];
                    mainImage.src = src;
                    lightboxImage.src = src;
                };

                thumbs.forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const idx = Number(btn.getAttribute('data-transport-gallery-thumb') || '0');
                        setImage(Number.isFinite(idx) ? idx : 0);
                    });
                });

                document.querySelector('[data-transport-gallery-open="1"]')?.addEventListener('click', () => {
                    lightbox.classList.remove('hidden');
                });

                lightbox.querySelector('[data-transport-gallery-close="1"]')?.addEventListener('click', () => {
                    lightbox.classList.add('hidden');
                });

                lightbox.querySelector('[data-transport-gallery-prev="1"]')?.addEventListener('click', () => {
                    setImage(currentIndex - 1);
                });

                lightbox.querySelector('[data-transport-gallery-next="1"]')?.addEventListener('click', () => {
                    setImage(currentIndex + 1);
                });

                lightbox.addEventListener('click', (event) => {
                    if (event.target === lightbox) {
                        lightbox.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (lightbox.classList.contains('hidden')) return;

                    if (event.key === 'Escape') {
                        event.preventDefault();
                        lightbox.classList.add('hidden');
                        return;
                    }

                    if (event.key === 'ArrowLeft') {
                        event.preventDefault();
                        setImage(currentIndex - 1);
                        return;
                    }

                    if (event.key === 'ArrowRight') {
                        event.preventDefault();
                        setImage(currentIndex + 1);
                    }
                });
            })();
        </script>
    @endpush
@endif

