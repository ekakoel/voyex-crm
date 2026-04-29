@extends('layouts.master')
@section('page_title', ui_phrase('Inquiries'))
@section('page_subtitle', ui_phrase('Manage inquiry records.'))
@section('page_actions')
    <a href="{{ route('inquiries.create') }}" class="btn-primary">
        {{ ui_phrase('Add Inquiry') }}
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--inquiries" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('Refine your list quickly.') }}</p>
                    </div>
                    <form method="GET" action="{{ route('inquiries.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}"
                            class="app-input sm:col-span-2" data-service-filter-input>
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            @foreach (\App\Models\Inquiry::STATUS_OPTIONS as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ui_phrase((string) $status) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="priority" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Priority') }}</option>
                            @foreach (['low', 'normal', 'high'] as $priority)
                                <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ui_phrase($priority) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="assigned_to" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Assigned') }}</option>
                            @foreach ($assignees as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('inquiries.index') }}" class="btn-ghost" data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-service-filter-results>
                @if (session('success'))
                    <div
                        class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
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
                                        {{ ui_phrase('Customer:') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Priority') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Assigned') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Deadline') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Itinerary') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($inquiries as $index=>$inquiry)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ ++$index }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $inquiry->customer->name ?? '-' }} <br> <x-status-badge :status="$inquiry->status"
                                                size="xs" /></td>

                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ ui_phrase((string) $inquiry->priority) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @if(($inquiry->assigned_to ?? null) === (auth()->user()?->id ?? null))
                                                {{ ui_phrase('You') }}
                                            @else
                                                {{ $inquiry->assignedUser->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @if (($inquiry->itineraries_count ?? 0) > 0)
                                                <span
                                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    {{ ui_phrase('Available (:count)', ['count' => $inquiry->itineraries_count]) }}
                                                </span>
                                                <div class="mt-1 space-y-1">
                                                    @foreach ($inquiry->itineraries->take(2) as $itinerary)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            @if (Route::has('itineraries.show') && auth()->user()->can('module.itineraries.access'))
                                                                <a href="{{ route('itineraries.show', $itinerary) }}"
                                                                    class="text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                                                    {{ $itinerary->title }} ({{ ui_phrase((string) ($itinerary->status ?? 'pending')) }})
                                                                </a>
                                                            @else
                                                                {{ $itinerary->title }} ({{ ui_phrase((string) ($itinerary->status ?? 'pending')) }})
                                                            @endif
                                                        </p>
                                                    @endforeach
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('inquiries.show', $inquiry) }}"
                                                    class="btn-secondary-sm" title="{{ ui_phrase('Detail') }}" aria-label="{{ ui_phrase('Detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('Detail') }}</span></a>
                                                @can('update', $inquiry)
                                                    @if (!in_array(($inquiry->quotation->status ?? ''), ['approved', \App\Models\Quotation::FINAL_STATUS], true) && !$inquiry->isFinal())
                                                        <a href="{{ route('inquiries.edit', $inquiry) }}"
                                                            class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                                    @endif
                                                @endcan
                                                <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}"
                                                    class="btn-outline-sm">{{ ui_phrase('Create Itinerary') }}</a>
</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Inquiries')]) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="md:hidden space-y-3">
                    @forelse ($inquiries as $inquiry)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $inquiry->inquiry_number }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $inquiry->customer->name ?? '-' }}</p>
                                </div>
                                <x-status-badge :status="$inquiry->status" size="xs" />
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Priority') }}</div>
                                <div>{{ ui_phrase((string) $inquiry->priority) }}</div>
                                <div>{{ ui_phrase('Assigned') }}</div>
                                <div>
                                    @if(($inquiry->assigned_to ?? null) === (auth()->user()?->id ?? null))
                                        {{ ui_phrase('You') }}
                                    @else
                                        {{ $inquiry->assignedUser->name ?? '-' }}
                                    @endif
                                </div>
                                <div>{{ ui_phrase('Deadline') }}</div>
                                <div>{{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</div>
                            </div>
                            <div class="mt-3">
                                @if (($inquiry->itineraries_count ?? 0) > 0)
                                    <span
                                        class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        {{ ui_phrase('itinerary available', ['count' => $inquiry->itineraries_count]) }}
                                    </span>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($inquiry->itineraries->take(2) as $itinerary)
                                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                                -
                                                @if (Route::has('itineraries.show') && auth()->user()->can('module.itineraries.access'))
                                                    <a href="{{ route('itineraries.show', $itinerary) }}"
                                                        class="text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                                        {{ $itinerary->title }} ({{ ui_phrase((string) ($itinerary->status ?? 'pending')) }})
                                                    </a>
                                                @else
                                                    {{ $itinerary->title }} ({{ ui_phrase((string) ($itinerary->status ?? 'pending')) }})
                                                @endif
                                            </p>
                                        @endforeach
                                    </div>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        {{ ui_phrase('No Itinerary Yet') }}
                                    </span>
                                @endif
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('inquiries.show', $inquiry) }}" class="btn-outline-sm" title="{{ ui_phrase('Detail') }}" aria-label="{{ ui_phrase('Detail') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('Detail') }}</span></a>
                                @can('update', $inquiry)
                                    @if (!in_array(($inquiry->quotation->status ?? ''), ['approved', \App\Models\Quotation::FINAL_STATUS], true) && !$inquiry->isFinal())
                                        <a href="{{ route('inquiries.edit', $inquiry) }}" class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                    @endif
                                @endcan
                                <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}"
                                    class="btn-outline-sm">
                                    {{ ui_phrase('Create Itinerary') }}
                                </a>
</div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Inquiries')]) }}
                        </div>
                    @endforelse
                </div>
                <div>{{ $inquiries->links() }}</div>
            </div>
        </div>
    </div>
@endsection
