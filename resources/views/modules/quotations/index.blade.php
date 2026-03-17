@extends('layouts.master')
@section('page_title', 'Quotations')
@section('page_subtitle', 'Manage quotation data.')
@section('page_actions')
    <a href="{{ route('quotations.export', request()->only(['q','status','per_page'])) }}" class="btn-secondary">Export CSV</a>
    <a href="{{ route('quotations.create') }}" class="btn-primary">Add Quotation</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search number / customer" class="sm:col-span-2 app-input">
            <select name="status" class="app-input">
                <option value="">Status</option>
                @foreach (\App\Models\Quotation::STATUS_OPTIONS as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select name="per_page" class="app-input">
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                <button class="btn-primary">Filter</button>
                <a href="{{ route('quotations.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif
        <div class="md:hidden space-y-3">
            @forelse ($quotations as $quotation)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $quotation->quotation_number }}</p>
                        </div>
                        <x-status-badge :status="$quotation->status" size="xs" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>Validity</div>
                        <div>{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</div>
                        <div>Amount</div>
                        <div><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></div>
                        <div>Created By</div>
                        <div>
                            {{ $quotation->creator?->name ?? '-' }}<br>
                            {{ $quotation->created_at?->format('Y-m-d H:i') ?? '-' }}
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('quotations.show', $quotation) }}"  class="btn-ghost-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                        @can('update', $quotation)
                            @if (! in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                <a href="{{ route('quotations.edit', $quotation) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                            @endif
                        @endcan
                        <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener"  class="btn-outline-sm">PDF</a>
                        @can('delete', $quotation)
                            @if (! in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                <form action="{{ route('quotations.toggle-status', $quotation->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $quotation->trashed() ? 'Activate this quotation?' : 'Deactivate this quotation?' }}')"   class="{{ $quotation->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">
                                        {{ $quotation->trashed() ? 'Activate' : 'Deactivate' }}
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No quotations available.
                </div>
            @endforelse
        </div>
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Number</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Validity</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Created By</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($quotations as $index=>$quotation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $quotation->quotation_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <x-status-badge :status="$quotation->status" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-money :amount="$quotation->final_amount ?? 0" currency="IDR" /></td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ $quotation->creator?->name ?? '-' }}<br>
                                <i>{{ $quotation->created_at?->format('Y-m-d H:i') ?? '-' }}</i>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"></td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('quotations.show', $quotation) }}" class="btn-outline-sm" title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                @can('update', $quotation)
                                    @if (! in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                        <a href="{{ route('quotations.edit', $quotation) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                    @endif
                                @endcan
                                <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener"  class="btn-outline-sm">PDF</a>
                                @can('delete', $quotation)
                                    @if (! in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                        <form action="{{ route('quotations.toggle-status', $quotation->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('{{ $quotation->trashed() ? 'Activate this quotation?' : 'Deactivate this quotation?' }}')"   class="{{ $quotation->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $quotation->trashed() ? 'Activate' : 'Deactivate' }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No quotations available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div>{{ $quotations->links() }}</div>
            </div>
        </div>
</div>
@endsection




