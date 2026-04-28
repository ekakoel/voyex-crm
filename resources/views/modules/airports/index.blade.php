@extends('layouts.master')
@section('page_title', ui_phrase('modules_airports_page_title'))
@section('page_subtitle', ui_phrase('modules_airports_page_subtitle'))
@section('page_actions')
    <a href="{{ route('airports.create') }}" class="btn-primary">{{ ui_phrase('modules_airports_add_airport') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--airports" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_filters') }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('airports.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                        data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}"
                            placeholder="{{ ui_phrase('modules_airports_search') }}" class="app-input sm:col-span-2"
                            data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                    {{ ui_phrase('index_per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('airports.index') }}" class="btn-ghost"
                                data-service-filter-reset>{{ ui_phrase('common_reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main" data-service-filter-results>
                @if (session('success'))
                    <div
                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('modules_airports_airport') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('common_location') }}</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('common_status') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('common_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($airports as $index => $airport)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        @php($isActive = !$airport->trashed())
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                                            {{ ++$index }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>{{ $airport->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $airport->country ?: '-' }}</div>
                                            <div class="text-xs text-indigo-600 dark:text-indigo-300">
                                                {{ $airport->destination?->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>
                                                {{ trim(($airport->city ?? '') . ($airport->city && $airport->province ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $airport->country ?: '-' }}</div>
                                            <div class="text-xs text-indigo-600 dark:text-indigo-300">
                                                {{ $airport->destination?->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('airports.show', $airport) }}" class="btn-outline-sm"
                                                    title="{{ ui_phrase('common_view') }}"
                                                    aria-label="{{ ui_phrase('common_view') }}"><i
                                                        class="fa-solid fa-eye"></i><span
                                                        class="sr-only">{{ ui_phrase('common_view') }}</span></a>
                                                <a href="{{ route('airports.edit', $airport) }}" class="btn-secondary-sm"
                                                    title="{{ ui_phrase('common_edit') }}"
                                                    aria-label="{{ ui_phrase('common_edit') }}"><i
                                                        class="fa-solid fa-pen"></i><span
                                                        class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                                <form action="{{ route('airports.toggle-status', $airport->id) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        onclick="return confirm('{{ $isActive ? ui_phrase('modules_airports_confirm_deactivate') : ui_phrase('modules_airports_confirm_activate') }}')"
                                                        class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('common_deactivate') : ui_phrase('common_activate') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_airports')]) }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($airports as $airport)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $airport->code }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $airport->name }}</p>
                                </div>
                                <span
                                    class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $airport->country ?: '-' }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('common_location') }}</div>
                                <div>
                                    {{ trim(($airport->city ?? '') . ($airport->city && $airport->province ? ', ' : '') . ($airport->province ?? '')) ?: '-' }}
                                </div>
                                <div>{{ ui_phrase('common_country') }}</div>
                                <div>{{ $airport->country ?: '-' }}</div>
                                <div>{{ ui_phrase('common_destination') }}</div>
                                <div>{{ $airport->destination?->name ?? '-' }}</div>
                                <div>{{ ui_phrase('common_status') }}</div>
                                <div><x-status-badge :status="$airport->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('airports.show', $airport) }}" class="btn-outline-sm"
                                    title="{{ ui_phrase('common_view') }}" aria-label="{{ ui_phrase('common_view') }}"><i
                                        class="fa-solid fa-eye"></i><span
                                        class="sr-only">{{ ui_phrase('common_view') }}</span></a>
                                <a href="{{ route('airports.edit', $airport) }}" class="btn-secondary-sm"
                                    title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i
                                        class="fa-solid fa-pen"></i><span
                                        class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                <form action="{{ route('airports.toggle-status', $airport->id) }}" method="POST"
                                    class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        onclick="return confirm('{{ $airport->trashed() ? ui_phrase('modules_airports_confirm_activate') : ui_phrase('modules_airports_confirm_deactivate') }}')"
                                        class="{{ $airport->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $airport->trashed() ? ui_phrase('common_activate') : ui_phrase('common_deactivate') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_airports')]) }}</div>
                    @endforelse
                </div>
                <div>{{ $airports->links() }}</div>
            </div>
        </div>
    </div>
@endsection
