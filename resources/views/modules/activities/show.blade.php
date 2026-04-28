@extends('layouts.master')

@section('page_title', ui_phrase('modules_activities_page_title'))
@section('page_subtitle', ui_phrase('modules_activities_show_page_subtitle'))
@section('page_actions')
    <a href="{{ route('activities.index') }}" class="btn-ghost">{{ ui_phrase('common_back') }}</a>
    <a href="{{ route('activities.edit', $activity) }}" class="btn-primary">{{ ui_phrase('common_edit') }}</a>
@endsection

@section('content')
    @php
        $gallery = is_array($activity->gallery_images) ? array_values($activity->gallery_images) : [];
        $galleryItems = collect($gallery)
            ->map(function ($path, $index) {
                $thumbnailUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl((string) $path);
                $originalUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl((string) $path);
                $fullUrl = filled($originalUrl) ? $originalUrl : $thumbnailUrl;
                if (! filled($thumbnailUrl) && ! filled($fullUrl)) {
                    return null;
                }

                return [
                    'path' => (string) $path,
                    'thumbnail_url' => $thumbnailUrl,
                    'full_url' => $fullUrl,
                    'index' => $index,
                ];
            })
            ->filter()
            ->values();
    @endphp
    @php($isActive = ! $activity->trashed())
    @php($firstGalleryImage = $galleryItems->first()['full_url'] ?? null)
    <div class="module-page module-page--activities">
        <div class="module-grid-8-4 activity-detail-print-grid">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_gallery') }}</h3>
                    @if ($galleryItems->isNotEmpty())
                        <div class="mt-3 space-y-3">
                            <button
                                type="button"
                                class="block w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                data-gallery-open="1"
                            >
                                <img id="activity-gallery-main-image" src="{{ $firstGalleryImage }}" alt="{{ ui_phrase('modules_activities_activity_image_alt') }}" class="h-72 w-full object-cover object-center md:h-[28rem]">
                            </button>
                            <div class="grid grid-cols-3 gap-2 md:grid-cols-6">
                                @foreach ($galleryItems as $item)
                                    <button
                                        type="button"
                                        class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
                                        data-gallery-thumb="{{ $loop->index }}"
                                        data-gallery-src="{{ $item['full_url'] }}"
                                    >
                                        <img src="{{ $item['thumbnail_url'] ?: $item['full_url'] }}" alt="{{ ui_phrase('modules_activities_activity_thumbnail_alt', ['number' => $loop->iteration]) }}" class="h-16 w-full object-cover" loading="lazy" decoding="async">
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_hotels_no_gallery') }}</p>
                    @endif
                </div>

                <div class="app-card p-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_activity_name') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $activity->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_type') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $activity->activityType->name ?? $activity->activity_type ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_vendor') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $activity->vendor->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_duration') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ (int) ($activity->duration_minutes ?? 0) }} min</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_capacity') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $activity->capacity_min ?? '-' }} - {{ $activity->capacity_max ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('common_status') }}</p>
                            <div class="mt-1"><x-status-badge :status="$activity->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('modules_activities_pricing_idr') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_adult_contract_rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($activity->adult_contract_rate ?? 0)" currency="IDR" /></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_child_contract_rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($activity->child_contract_rate ?? 0)" currency="IDR" /></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_adult_publish_rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($activity->adult_publish_rate ?? 0)" currency="IDR" /></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_adult_markup') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if (($activity->adult_markup_type ?? 'fixed') === 'percent')
                                    {{ rtrim(rtrim(number_format((float) ($activity->adult_markup ?? 0), 2, '.', ''), '0'), '.') }}%
                                @else
                                    <x-money :amount="(float) ($activity->adult_markup ?? 0)" currency="IDR" />
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_child_publish_rate') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100"><x-money :amount="(float) ($activity->child_publish_rate ?? 0)" currency="IDR" /></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_activities_child_markup') }}</p>
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                @if (($activity->child_markup_type ?? 'fixed') === 'percent')
                                    {{ rtrim(rtrim(number_format((float) ($activity->child_markup ?? 0), 2, '.', ''), '0'), '.') }}%
                                @else
                                    <x-money :amount="(float) ($activity->child_markup ?? 0)" currency="IDR" />
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('modules_activities_description_policy') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_benefits') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $activity->benefits ?: '-' !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_description') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $activity->descriptions ?: '-' !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_includes') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $activity->includes ?: '-' !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_excludes') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $activity->excludes ?: '-' !!}</div>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_cancellation_policy') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $activity->cancellation_policy ?: '-' !!}</div>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_notes') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $activity->notes ?: '-' !!}</div>
                        </div>
                    </div>
                </div>

            </div>

            <aside class="module-grid-side activity-detail-print-hide">
                <div class="app-card p-5">
                    <p class="text-xs mb-3 font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ ui_phrase('common_quick_actions') }}</p>
                    <a href="{{ route('activities.edit', $activity) }}" class="btn-primary mb-3 w-full justify-center">{{ ui_phrase('modules_activities_edit_activity') }}</a>
                    <form action="{{ route('activities.toggle-status', $activity->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? ui_phrase('modules_activities_confirm_deactivate') : ui_phrase('modules_activities_confirm_activate') }}')"
                            class="{{ $isActive ? 'btn-muted' : 'btn-primary' }} mb-3 w-full justify-center"
                        >
                            {{ $isActive ? ui_phrase('common_deactivate') : ui_phrase('common_activate') }}
                        </button>
                    </form>
                </div>
                @include('modules.activities.partials._vendor-info', ['vendor' => $activity->vendor])
                @include('partials._audit-info', ['record' => $activity])
            </aside>
        </div>
    </div>

    @if ($galleryItems->isNotEmpty())
        <div id="activity-gallery-lightbox" class="fixed inset-0 z-[100] hidden bg-black/85 p-4">
            <div class="mx-auto flex h-full w-full max-w-6xl items-center justify-center">
                <button type="button" class="absolute right-5 top-5 rounded-md border border-white/30 px-3 py-1 text-xs font-semibold text-white" data-gallery-close="1">{{ ui_phrase('common_close') }}</button>
                <button type="button" class="absolute left-4 rounded-md border border-white/30 px-3 py-2 text-xs font-semibold text-white" data-gallery-prev="1">{{ ui_phrase('modules_hotels_prev') }}</button>
                <img id="activity-gallery-lightbox-image" src="{{ $firstGalleryImage }}" alt="{{ ui_phrase('modules_activities_activity_gallery_full_alt') }}" class="max-h-[90vh] max-w-full rounded-lg object-contain">
                <button type="button" class="absolute right-4 rounded-md border border-white/30 px-3 py-2 text-xs font-semibold text-white" data-gallery-next="1">{{ ui_phrase('modules_hotels_next') }}</button>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        @media print {
            .activity-detail-print-hide,
            .app-sidebar,
            .app-topbar,
            .app-page-header__actions,
            .page-spinner {
                display: none !important;
            }

            .activity-detail-print-grid {
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
                const mainImage = document.getElementById('activity-gallery-main-image');
                const lightbox = document.getElementById('activity-gallery-lightbox');
                const lightboxImage = document.getElementById('activity-gallery-lightbox-image');
                if (!mainImage || !lightbox || !lightboxImage) return;

                const thumbs = Array.from(document.querySelectorAll('[data-gallery-thumb]'));
                const sources = thumbs.map((btn) => btn.getAttribute('data-gallery-src')).filter(Boolean);
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
                        const idx = Number(btn.getAttribute('data-gallery-thumb') || '0');
                        setImage(Number.isFinite(idx) ? idx : 0);
                    });
                });

                document.querySelector('[data-gallery-open="1"]')?.addEventListener('click', () => {
                    lightbox.classList.remove('hidden');
                });

                lightbox.querySelector('[data-gallery-close="1"]')?.addEventListener('click', () => {
                    lightbox.classList.add('hidden');
                });

                lightbox.querySelector('[data-gallery-prev="1"]')?.addEventListener('click', () => {
                    setImage(currentIndex - 1);
                });

                lightbox.querySelector('[data-gallery-next="1"]')?.addEventListener('click', () => {
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
