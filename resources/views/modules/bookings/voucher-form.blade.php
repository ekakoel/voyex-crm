@extends('layouts.master')

@section('page_title', ui_phrase('Booking Item Voucher'))
@section('page_subtitle', ui_phrase('Create or update voucher for selected booking item.'))
@section('page_actions')
    <a href="{{ route('bookings.show', $bookingItem->booking_id) }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    @php
        $voucherStatus = (string) (optional($voucher)->status ?? $autoStatus ?? 'draft');
    @endphp
    <div class="space-y-6 module-page module-page--bookings">
        <x-ui.page-header :title="ui_phrase('Booking Item Voucher')" :subtitle="ui_phrase('Create or update voucher for selected booking item.')">
            <x-slot:breadcrumb>
                <a href="{{ route('bookings.index') }}" class="hover:underline">{{ ui_phrase('Bookings') }}</a>
                <span>/</span>
                <a href="{{ route('bookings.show', $bookingItem->booking_id) }}" class="hover:underline">{{ $bookingItem->booking?->booking_number ?? '-' }}</a>
                <span>/</span>
                <span>{{ ui_phrase('Voucher') }}</span>
            </x-slot:breadcrumb>
        </x-ui.page-header>

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <x-ui.section-card :title="ui_phrase('Voucher Form')" :description="ui_phrase('Voucher data is generated from booking item and can be adjusted before sending to vendor/provider.')">
                    <form method="POST" action="{{ route('booking-items.voucher.upsert', $bookingItem) }}">
                        @csrf
                        <div class="space-y-5 module-form">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/20 dark:text-slate-200">
                                <div><b>{{ ui_phrase('Booking Number') }}:</b> {{ $bookingItem->booking?->booking_number ?? '-' }}</div>
                                <div><b>{{ ui_phrase('Order Number') }}:</b> {{ $bookingItem->booking?->quotation?->order_number ?? '-' }}</div>
                                <div><b>{{ ui_phrase('Description') }}:</b> {{ $bookingItem->description }}</div>
                                <div><b>{{ ui_phrase('Qty') }}:</b> {{ (int) ($bookingItem->qty ?? 0) }}</div>
                                <div><b>{{ ui_phrase('Customer:') }}</b> {{ $bookingItem->booking?->quotation?->inquiry?->customer?->name ?? '-' }}</div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Status') }}</label>
                                    <div class="mt-2 flex items-center gap-2">
                                        <x-ui.status-badge :status="$voucherStatus" />
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Automatic') }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Tour / Name') }}</label>
                                    <input name="tour_name" type="text" class="mt-1 app-input" value="{{ old('tour_name', optional($voucher)->tour_name ?? ($prefill['tour_name'] ?? '')) }}" placeholder="{{ ui_phrase('Order Number - Customer Name') }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Service Date') }}</label>
                                    <input name="service_date" type="date" class="mt-1 app-input" value="{{ old('service_date', optional(optional($voucher)->service_date)->format('Y-m-d') ?: optional($prefill['service_date'] ?? null)->format('Y-m-d') ?: optional($bookingItem->booking?->travel_date)->format('Y-m-d')) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Service Time') }}</label>
                                    <input name="service_time" type="text" class="mt-1 app-input" placeholder="{{ ui_phrase('HH:mm') }}" value="{{ old('service_time', optional($voucher)->service_time ?? ($prefill['service_time'] ?? '')) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Vendor Contact Name') }}</label>
                                    <input name="vendor_contact_name" type="text" class="mt-1 app-input" value="{{ old('vendor_contact_name', optional($voucher)->vendor_contact_name ?? ($prefill['vendor_contact_name'] ?? '')) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Vendor Contact Phone') }}</label>
                                    <input name="vendor_contact_phone" type="text" class="mt-1 app-input" value="{{ old('vendor_contact_phone', optional($voucher)->vendor_contact_phone ?? ($prefill['vendor_contact_phone'] ?? '')) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Vendor Contact Email') }}</label>
                                    <input name="vendor_contact_email" type="email" class="mt-1 app-input" value="{{ old('vendor_contact_email', optional($voucher)->vendor_contact_email ?? ($prefill['vendor_contact_email'] ?? '')) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Confirmation Code') }}</label>
                                    <input name="confirmation_code" type="text" class="mt-1 app-input" value="{{ old('confirmation_code', optional($voucher)->confirmation_code ?? ($prefill['confirmation_code'] ?? '')) }}">
                                </div>
                            </div>

                            <x-ui.action-panel :title="ui_phrase('Voucher Actions')" :description="ui_phrase('Save voucher data, then download PDF if voucher already generated.')">
                                <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
                                @if ($voucher)
                                    <a href="{{ route('booking-items.voucher.pdf', $bookingItem) }}" class="btn-secondary">{{ ui_phrase('Download PDF') }}</a>
                                @endif
                            </x-ui.action-panel>
                        </div>
                    </form>
                </x-ui.section-card>
            </div>
        </div>
    </div>
@endsection
