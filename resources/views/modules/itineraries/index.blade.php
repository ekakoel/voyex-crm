@extends('layouts.master')
@section('page_title', __('ui.modules.itineraries.page_title'))
@section('page_subtitle', __('ui.modules.itineraries.page_subtitle'))
@section('page_actions')
    <a href="{{ route('itineraries.create') }}" class="btn-primary">{{ __('ui.modules.itineraries.create_itinerary') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--itineraries" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="min-w-0 space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('itineraries.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="title" value="{{ request('title') }}" placeholder="{{ __('ui.common.title') }}" class="app-input sm:col-span-2" data-service-filter-input>
                        <select name="destination_id" class="app-input sm:col-span-2" data-service-filter-input>
                            <option value="">{{ __('ui.modules.itineraries.all_destinations') }}</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>{{ $destination->name }}</option>
                            @endforeach
                        </select>
                        <input name="duration" type="number" min="1" value="{{ request('duration') }}" placeholder="{{ __('ui.modules.itineraries.duration_days') }}" class="app-input" data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('itineraries.index') }}" class="btn-ghost" data-service-filter-reset>{{ __('ui.common.reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="min-w-0 space-y-4 xl:col-span-9" data-service-filter-results>
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.itineraries.inquiry') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.duration') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.common.status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ __('ui.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($itineraries as $index => $itinerary)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div class="font-medium">{{ $itinerary->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.itineraries.by', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), 'System') ?: '-']) }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if ($itinerary->inquiry)
                                    <span class="font-medium">{{ $itinerary->inquiry?->inquiry_number ?? '-' }}</span>
                                    @if (!empty($itinerary->inquiry?->customer?->name))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">| {{ $itinerary->inquiry?->customer?->name }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.itineraries.independent') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $itinerary->duration_days }}D{{ $itinerary->duration_nights > 0 ? "/".$itinerary->duration_nights."N":""; }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <x-status-badge :status="$itinerary->status" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="{{ __('ui.common.view') }}" aria-label="{{ __('ui.common.view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.view') }}</span></a>
                                @if (! $itinerary->trashed())
                                    <form action="{{ route('itineraries.duplicate', $itinerary) }}" method="POST" class="inline" onsubmit="if (!confirm('{{ __('ui.modules.itineraries.confirm_duplicate') }}')) { return false; } const button = this.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); } return true;">
                                        @csrf
                                        <button type="submit" class="btn-ghost-sm" title="{{ __('ui.common.duplicate') }}" aria-label="{{ __('ui.common.duplicate') }}">
                                            <i class="fa-solid fa-copy"></i><span class="sr-only">{{ __('ui.common.duplicate') }}</span>
                                        </button>
                                    </form>
                                @endif
                                @can('update', $itinerary)
                                    @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                                        <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                    @endif
                                @endcan</div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.itineraries')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($itineraries as $itinerary)
                <div class="app-card p-4">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $itinerary->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.itineraries.by', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), 'System') ?: '-']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('ui.modules.itineraries.inquiry') }}:
                        {{ $itinerary->inquiry?->inquiry_number ?? __('ui.modules.itineraries.independent') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.modules.itineraries.day_count', ['count' => $itinerary->duration_days]) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('ui.common.status') }}: {{ ucfirst($itinerary->status ?? 'pending') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="{{ __('ui.common.view') }}" aria-label="{{ __('ui.common.view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ __('ui.common.view') }}</span></a>
                        @if (! $itinerary->trashed())
                            <form action="{{ route('itineraries.duplicate', $itinerary) }}" method="POST" class="inline" onsubmit="if (!confirm('{{ __('ui.modules.itineraries.confirm_duplicate') }}')) { return false; } const button = this.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); } return true;">
                                @csrf
                                <button type="submit" class="btn-ghost-sm" title="{{ __('ui.common.duplicate') }}" aria-label="{{ __('ui.common.duplicate') }}">
                                    <i class="fa-solid fa-copy"></i><span class="sr-only">{{ __('ui.common.duplicate') }}</span>
                                </button>
                            </form>
                        @endif
                        @can('update', $itinerary)
                            @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                                <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                            @endif
                        @endcan</div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.no_data_available', ['entity' => __('ui.entities.itineraries')]) }}</div>
            @endforelse
        </div>
        <div>{{ $itineraries->links() }}</div>
            </div>
        </div>
</div>
@endsection

