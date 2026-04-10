@extends('layouts.master')
@section('page_title', 'Quotations')
@section('page_subtitle', 'Manage quotation data.')
@section('page_actions')
    <a href="{{ route('quotations.export', request()->only(['q', 'status', 'per_page', 'needs_my_approval'])) }}"
        class="btn-secondary">Export CSV</a>
    <a href="{{ route('quotations.create') }}" class="btn-primary">Add Quotation</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--quotations" data-service-filter-page data-page-spinner="off">
        @if (request()->boolean('needs_my_approval'))
            <div class="flex items-center">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                    <i class="fa-solid fa-bell"></i>
                    Needs My Approval
                </span>
            </div>
        @endif
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" action="{{ route('quotations.index') }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1"
                        data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="Search number / customer"
                            class="sm:col-span-2 app-input" data-service-filter-input>
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">Status</option>
                            @foreach (\App\Models\Quotation::STATUS_OPTIONS as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('quotations.index') }}" class="btn-ghost" data-service-filter-reset>Reset</a>
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
                @if (request()->boolean('needs_my_approval'))
                    <div
                        class="rounded-lg mb-6 border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                        Showing: Quotations requiring your approval.
                    </div>
                @endif
                <div class="md:hidden space-y-3">
                    @forelse ($quotations as $quotation)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $quotation->quotation_number }}</p>
                                    @if ((bool) ($quotation->needs_my_approval_badge ?? false))
                                        <span
                                            class="mt-1 inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                            Need Approval
                                        </span>
                                    @endif
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
                                    <x-local-time :value="$quotation->created_at" />
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('quotations.show', $quotation) }}" class="btn-ghost-sm" title="View"
                                    aria-label="View"><i class="fa-solid fa-eye"></i><span class="sr-only">View</span></a>
                                @can('update', $quotation)
                                    @if (($quotation->status ?? '') !== 'final')
                                        <a href="{{ route('quotations.edit', $quotation) }}" class="btn-secondary-sm"
                                            title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span
                                                class="sr-only">Edit</span></a>
                                    @endif
                                @endcan
                                @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                    <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener"
                                        class="btn-outline-sm">PDF</a>
                                @endif
                                @can('delete', $quotation)
                                    @if (!in_array($quotation->status ?? '', ['approved', 'final'], true))
                                        <form action="{{ route('quotations.toggle-status', $quotation->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                onclick="return confirm('{{ $quotation->trashed() ? 'Activate this quotation?' : 'Deactivate this quotation?' }}')"
                                                class="{{ $quotation->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">
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
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Number</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Validity</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Amount</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Created By</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($quotations as $index=>$quotation)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ ++$index }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="flex flex-col items-start">
                                                <span>{{ $quotation->quotation_number }}</span>
                                                @if ((bool) ($quotation->needs_my_approval_badge ?? false))
                                                    <span
                                                        class="mt-1 inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                                        Need Approval
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-status-badge :status="$quotation->status" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-money
                                                :amount="$quotation->final_amount ?? 0" currency="IDR" /></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $quotation->creator?->name ?? '-' }}<br>
                                            <i><x-local-time :value="$quotation->created_at" /></i>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('quotations.show', $quotation) }}" class="btn-outline-sm"
                                                    title="View" aria-label="View"><i class="fa-solid fa-eye"></i><span
                                                        class="sr-only">View</span></a>
                                                @can('update', $quotation)
                                                    @if (($quotation->status ?? '') !== 'final')
                                                        <a href="{{ route('quotations.edit', $quotation) }}"
                                                            class="btn-secondary-sm" title="Edit" aria-label="Edit"><i
                                                                class="fa-solid fa-pen"></i><span
                                                                class="sr-only">Edit</span></a>
                                                    @endif
                                                @endcan
                                                @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                                    <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank"
                                                        rel="noopener" class="btn-outline-sm">PDF</a>
                                                @endif
                                                @can('delete', $quotation)
                                                    @if (!in_array($quotation->status ?? '', ['approved', 'final'], true))
                                                        <form action="{{ route('quotations.toggle-status', $quotation->id) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit"
                                                                onclick="return confirm('{{ $quotation->trashed() ? 'Activate this quotation?' : 'Deactivate this quotation?' }}')"
                                                                class="{{ $quotation->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $quotation->trashed() ? 'Activate' : 'Deactivate' }}
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No
                                            quotations available.</td>
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
