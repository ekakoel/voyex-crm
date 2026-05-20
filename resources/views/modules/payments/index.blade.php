@extends('layouts.master')

@section('page_title', ui_phrase('Payments'))
@section('page_subtitle', ui_phrase('Track payment records and confirmation state.'))

@section('page_actions')
    <a href="{{ route('payments.create') }}" class="btn-primary">{{ ui_phrase('Record Payment') }}</a>
@endsection

@section('content')
    <div class="space-y-6 module-page module-page--payments">
        <div class="app-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="app-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left">{{ ui_phrase('Payment Number') }}</th>
                            <th class="px-4 py-3 text-left">{{ ui_phrase('Invoice') }}</th>
                            <th class="px-4 py-3 text-left">{{ ui_phrase('Payment Date') }}</th>
                            <th class="px-4 py-3 text-left">{{ ui_phrase('Amount') }}</th>
                            <th class="px-4 py-3 text-left">{{ ui_phrase('Type') }}</th>
                            <th class="px-4 py-3 text-left">{{ ui_phrase('Status') }}</th>
                            <th class="px-4 py-3 text-right">{{ ui_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="px-4 py-3">{{ $payment->payment_number }}</td>
                                <td class="px-4 py-3">{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                                <td class="px-4 py-3">{{ optional($payment->payment_date)->format('Y-m-d') ?? '-' }}</td>
                                <td class="px-4 py-3"><x-money :amount="$payment->amount ?? 0" :currency="(string) ($payment->currency_code ?? 'IDR')" /></td>
                                <td class="px-4 py-3">{{ ui_phrase((string) ($payment->payment_type ?? 'full_payment')) }}</td>
                                <td class="px-4 py-3"><x-status-badge :status="(string) ($payment->status ?? 'pending')" size="xs" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn-secondary-sm">{{ ui_phrase('Detail') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Payments')]) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div>{{ $payments->links() }}</div>
    </div>
@endsection

