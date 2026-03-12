@extends('layouts.master')

@section('content')
    <div class="space-y-6 module-page module-page--quotations">
        @section('page_actions')<a href="{{ route('quotations.export', request()->only(['q','status','per_page'])) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    Export CSV
                </a>
                <a href="{{ route('quotations.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Add Quotation
                </a>@endsection

        <form method="GET" class="grid grid-cols-1 gap-3 app-card p-4 md:grid-cols-4">
            <input name="q" value="{{ request('q') }}" placeholder="Search number / customer" class="w-full md:col-span-2 app-input">
            <select name="status" class="w-full app-input">
                <option value="">Status</option>
                @foreach (['draft','pending','sent','approved','rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <select name="per_page" class="w-full app-input">
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page</option>
                @endforeach
            </select>
            <div class="flex flex-col gap-2 md:col-span-4 sm:flex-row">
                <button class="w-full sm:w-auto rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900">Filter</button>
                <a href="{{ route('quotations.index') }}" class="w-full sm:w-auto rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 text-center">Reset</a>
            </div>
        </form>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
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
                        <a href="{{ route('quotations.show', $quotation) }}" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">View</a>
                        @if ((int) ($quotation->created_by ?? 0) === (int) auth()->id() && ($quotation->status ?? '') !== 'approved')
                            <a href="{{ route('quotations.edit', $quotation) }}" class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Edit</a>
                        @endif
                        <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener" class="rounded-lg border border-emerald-300 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">PDF</a>
                        @if ((int) ($quotation->created_by ?? 0) === (int) auth()->id() && ($quotation->status ?? '') !== 'approved')
                            <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this quotation?')" class="rounded-lg border border-rose-300 px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/20">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No quotations available.
                </div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto app-card">
            <table class="app-table divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Number</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Validity</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Created By</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
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
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('quotations.show', $quotation) }}" class="mr-3 font-medium text-gray-600 hover:text-gray-700 dark:text-gray-300">View</a>
                                @if ((int) ($quotation->created_by ?? 0) === (int) auth()->id() && ($quotation->status ?? '') !== 'approved')
                                    <a href="{{ route('quotations.edit', $quotation) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                @endif
                                <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener" class="mr-3 font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">PDF</a>
                                @if ((int) ($quotation->created_by ?? 0) === (int) auth()->id() && ($quotation->status ?? '') !== 'approved')
                                    <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Are you sure you want to delete this quotation?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">
                                            Delete
                                        </button>
                                    </form>
                                @endif
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

        <div>{{ $quotations->links() }}</div>
    </div>
@endsection


