@extends('layouts.master')
@section('page_title', 'Inquiries')
@section('page_subtitle', 'Manage inquiry data.')
@section('page_actions')
    <a href="{{ route('inquiries.create') }}" class="btn-primary">
        Add Inquiry
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--inquiries">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <input name="q" value="{{ request('q') }}" placeholder="Search number / customer"
                            class="app-input sm:col-span-2">
                        <select name="status" class="app-input">
                            <option value="">Status</option>
                            @foreach (\App\Models\Inquiry::STATUS_OPTIONS as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="priority" class="app-input">
                            <option value="">Priority</option>
                            @foreach (['low', 'normal', 'high'] as $priority)
                                <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ $priority }}
                                </option>
                            @endforeach
                        </select>
                        <select name="assigned_to" class="app-input">
                            <option value="">Assigned</option>
                            @foreach ($assignees as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <button class="btn-primary">Filter</button>
                            <a href="{{ route('inquiries.index') }}" class="btn-ghost">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
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
                                        Customer</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Priority</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Assigned</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Deadline</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Itinerary</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        Actions</th>
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
                                            {{ $inquiry->priority }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $inquiry->assignedUser->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            @if (($inquiry->itineraries_count ?? 0) > 0)
                                                <span
                                                    class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                    Available ({{ $inquiry->itineraries_count }})
                                                </span>
                                                <div class="mt-1 space-y-1">
                                                    @foreach ($inquiry->itineraries->take(2) as $itinerary)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            @if (Route::has('itineraries.show') && auth()->user()->can('module.itineraries.access'))
                                                                <a href="{{ route('itineraries.show', $itinerary) }}"
                                                                    class="text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                                                    {{ $itinerary->title }}
                                                                </a>
                                                            @else
                                                                {{ $itinerary->title }}
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
                                                    class="btn-secondary-sm" title="Detail" aria-label="Detail"><i class="fa-solid fa-eye"></i><span class="sr-only">Detail</span></a>
                                                @can('update', $inquiry)
                                                    @if (!($inquiry->quotation && ($inquiry->quotation->status ?? '') === 'approved') && !$inquiry->isFinal())
                                                        <a href="{{ route('inquiries.edit', $inquiry) }}"
                                                            class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                                    @endif
                                                @endcan
                                                @if (($inquiry->itineraries_count ?? 0) === 0 && !$inquiry->isFinal())
                                                    <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}"
                                                        class="btn-outline-sm">Create Itinerary</a>
                                                @endif
                                                <form action="{{ route('inquiries.toggle-status', $inquiry->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" onclick="return confirm('{{ $inquiry->trashed() ? 'Activate this inquiry?' : 'Deactivate this inquiry?' }}')" class="{{ $inquiry->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $inquiry->trashed() ? 'Activate' : 'Deactivate' }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No
                                            inquiries available.</td>
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
                                <div>Priority</div>
                                <div>{{ $inquiry->priority }}</div>
                                <div>Assigned</div>
                                <div>{{ $inquiry->assignedUser->name ?? '-' }}</div>
                                <div>Deadline</div>
                                <div>{{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</div>
                            </div>
                            <div class="mt-3">
                                @if (($inquiry->itineraries_count ?? 0) > 0)
                                    <span
                                        class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Itinerary Available ({{ $inquiry->itineraries_count }})
                                    </span>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($inquiry->itineraries->take(2) as $itinerary)
                                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                                -
                                                @if (Route::has('itineraries.show') && auth()->user()->can('module.itineraries.access'))
                                                    <a href="{{ route('itineraries.show', $itinerary) }}"
                                                        class="text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                                        {{ $itinerary->title }}
                                                    </a>
                                                @else
                                                    {{ $itinerary->title }}
                                                @endif
                                            </p>
                                        @endforeach
                                    </div>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                        No Itinerary Yet
                                    </span>
                                @endif
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('inquiries.show', $inquiry) }}" class="btn-outline-sm" title="Detail" aria-label="Detail"><i class="fa-solid fa-eye"></i><span class="sr-only">Detail</span></a>
                                @can('update', $inquiry)
                                    @if (!($inquiry->quotation && ($inquiry->quotation->status ?? '') === 'approved') && !$inquiry->isFinal())
                                        <a href="{{ route('inquiries.edit', $inquiry) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                    @endif
                                @endcan
                                @if (($inquiry->itineraries_count ?? 0) === 0 && !$inquiry->isFinal())
                                    <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}"
                                        class="btn-outline-sm">
                                        Create Itinerary
                                    </a>
                                @endif
                                <form action="{{ route('inquiries.toggle-status', $inquiry->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $inquiry->trashed() ? 'Activate this inquiry?' : 'Deactivate this inquiry?' }}')" class="{{ $inquiry->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $inquiry->trashed() ? 'Activate' : 'Deactivate' }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No inquiries available.
                        </div>
                    @endforelse
                </div>
                <div>{{ $inquiries->links() }}</div>
            </div>
        </div>
    </div>
@endsection

