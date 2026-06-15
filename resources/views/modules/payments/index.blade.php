@extends('layouts.master')

@section('page_title', ui_phrase('Payments'))
@section('page_subtitle', ui_phrase('Track payment records and confirmation state.'))
@section('page_actions')
    <a href="{{ route('payments.create') }}" class="btn-primary">{{ ui_phrase('Record Payment') }}</a>
@endsection

@section('content')
    <div class="space-y-5 module-page module-page--payments">

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.metric-card :title="ui_phrase('Total Payment')" :value="(string) ($summaries['total'] ?? 0)" />
            <x-ui.metric-card :title="ui_phrase('Pending')" :value="(string) ($summaries['pending'] ?? 0)" />
            <x-ui.metric-card :title="ui_phrase('Confirmed')" :value="(string) ($summaries['confirmed'] ?? 0)" />
            <x-ui.metric-card :title="ui_phrase('Rejected')" :value="(string) ($summaries['rejected'] ?? 0)" />
        </div>

        <x-ui.filter-bar :title="ui_phrase('Filters')" :description="ui_phrase('Search payments by number, invoice reference, type, and date range.')" formClass="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <form method="GET" action="{{ route('payments.index') }}" class="contents">
                <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search payment number, reference, invoice...') }}" class="app-input lg:col-span-2">
                <select name="payment_type" class="app-input">
                    <option value="">{{ ui_phrase('Payment Type') }}</option>
                    @foreach (\App\Models\Payment::TYPE_OPTIONS as $type)
                        <option value="{{ $type }}" @selected(request('payment_type') === $type)>{{ ui_phrase($type) }}</option>
                    @endforeach
                </select>
                <select name="status" class="app-input">
                    <option value="">{{ ui_phrase('Status') }}</option>
                    @foreach (\App\Models\Payment::STATUS_OPTIONS as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ui_phrase($status) }}</option>
                    @endforeach
                </select>
                <input type="date" name="payment_from" value="{{ request('payment_from') }}" class="app-input">
                <input type="date" name="payment_to" value="{{ request('payment_to') }}" class="app-input">
                <select name="per_page" class="app-input">
                    @foreach ($perPageOptions as $size)
                        <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-primary">{{ ui_phrase('Apply') }}</button>
                    <a href="{{ route('payments.index') }}" class="btn-ghost">{{ ui_phrase('Reset') }}</a>
                </div>
            </form>
            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-gray-500">{{ ui_phrase('Quick Status') }}:</span>
                @foreach ($paymentStatusTabs as $tab)
                    <a href="{{ $tab['url'] }}" class="{{ $paymentStatusActive === $tab['key'] ? 'btn-primary-sm' : 'btn-ghost-sm' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
        </x-ui.filter-bar>

        <x-ui.data-table>
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Payment Number') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Invoice') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Customer') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Payment Date') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Amount') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Type') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">{{ ui_phrase('Status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">{{ ui_phrase('Actions') }}</th>
                </tr>
            </x-slot:head>
            @forelse($paymentRows as $row)
                @php($payment = $row['payment'])
                <tr>
                    <td class="px-4 py-3">{{ $row['payment_number'] }}</td>
                    <td class="px-4 py-3">{{ $row['invoice_number'] }}</td>
                    <td class="px-4 py-3">{{ $row['customer_name'] }}</td>
                    <td class="px-4 py-3"><x-ui.date-display :date="$payment->payment_date" format="Y-m-d" /></td>
                    <td class="px-4 py-3"><x-ui.money :amount="$row['amount']" :currency="$row['currency_code']" /></td>
                    <td class="px-4 py-3">{{ ui_phrase($row['payment_type']) }}</td>
                    <td class="px-4 py-3"><x-ui.status-badge :status="$row['status']" size="xs" /></td>
                    <td class="px-4 py-3 text-right">
                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                            <a href="{{ $row['show_url'] }}" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                <span>{{ ui_phrase('Detail') }}</span>
                            </a>
                        </x-ui.table-action-dropdown>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-6">
                        <x-ui.empty-state :title="ui_phrase('No payments found.')" :description="ui_phrase('Record payment from invoice detail when customer makes payment.')" />
                    </td>
                </tr>
            @endforelse
        </x-ui.data-table>

        <div>{{ $payments->links() }}</div>
    </div>
@endsection
