@extends('layouts.master')
@section('page_title', 'Invoices')
@section('page_subtitle', 'Manage invoice data.')
@section('content')
    <div class="space-y-6 module-page module-page--invoices">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search invoice / booking / customer" class="app-input sm:col-span-2">
            <select name="status" class="app-input">
                <option value="">Status</option>
                @foreach (\App\Models\Invoice::STATUS_OPTIONS as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <input name="invoice_from" type="date" value="{{ request('invoice_from') }}" class="app-input">
            <input name="invoice_to" type="date" value="{{ request('invoice_to') }}" class="app-input">
            <select name="per_page" class="app-input">
                @foreach ([10,25,50,100] as $size)
                    <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                <button class="btn-primary">Filter</button>
                <a href="{{ route('invoices.index') }}" class="btn-ghost">Reset</a>
            </div>
        </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Invoice</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Booking</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Customer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Invoice Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Due Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Amount</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($invoices as $index => $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->booking->booking_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200"><x-status-badge :status="$invoice->status" size="xs" /></td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary-sm" title="Detail" aria-label="Detail"><i class="fa-solid fa-eye"></i><span class="sr-only">Detail</span></a>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No invoices available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($invoices as $invoice)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $invoice->invoice_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $invoice->booking?->booking_number ?? '-' }} • {{ $invoice->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</p>
                        </div>
                        <x-status-badge :status="$invoice->status" size="xs" />
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>Invoice Date</div>
                        <div>{{ $invoice->invoice_date?->format('Y-m-d') ?? '-' }}</div>
                        <div>Due Date</div>
                        <div>{{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</div>
                        <div>Amount</div>
                        <div><x-money :amount="$invoice->total_amount ?? 0" currency="IDR" /></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary-sm" title="Detail" aria-label="Detail"><i class="fa-solid fa-eye"></i><span class="sr-only">Detail</span></a>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No invoices available.</div>
            @endforelse
        </div>
        <div>{{ $invoices->links() }}</div>
            </div>
        </div>
</div>
@endsection



