@extends('layouts.master')
@section('page_title', ui_phrase('modules_itineraries_page_title'))
@section('page_subtitle', ui_phrase('modules_itineraries_page_subtitle'))
@section('page_actions')
    <a href="{{ route('itineraries.create') }}" class="btn-primary">{{ ui_phrase('modules_itineraries_create_itinerary') }}</a>
@endsection
@section('content')
    <div class="space-y-5 module-page module-page--itineraries" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid min-w-0 grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="min-w-0 space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('itineraries.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="title" value="{{ request('title') }}" placeholder="{{ ui_phrase('common_title') }}" class="app-input sm:col-span-2" data-service-filter-input>
                        <select name="destination_id" class="app-input sm:col-span-2" data-service-filter-input>
                            <option value="">{{ ui_phrase('modules_itineraries_all_destinations') }}</option>
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->id }}" @selected((string) request('destination_id') === (string) $destination->id)>{{ $destination->name }}</option>
                            @endforeach
                        </select>
                        <input name="duration" type="number" min="1" value="{{ request('duration') }}" placeholder="{{ ui_phrase('modules_itineraries_duration_days') }}" class="app-input" data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase('index_per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('itineraries.index') }}" class="btn-ghost" data-service-filter-reset>{{ ui_phrase('common_reset') }}</a>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_itineraries_inquiry') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_duration') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('common_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($itineraries as $index => $itinerary)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div class="font-medium">{{ $itinerary->title }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_itineraries_by', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), 'System') ?: '-']) }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if ($itinerary->inquiry)
                                    <span class="font-medium">{{ $itinerary->inquiry?->inquiry_number ?? '-' }}</span>
                                    @if (!empty($itinerary->inquiry?->customer?->name))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">| {{ $itinerary->inquiry?->customer?->name }}</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_itineraries_independent') }}</span>
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
        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="{{ ui_phrase('common_view') }}" aria-label="{{ ui_phrase('common_view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('common_view') }}</span></a>
                                @if (! $itinerary->trashed())
                                    <form action="{{ route('itineraries.duplicate', $itinerary) }}" method="POST" class="inline" onsubmit="if (!confirm('{{ ui_phrase('modules_itineraries_confirm_duplicate') }}')) { return false; } const button = this.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); } return true;">
                                        @csrf
                                        <button type="submit" class="btn-ghost-sm" title="{{ ui_phrase('common_duplicate') }}" aria-label="{{ ui_phrase('common_duplicate') }}">
                                            <i class="fa-solid fa-copy"></i><span class="sr-only">{{ ui_phrase('common_duplicate') }}</span>
                                        </button>
                                    </form>
                                @endif
                                @can('update', $itinerary)
                                    @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                                        <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                    @endif
                                @endcan</div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_itineraries')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($itineraries as $itinerary)
                @php
                    $status = strtolower($itinerary->status ?? 'pending');
                    $statusBg = [
                        'pending' => 'bg-yellow-50 dark:bg-yellow-900/10',
                        'approved' => 'bg-emerald-50 dark:bg-emerald-900/10',
                        'draft' => 'bg-gray-50 dark:bg-gray-800/10',
                        'sent' => 'bg-blue-50 dark:bg-blue-900/10',
                        'rejected' => 'bg-red-50 dark:bg-red-900/10',
                        'final' => 'bg-indigo-50 dark:bg-indigo-900/10',
                    ];
                    $bgClass = $statusBg[$status] ?? 'bg-gray-50 dark:bg-gray-800/10';
                @endphp
                <div class="app-card p-4 {{ $bgClass }}">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $itinerary->title }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_itineraries_by', ['name' => $itinerary->creator?->displayNameFor(auth()->user(), 'System') ?: '-']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ ui_phrase('modules_itineraries_inquiry') }}:
                        {{ $itinerary->inquiry?->inquiry_number ?? ui_phrase('modules_itineraries_independent') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('modules_itineraries_day_count', ['count' => $itinerary->duration_days]) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $itinerary->destination?->name ?? $itinerary->destination ?? '-' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('common_status') }}: {{ ucfirst($itinerary->status ?? 'pending') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-outline-sm" title="{{ ui_phrase('common_view') }}" aria-label="{{ ui_phrase('common_view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('common_view') }}</span></a>
                        @if (! $itinerary->trashed())
                            <form action="{{ route('itineraries.duplicate', $itinerary) }}" method="POST" class="inline" onsubmit="if (!confirm('{{ ui_phrase('modules_itineraries_confirm_duplicate') }}')) { return false; } const button = this.querySelector('button[type=submit]'); if (button) { button.disabled = true; button.classList.add('opacity-60', 'cursor-not-allowed'); } return true;">
                                @csrf
                                <button type="submit" class="btn-ghost-sm" title="{{ ui_phrase('common_duplicate') }}" aria-label="{{ ui_phrase('common_duplicate') }}">
                                    <i class="fa-solid fa-copy"></i><span class="sr-only">{{ ui_phrase('common_duplicate') }}</span>
                                </button>
                            </form>
                        @endif
                        @can('update', $itinerary)
                            @if (!($itinerary->quotation && ($itinerary->quotation->status ?? '') === 'approved') && ! $itinerary->isFinal())
                                <a href="{{ route('itineraries.edit', $itinerary) }}"  class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                            @endif
                        @endcan</div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_itineraries')]) }}</div>
            @endforelse
        </div>
        <div>{{ $itineraries->links() }}</div>
            </div>
        </div>
</div>
@endsection

