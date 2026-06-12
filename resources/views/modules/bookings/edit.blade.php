@extends('layouts.master')

@section('page_title', ui_phrase('edit page title'))
@section('page_subtitle', ui_phrase('edit page subtitle'))
@section('page_actions')
    <a href="{{ route('bookings.show', $booking) }}" class="btn-secondary">{{ ui_phrase('View Detail') }}</a>
    <a href="{{ route('bookings.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection

@section('content')
    <div
        class="space-y-6 module-page module-page--bookings"
        data-no-booking-log-text="{{ ui_phrase('No booking log available.') }}"
        x-data="{
            voucher: {
                number: '-',
                item: '-',
                bookingSummary: '-',
                tourName: '-',
                qty: '-',
                serviceDate: '-',
                vendorName: '-',
                toLocation: '-',
                toContact: '-',
                confirmation: '-',
                contactedPerson: '-',
                contactChannel: '-',
                contactDetail: '-',
            },
            openVoucherModal(el) {
                const text = (value) => String(value ?? '').trim() || '-';
                const noBookingLogText = text(this.$el.dataset.noBookingLogText);
                this.voucher.number = text(el.dataset.voucherNumber);
                this.voucher.item = text(el.dataset.voucherItem);
                const bookingSummary = text(el.dataset.bookingSummary);
                const bookingAt = text(el.dataset.bookingAt);
                const bookingChannel = text(el.dataset.bookingChannel);
                const bookingContacted = text(el.dataset.bookingContacted);
                this.voucher.bookingSummary = bookingSummary !== '-' ? bookingSummary : `${bookingAt} | ${bookingChannel} | ${bookingContacted}`;
                this.voucher.tourName = text(el.dataset.voucherTourName);
                this.voucher.qty = text(el.dataset.voucherQty);
                this.voucher.serviceDate = text(el.dataset.voucherServiceDate);
                this.voucher.vendorName = text(el.dataset.voucherVendorName);
                this.voucher.toLocation = text(el.dataset.voucherToLocation);
                this.voucher.toContact = text(el.dataset.voucherToContact);
                this.voucher.confirmation = text(el.dataset.voucherConfirmation);
                this.voucher.contactedPerson = bookingContacted;
                this.voucher.contactChannel = bookingChannel;
                this.voucher.contactDetail = text(el.dataset.bookingContactDetail);
                const bookingSummaryEl = document.getElementById('voucher-booking-summary-edit');
                if (bookingSummaryEl) {
                    bookingSummaryEl.textContent = this.voucher.bookingSummary !== '-' ? this.voucher.bookingSummary : noBookingLogText;
                }
                this.$dispatch('open-modal', 'booking-voucher-modal-edit');
            }
        }"
    >

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                <x-ui.section-card :title="ui_phrase('Edit Booking')" :description="ui_phrase('Update booking operational details.')">
                    <form method="POST" action="{{ route('bookings.update', $booking) }}">
                        @csrf
                        @method('PUT')
                        @include('modules.bookings._form', [
                            'booking' => $booking,
                            'buttonLabel' => ui_phrase('Update Booking'),
                            'itemsReadonly' => true,
                            'hideItemsSection' => true,
                            'quotationReadonly' => (bool) ($hasOperationalLock ?? false),
                        ])
                    </form>
                </x-ui.section-card>

                @include('modules.bookings.partials._services-workspace', [
                    'booking' => $booking,
                    'sourceUpdatedMap' => $sourceUpdatedMap ?? [],
                    'fallbackPolicyRulesMap' => $fallbackPolicyRulesMap ?? [],
                ])
            </div>
            <aside  class="module-grid-side">
                @include('partials._audit-info', ['record' => $booking])
            </aside>
        </div>
    </div>

    <x-modal name="booking-voucher-modal-edit" focusable maxWidth="2xl">
        <div class="p-5">
            <div class="border border-gray-900 text-gray-900 dark:border-gray-200 dark:text-gray-100">
                <div class="border-b border-gray-900 px-3 py-2 text-xs dark:border-gray-200">
                    <p id="voucher-booking-summary-edit">{{ ui_phrase('No booking log available.') }}</p>
                </div>
                <div class="grid grid-cols-12 border-b border-gray-900 dark:border-gray-200">
                    <div class="col-span-5 border-r border-gray-900 p-3 dark:border-gray-200">
                        <p class="text-sm font-bold">{{ $companyName }}</p>
                        <p class="text-xs">{{ $companyAddress !== '' ? $companyAddress : '-' }}</p>
                        <p class="text-xs">{{ ui_phrase('E-mail') }} : {{ $companyEmail !== '' ? $companyEmail : '-' }}</p>
                    </div>
                    <div class="col-span-7 p-3">
                        <p class="text-sm font-bold leading-none text-center">{{ ui_phrase('Voucher') }}</p>
                        <div class="mt-2 grid grid-cols-[70px,1fr] text-sm">
                            <p class="font-semibold">{{ ui_phrase('TO') }} :</p>
                            <div>
                                <p class="font-semibold" x-text="voucher.vendorName">-</p>
                                <p class="mt-1 text-xs" x-text="voucher.toLocation">-</p>
                                <p class="text-xs" x-text="voucher.toContact">-</p>
                            </div>
                            <p class="mt-1 font-semibold">{{ ui_phrase('No') }} :</p>
                            <p class="mt-1 font-bold leading-tight" x-text="voucher.number">-</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-12 border-b border-gray-900 text-sm dark:border-gray-200">
                    <div class="col-span-5 border-r border-gray-900 p-2 dark:border-gray-200">
                        <p class="font-semibold">{{ ui_phrase('Tour / Name') }} :</p>
                        <p x-text="voucher.tourName">-</p>
                    </div>
                    <div class="col-span-4 border-r border-gray-900 p-2 dark:border-gray-200">
                        <p class="font-semibold">{{ ui_phrase('Total Pax') }} :</p>
                        <p x-text="voucher.qty">-</p>
                    </div>
                    <div class="col-span-3 p-2">
                        <p class="font-semibold">{{ ui_phrase('Issuing Date') }} :</p>
                        <p>{{ now()->format('d-M-y') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-12 border-b border-gray-900 text-sm dark:border-gray-200">
                    <div class="col-span-9 border-r border-gray-900 p-2 dark:border-gray-200">
                        <p class="font-semibold">{{ ui_phrase('Please provide bearer of this voucher with services as below:') }}</p>
                        <p class="mt-2">{{ ui_phrase('Date') }} <span x-text="voucher.serviceDate">-</span></p>
                        <p x-text="voucher.item">-</p>
                        <p>{{ ui_phrase('Confirmation No') }} : <span x-text="voucher.confirmation">-</span></p>
                        <p class="mt-4">{{ ui_phrase("All other services not specified above are not for client's account") }}</p>
                    </div>
                    <div class="col-span-3 p-2">
                        <p class="font-semibold">{{ ui_phrase('Official Stamp') }}</p>
                        <p class="mt-2 font-semibold">{{ ui_phrase('Authorized Signature') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-12 text-sm">
                    <div class="col-span-5 border-r border-gray-900 p-2 dark:border-gray-200">
                        <p class="font-semibold">{{ ui_phrase('Final service to be rendered as') }} :</p>
                        <p class="mt-4">{{ ui_phrase('Confirmed By') }} : <span x-text="voucher.contactedPerson">-</span></p>
                        <p class="mt-1">{{ ui_phrase('Contact Channel') }} : <span x-text="voucher.contactChannel">-</span></p>
                        <p class="mt-1">{{ ui_phrase('Contact Detail') }} : <span x-text="voucher.contactDetail">-</span></p>
                    </div>
                    <div class="col-span-4 border-r border-gray-900 p-2 dark:border-gray-200"><p class="font-semibold">{{ ui_phrase('Tour Guide') }}:</p></div>
                    <div class="col-span-3 p-2"><p class="font-semibold">{{ ui_phrase('Remarks') }}</p></div>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-700 dark:text-gray-300">{{ ui_phrase('This voucher not valid unless officially signed & stamp. Please attach original voucher for billing.') }}</p>
            <div class="mt-3 flex justify-end">
                <button type="button" class="btn-ghost px-2 py-1 text-xs" x-on:click.prevent="$dispatch('close-modal', 'booking-voucher-modal-edit')">{{ ui_phrase('Close') }}</button>
            </div>
        </div>
    </x-modal>
@endsection







