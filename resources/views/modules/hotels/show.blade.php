@extends('layouts.master')

@section('page_title', __('ui.modules.hotels.page_title'))
@section('page_subtitle', __('ui.modules.hotels.show_page_subtitle'))
@section('page_actions')
    <a href="{{ route('hotels.index') }}" class="btn-ghost">{{ __('ui.common.back') }}</a>
    <a href="{{ route('hotels.edit', $hotel) }}" class="btn-primary">{{ __('ui.common.edit') }}</a>
@endsection

@section('content')
    @php
        $today = now()->toDateString();
        $isActive = ! $hotel->trashed() && (string) $hotel->status === 'active';
        $hotelStatus = $isActive ? 'active' : 'inactive';

        $resolveThumbnailImageUrl = function (?string $path, array $directories = []): ?string {
            return \App\Support\ImageThumbnailGenerator::resolvePublicUrl($path, $directories);
        };
        $resolveOriginalImageUrl = function (?string $path, array $directories = []): ?string {
            return \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($path, $directories);
        };

        $renderRichText = function (?string $value): string {
            $content = trim((string) $value);
            if ($content === '') {
                return '-';
            }

            // Keep formatting tags so rich text stays styled, but hide raw HTML code.
            $content = strip_tags($content, '<p><br><ul><ol><li><strong><b><em><i><u><blockquote><h1><h2><h3><h4><h5><h6><a><span>');
            $content = str_ireplace('javascript:', '', $content);

            return $content;
        };

        $galleryItems = collect([]);
        if (filled($hotel->cover)) {
            $coverThumbUrl = $resolveThumbnailImageUrl($hotel->cover, ['hotels/cover', 'hotels/covers']);
            $coverFullUrl = $resolveOriginalImageUrl($hotel->cover, ['hotels/cover', 'hotels/covers']) ?: $coverThumbUrl;
            $galleryItems->push([
                'thumbnail_url' => $coverThumbUrl,
                'full_url' => $coverFullUrl,
                'label' => __('ui.modules.hotels.hotel_cover'),
            ]);
        }

        foreach (($hotel->rooms ?? collect()) as $room) {
            if (! filled($room->cover)) {
                continue;
            }

            $roomThumbUrl = $resolveThumbnailImageUrl($room->cover, ['hotels/rooms']);
            $roomFullUrl = $resolveOriginalImageUrl($room->cover, ['hotels/rooms']) ?: $roomThumbUrl;
            $galleryItems->push([
                'thumbnail_url' => $roomThumbUrl,
                'full_url' => $roomFullUrl,
                'label' => (string) (__('ui.modules.hotels.room') . ': ' . ($room->rooms ?: __('ui.modules.hotels.cover'))),
            ]);
        }

        $galleryItems = $galleryItems
            ->filter(fn ($item) => filled($item['thumbnail_url'] ?? null) || filled($item['full_url'] ?? null))
            ->unique('full_url')
            ->values();

        $firstGalleryImage = $galleryItems->first()['full_url'] ?? null;

        $priceRows = collect($hotel->prices ?? [])->sortBy([
            ['end_date', 'desc'],
            ['start_date', 'desc'],
        ])->values();
    @endphp

    <div class="module-page module-page--hotels">
        <div class="module-grid-9-3 hotel-detail-print-grid">
            <div class="module-grid-main">
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.gallery') }}</h3>
                    @if ($galleryItems->isNotEmpty())
                        <div class="mt-3 space-y-3">
                            <button
                                type="button"
                                class="block w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                                data-hotel-gallery-open="1"
                            >
                                <img id="hotel-gallery-main-image" src="{{ $firstGalleryImage }}" alt="{{ __('ui.modules.hotels.hotel_image_alt') }}" class="h-72 w-full object-cover object-center md:h-[28rem]">
                            </button>
                            <div class="grid grid-cols-3 gap-2 md:grid-cols-6">
                                @foreach ($galleryItems as $index => $item)
                                    <button
                                        type="button"
                                        class="relative overflow-hidden rounded-md border border-gray-200 dark:border-gray-700"
                                        data-hotel-gallery-thumb="{{ $index }}"
                                        data-hotel-gallery-src="{{ $item['full_url'] }}"
                                        title="{{ $item['label'] }}"
                                    >
                                        <img src="{{ $item['thumbnail_url'] ?: $item['full_url'] }}" alt="{{ $item['label'] }}" class="h-16 w-full object-cover">
                                        <span class="pointer-events-none absolute bottom-1 left-1 right-1 truncate rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-medium text-white">
                                            {{ $item['label'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.no_gallery') }}</p>
                    @endif
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.hotels.hotel_information') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.hotel_name') }}</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $hotel->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.code') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->code ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.property') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->region ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.destination') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->destination?->province ?: ($hotel->destination?->name ?? '-') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.city') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->city ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.province') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->province ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.country') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->country ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.contact_person') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->contact_person ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.phone') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->phone ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.check_in') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->check_in_time ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.check_out') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->check_out_time ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.min_stay') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->min_stay ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.max_stay') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->max_stay ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.airport_distance') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->airport_distance ? __('ui.modules.hotels.distance_km', ['distance' => $hotel->airport_distance]) : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.airport_duration') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->airport_duration ? __('ui.modules.hotels.duration_min', ['duration' => $hotel->airport_duration]) : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}</p>
                            <div class="mt-1"><x-status-badge :status="$hotelStatus" size="xs" /></div>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('ui.modules.airports.address') }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hotel->address ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="app-card p-5">
                    @include('modules.hotels.partials._location-map', [
                        'mapTitle' => __('ui.modules.hotels.location_on_map'),
                        'mapHeightClass' => 'h-[320px]',
                        'latValue' => $hotel->latitude,
                        'lngValue' => $hotel->longitude,
                        'interactive' => false,
                    ])
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.rooms') }}</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.hotels.room') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.view') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.hotels.capacity_label') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.hotels.beds') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($hotel->rooms as $room)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $room->rooms }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $room->roomView?->name ?? '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ (int) ($room->capacity_adult ?? 0) }}A / {{ (int) ($room->capacity_child ?? 0) }}C</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $room->beds ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.no_room_data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.rates') }}</h3>
                    <div class="mt-3 overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.hotels.room') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.start') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.end') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.hotels.contract_rate') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.tourist_attractions.markup') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.hotels.publish_rate') }}</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($priceRows as $price)
                                    @php
                                        $start = (string) ($price->start_date ?? '');
                                        $end = (string) ($price->end_date ?? '');
                                        $isExpired = $end !== '' && $end < $today;
                                        $isUpcoming = $start !== '' && $start > $today;
                                        $periodStatusKey = $isExpired ? 'expired' : ($isUpcoming ? 'upcoming' : 'active');
                                        $periodStatusLabel = $isExpired ? __('ui.modules.hotels.expired') : ($isUpcoming ? __('ui.modules.hotels.upcoming') : __('ui.common.active'));
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $price->room?->rooms ?? '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $start ?: '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $end ?: '-' }}</td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200"><x-money :amount="(float) ($price->contract_rate ?? 0)" currency="IDR" /></td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                            @if (($price->markup_type ?? 'fixed') === 'percent')
                                                {{ rtrim(rtrim(number_format((float) ($price->markup ?? 0), 2, '.', ''), '0'), '.') }}%
                                            @else
                                                <x-money :amount="(float) ($price->markup ?? 0)" currency="IDR" />
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200"><x-money :amount="(float) ($price->publish_rate ?? 0)" currency="IDR" /></td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                            <x-status-badge :status="$periodStatusKey" :label="$periodStatusLabel" size="xs" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.no_rate_data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="app-card p-5">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.hotels.descriptions_policies') }}</h3>
                    <div class="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.description') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($hotel->description) !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.facilities') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($hotel->facility) !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.hotels.additional_info') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($hotel->additional_info) !!}</div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.cancellation_policy') }}</p>
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-200 rich-text">{!! $renderRichText($hotel->cancellation_policy) !!}</div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="module-grid-side hotel-detail-print-hide">
                <div class="app-card p-5">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.quick_actions') }}</p>
                    <a href="{{ route('hotels.edit', $hotel) }}" class="mb-3 btn-primary w-full justify-center">{{ __('ui.modules.hotels.edit_hotel') }}</a>
                    <form action="{{ route('hotels.toggle-status', $hotel->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button
                            type="submit"
                            onclick="return confirm('{{ $isActive ? __('ui.modules.hotels.confirm_deactivate') : __('ui.modules.hotels.confirm_activate') }}')"
                            class="{{ $isActive ? 'btn-muted' : 'btn-primary' }} w-full justify-center"
                        >
                            {{ $isActive ? __('ui.common.deactivate') : __('ui.common.activate') }}
                        </button>
                    </form>
                </div>

                <div class="app-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.modules.hotels.contact_location') }}</p>
                    <dl class="mt-3">
                        @if (filled($hotel->web))
                            <div class="grid grid-cols-[110px_1fr] gap-2">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.modules.hotels.website') }}</dt>
                                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">
                                    <a href="{{ $hotel->web }}" target="_blank" rel="noopener" class="text-sky-600 hover:underline dark:text-sky-300">{{ $hotel->web }}</a>
                                </dd>
                            </div>
                        @endif
                        @if (filled($hotel->map))
                            <div class="grid grid-cols-[110px_1fr] gap-2">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.modules.hotels.map_url') }}</dt>
                                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">
                                    <a href="{{ $hotel->map }}" target="_blank" rel="noopener" class="text-sky-600 hover:underline dark:text-sky-300">{{ __('ui.modules.airports.open_map') }}</a>
                                </dd>
                            </div>
                        @endif
                        @if (!is_null($hotel->latitude))
                            <div class="grid grid-cols-[110px_1fr] gap-2">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.modules.airports.latitude') }}</dt>
                                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $hotel->latitude }}</dd>
                            </div>
                        @endif
                        @if (!is_null($hotel->longitude))
                            <div class="grid grid-cols-[110px_1fr] gap-2">
                                <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.modules.airports.longitude') }}</dt>
                                <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">{{ $hotel->longitude }}</dd>
                            </div>
                        @endif
                        <div class="grid grid-cols-[110px_1fr] gap-2">
                            <dt class="text-xs text-slate-500 dark:text-slate-400">{{ __('ui.common.status') }}</dt>
                            <dd class="text-sm text-slate-700 dark:text-slate-200 break-words">
                                <x-status-badge :status="$hotelStatus" size="xs" />
                            </dd>
                        </div>
                    </dl>
                </div>

                @include('partials._audit-info', ['record' => $hotel])
            </aside>
        </div>
    </div>

    @if ($galleryItems->isNotEmpty())
        <div id="hotel-gallery-lightbox" class="fixed inset-0 z-[100] hidden bg-black/85 p-4">
            <div class="mx-auto flex h-full w-full max-w-6xl items-center justify-center">
                <button type="button" class="absolute right-5 top-5 rounded-md border border-white/30 px-3 py-1 text-xs font-semibold text-white" data-hotel-gallery-close="1">{{ __('ui.common.close') }}</button>
                <button type="button" class="absolute left-4 rounded-md border border-white/30 px-3 py-2 text-xs font-semibold text-white" data-hotel-gallery-prev="1">{{ __('ui.modules.hotels.prev') }}</button>
                <img id="hotel-gallery-lightbox-image" src="{{ $firstGalleryImage }}" alt="{{ __('ui.modules.hotels.hotel_gallery_full_alt') }}" class="max-h-[90vh] max-w-full rounded-lg object-contain">
                <button type="button" class="absolute right-4 rounded-md border border-white/30 px-3 py-2 text-xs font-semibold text-white" data-hotel-gallery-next="1">{{ __('ui.modules.hotels.next') }}</button>
            </div>
        </div>
    @endif
@endsection

@push('styles')
    <style>
        @media print {
            .hotel-detail-print-hide,
            .app-sidebar,
            .app-topbar,
            .app-page-header__actions,
            .page-spinner {
                display: none !important;
            }

            .hotel-detail-print-grid {
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
                const mainImage = document.getElementById('hotel-gallery-main-image');
                const lightbox = document.getElementById('hotel-gallery-lightbox');
                const lightboxImage = document.getElementById('hotel-gallery-lightbox-image');
                if (!mainImage || !lightbox || !lightboxImage) return;

                const thumbs = Array.from(document.querySelectorAll('[data-hotel-gallery-thumb]'));
                const sources = thumbs.map((btn) => btn.getAttribute('data-hotel-gallery-src')).filter(Boolean);
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
                        const idx = Number(btn.getAttribute('data-hotel-gallery-thumb') || '0');
                        setImage(Number.isFinite(idx) ? idx : 0);
                    });
                });

                document.querySelector('[data-hotel-gallery-open="1"]')?.addEventListener('click', () => {
                    lightbox.classList.remove('hidden');
                });

                lightbox.querySelector('[data-hotel-gallery-close="1"]')?.addEventListener('click', () => {
                    lightbox.classList.add('hidden');
                });

                lightbox.querySelector('[data-hotel-gallery-prev="1"]')?.addEventListener('click', () => {
                    setImage(currentIndex - 1);
                });

                lightbox.querySelector('[data-hotel-gallery-next="1"]')?.addEventListener('click', () => {
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
