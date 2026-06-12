@extends('layouts.master')

@section('page_title', ui_phrase('Booking Detail'))
@section('page_subtitle', ui_phrase('Review complete booking and quotation information.'))

@section('content')
    <div
        class="space-y-6 module-page module-page--bookings"
        data-no-booking-log-text="{{ ui_phrase('No booking log available.') }}"
        x-data="{
            voucher: {
                number: '-',
                status: '-',
                item: '-',
                bookingSummary: '-',
                customer: '-',
                tourName: '-',
                qty: '-',
                serviceDate: '-',
                serviceTime: '-',
                pickup: '-',
                vendorName: '-',
                vendorPhone: '-',
                vendorEmail: '-',
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
                this.voucher.status = text(el.dataset.voucherStatus);
                this.voucher.item = text(el.dataset.voucherItem);
                const bookingSummary = text(el.dataset.bookingSummary);
                const bookingAt = text(el.dataset.bookingAt);
                const bookingChannel = text(el.dataset.bookingChannel);
                const bookingContacted = text(el.dataset.bookingContacted);
                const bookingContactDetail = text(el.dataset.bookingContactDetail);
                this.voucher.bookingSummary = bookingSummary !== '-'
                    ? bookingSummary
                    : `${bookingAt} | ${bookingChannel} | ${bookingContacted}`;
                this.voucher.contactedPerson = bookingContacted;
                this.voucher.contactChannel = bookingChannel;
                this.voucher.contactDetail = bookingContactDetail;
                this.voucher.customer = text(el.dataset.voucherCustomer);
                this.voucher.tourName = text(el.dataset.voucherTourName);
                this.voucher.qty = text(el.dataset.voucherQty);
                this.voucher.serviceDate = text(el.dataset.voucherServiceDate);
                this.voucher.serviceTime = text(el.dataset.voucherServiceTime);
                this.voucher.pickup = text(el.dataset.voucherPickup);
                this.voucher.vendorName = text(el.dataset.voucherVendorName);
                this.voucher.vendorPhone = text(el.dataset.voucherVendorPhone);
                this.voucher.vendorEmail = text(el.dataset.voucherVendorEmail);
                this.voucher.toLocation = text(el.dataset.voucherToLocation);
                this.voucher.toContact = text(el.dataset.voucherToContact);
                this.voucher.confirmation = text(el.dataset.voucherConfirmation);
                const bookingSummaryEl = document.getElementById('voucher-booking-summary');
                if (bookingSummaryEl) {
                    bookingSummaryEl.textContent = this.voucher.bookingSummary !== '-'
                        ? this.voucher.bookingSummary
                        : noBookingLogText;
                }
                window.dispatchEvent(new CustomEvent('booking-voucher-preview-updated', {
                    detail: { ...this.voucher }
                }));
                this.$dispatch('open-modal', 'booking-voucher-modal');
            }
        }"
    >
        @section('page_actions')
            @can('bookings.operation.spk.view')
                <a href="{{ route('bookings.spk', $booking) }}" target="_blank" rel="noopener" class="btn-secondary">{{ ui_phrase('View SPK') }}</a>
            @endcan
            @can('bookings.operation.spk.print')
                <a href="{{ route('bookings.spk.print', $booking) }}" target="_blank" rel="noopener" class="btn-secondary">{{ ui_phrase('Print SPK') }}</a>
            @endcan
            @can('bookings.operation.prepare')
                @if (in_array((string) ($booking->status ?? ''), ['confirmed', 'awaiting_dp', 'dp_received', 'awaiting_balance'], true))
                    <form method="POST" action="{{ route('bookings.operation.ready', $booking) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-secondary">{{ ui_phrase('Mark Ready to Operate') }}</button>
                    </form>
                @endif
            @endcan
            @can('bookings.operation.start')
                @if ((string) ($booking->status ?? '') === 'ready_to_operate')
                    <form method="POST" action="{{ route('bookings.operation.start', $booking) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-secondary">{{ ui_phrase('Start Operation') }}</button>
                    </form>
                @endif
            @endcan
            @can('bookings.operation.complete')
                @if ((string) ($booking->status ?? '') === 'in_operation')
                    <form method="POST" action="{{ route('bookings.operation.complete', $booking) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-secondary">{{ ui_phrase('Complete Service') }}</button>
                    </form>
                @endif
            @endcan
            @can('booking_settlements.view')
                <a href="{{ route('bookings.settlement.show', $booking) }}" class="btn-secondary">{{ ui_phrase('Settlement Review') }}</a>
            @endcan
            <form method="POST" action="{{ route('bookings.invoices.proforma', $booking) }}" class="inline">
                @csrf
                <button type="submit" class="btn-secondary">{{ ui_phrase('Generate Proforma') }}</button>
            </form>
            @if ((string) ($booking->status ?? '') === 'reconciliation')
                <form method="POST" action="{{ route('bookings.invoices.final', $booking) }}" class="inline">
                    @csrf
                    <button type="submit" class="btn-primary">{{ ui_phrase('Generate Final Invoice') }}</button>
                </form>
            @endif
            <a href="{{ route('bookings.reconciliation.show', $booking) }}" class="btn-secondary">{{ ui_phrase('Reconciliation') }}</a>
            @can('booking_settlements.close_booking')
                @if ((string) ($booking->settlement?->status ?? '') === 'settled' && ! $booking->isFinal())
                    <form method="POST" action="{{ route('bookings.settlement.close', $booking) }}" class="inline">
                        @csrf
                        <button type="submit" class="btn-danger">{{ ui_phrase('Close Booking') }}</button>
                    </form>
                @endif
            @endcan
            @can('update', $booking)
                @if (! $booking->isFinal())
                    <a href="{{ route('bookings.edit', $booking) }}"  class="btn-primary">
                        {{ ui_phrase('Edit') }}
                    </a>
                @endif
            @endcan
            <a href="{{ route('bookings.index') }}"  class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
        @endsection

        @php
            $bookingStepperSteps = [
                ['key' => 'created', 'label' => ui_phrase('Created')],
                ['key' => 'vendor_confirmation', 'label' => ui_phrase('Vendor Confirmation')],
                ['key' => 'ready_to_operate', 'label' => ui_phrase('Ready to Operate')],
                ['key' => 'in_operation', 'label' => ui_phrase('In Operation')],
                ['key' => 'service_completed', 'label' => ui_phrase('Service Completed')],
                ['key' => 'reconciliation', 'label' => ui_phrase('Reconciliation')],
                ['key' => 'invoiced', 'label' => ui_phrase('Invoiced')],
                ['key' => 'closed', 'label' => ui_phrase('Closed')],
            ];
            $bookingStatusCurrent = (string) ($booking->status ?? 'created');
            $bookingIsLocked = in_array($bookingStatusCurrent, ['reconciliation', 'closed', 'cancelled'], true);
        @endphp
        <x-ui.workflow-stepper
            :steps="$bookingStepperSteps"
            :current="$bookingStatusCurrent"
            :title="ui_phrase('Workflow Progress')"
        />
        @if ($bookingIsLocked)
            <x-ui.lock-alert :title="ui_phrase('Locked Booking')" :message="ui_phrase('This booking is locked for operational edits in current stage.')" type="warning" />
        @endif

        <div class="module-grid-8-4">
            <div class="module-grid-main">
                @can('bookings.operation.view')
                <div class="module-card p-6 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Detail Quotation') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Quotation Number') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation?->quotation_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Order Number') }}</p>
                            @php
                                $orderNumber = trim((string) ($booking->quotation?->order_number ?? ''));
                                $itineraryName = trim((string) ($booking->quotation?->itinerary?->title ?? ''));
                            @endphp
                            <p class="text-sm text-gray-800 dark:text-gray-100">
                                {{ $orderNumber !== '' ? $orderNumber : '-' }}@if($itineraryName !== '') | {{ $itineraryName }}@endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Travel Date') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100"><x-ui.date-display :date="$booking->travel_date" format="Y-m-d" /></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Pax (Adult/Child)') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ (int) ($booking->pax_adult ?? $booking->quotation?->pax_adult ?? 0) }} / {{ (int) ($booking->pax_child ?? $booking->quotation?->pax_child ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Destination') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ data_get($booking->itinerary_snapshot, 'destination_name') ?? $booking->quotation?->itinerary?->destination?->name ?? $booking->quotation?->itinerary?->destination ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Itinerary') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ data_get($booking->itinerary_snapshot, 'title') ?? $booking->quotation?->itinerary?->title ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                @php
                    $invoiceCount = $booking->invoices->count();
                    $confirmedPaymentCount = $booking->invoices->sum(fn ($invoice) => $invoice->payments->where('status', 'confirmed')->count());
                    $paidInvoiceCount = $booking->invoices->whereIn('status', ['paid', 'overpaid'])->count();
                    $paymentSatisfied = $paidInvoiceCount > 0 || $confirmedPaymentCount > 0;
                @endphp
                <div class="module-card p-6 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Operation Summary') }}</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Booking Status') }}</p>
                            <p class="mt-1"><x-ui.status-badge :status="(string) ($booking->status ?? 'pending_confirmation')" size="xs" /></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Payment Eligibility') }}</p>
                            @if ($paymentSatisfied)
                                <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ ui_phrase('Payment requirement satisfied for operation.') }}</p>
                            @else
                                <p class="text-sm text-rose-700 dark:text-rose-300">{{ ui_phrase('Payment requirement is not satisfied yet.') }}</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Invoices') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $invoiceCount }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Confirmed Payments') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ (int) $confirmedPaymentCount }}</p>
                        </div>
                    </div>
                    @can('bookings.operation.issue')
                        <form method="POST" action="{{ route('bookings.operation.issue', $booking) }}" class="space-y-2">
                            @csrf
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Operation Issue Note') }}</label>
                            <textarea name="issue_note" rows="2" class="app-input" placeholder="{{ ui_phrase('Describe the operational issue...') }}" required></textarea>
                            <button type="submit" class="btn-ghost">{{ ui_phrase('Report Operation Issue') }}</button>
                        </form>
                    @endcan
                </div>

                <x-ui.action-panel :title="ui_phrase('Quick Actions')" :description="ui_phrase('Available actions depend on workflow status and permissions.')">
                        <a href="{{ route('bookings.reconciliation.show', $booking) }}" class="btn-secondary">{{ ui_phrase('Open Reconciliation') }}</a>
                        <form method="POST" action="{{ route('bookings.invoices.proforma', $booking) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-secondary">{{ ui_phrase('Generate Proforma') }}</button>
                        </form>
                        @if ((string) ($booking->status ?? '') === 'reconciliation')
                            <form method="POST" action="{{ route('bookings.invoices.final', $booking) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-primary">{{ ui_phrase('Generate Final Invoice') }}</button>
                            </form>
                        @endif
                </x-ui.action-panel>

                <div class="module-card p-6 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Voucher Status Summary') }}</h3>
                    @php
                        $voucherSummary = [
                            'total_items' => $booking->items->count(),
                            'with_voucher' => $booking->items->filter(fn ($i) => $i->voucher !== null)->count(),
                            'generated' => $booking->items->filter(fn ($i) => (string) ($i->voucher->status ?? '') === \App\Models\BookingItemVoucher::STATUS_GENERATED)->count(),
                            'reissued' => $booking->items->filter(fn ($i) => (string) ($i->voucher->status ?? '') === \App\Models\BookingItemVoucher::STATUS_REISSUED)->count(),
                            'used' => $booking->items->filter(fn ($i) => (string) ($i->voucher->status ?? '') === \App\Models\BookingItemVoucher::STATUS_USED)->count(),
                        ];
                    @endphp
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Items') }}</p><p>{{ (int) $voucherSummary['total_items'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('With Voucher') }}</p><p>{{ (int) $voucherSummary['with_voucher'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Generated') }}</p><p>{{ (int) $voucherSummary['generated'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Reissued') }}</p><p>{{ (int) $voucherSummary['reissued'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Used') }}</p><p>{{ (int) $voucherSummary['used'] }}</p></div>
                    </div>
                </div>

                <div class="module-card p-6 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Invoice Summary') }}</h3>
                    @php
                        $invoiceSummary = [
                            'total' => $booking->invoices->count(),
                            'proforma' => $booking->invoices->where('invoice_type', 'proforma')->count(),
                            'final' => $booking->invoices->where('invoice_type', 'final')->count(),
                            'issued' => $booking->invoices->where('status', 'issued')->count(),
                            'paid' => $booking->invoices->whereIn('status', ['paid', 'overpaid'])->count(),
                        ];
                    @endphp
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Total') }}</p><p>{{ (int) $invoiceSummary['total'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Proforma') }}</p><p>{{ (int) $invoiceSummary['proforma'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Final') }}</p><p>{{ (int) $invoiceSummary['final'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Issued') }}</p><p>{{ (int) $invoiceSummary['issued'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Paid') }}</p><p>{{ (int) $invoiceSummary['paid'] }}</p></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="app-table w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Invoice') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Type') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Status') }}</th>
                                    <th class="px-3 py-2 text-right">{{ ui_phrase('Total') }}</th>
                                    <th class="px-3 py-2 text-right">{{ ui_phrase('Paid') }}</th>
                                    <th class="px-3 py-2 text-right">{{ ui_phrase('Balance') }}</th>
                                    <th class="px-3 py-2 text-right">{{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($booking->invoices as $bookingInvoice)
                                    <tr>
                                        <td class="px-3 py-2">{{ $bookingInvoice->invoice_number }}</td>
                                        <td class="px-3 py-2"><x-ui.status-badge :status="(string) ($bookingInvoice->invoice_type ?? 'proforma')" size="xs" /></td>
                                        <td class="px-3 py-2"><x-ui.status-badge :status="(string) ($bookingInvoice->status ?? 'draft')" size="xs" /></td>
                                        <td class="px-3 py-2 text-right"><x-ui.money :amount="(float) ($bookingInvoice->total_amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></td>
                                        <td class="px-3 py-2 text-right"><x-ui.money :amount="(float) ($bookingInvoice->paid_amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></td>
                                        <td class="px-3 py-2 text-right"><x-ui.money :amount="(float) ($bookingInvoice->balance_amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('invoices.show', $bookingInvoice) }}" class="btn-outline-sm">{{ ui_phrase('View Invoice') }}</a>
                                            @can('payments.create')
                                                @if ($bookingInvoice->canReceivePayment())
                                                    <a href="{{ route('payments.create', ['invoice_id' => $bookingInvoice->id]) }}" class="btn-outline-sm">{{ ui_phrase('Record Payment') }}</a>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500">
                                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Invoices')]) }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endcan

                @can('booking_adjustments.view')
                <div class="module-card p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Adjustment Summary') }}</h3>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('bookings.adjustments.index', $booking) }}" class="btn-ghost">{{ ui_phrase('View All') }}</a>
                            @can('booking_adjustments.create')
                                <a href="{{ route('bookings.adjustments.create', $booking) }}" class="btn-secondary">{{ ui_phrase('Create Adjustment') }}</a>
                            @endcan
                        </div>
                    </div>
                    @php
                        $adjustmentSummary = [
                            'total' => $booking->adjustments->count(),
                            'pending_approval' => $booking->adjustments->where('status', 'pending_approval')->count(),
                            'approved' => $booking->adjustments->where('status', 'approved')->count(),
                            'applied' => $booking->adjustments->where('status', 'applied')->count(),
                        ];
                    @endphp
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Total') }}</p><p>{{ (int) $adjustmentSummary['total'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Pending Approval') }}</p><p>{{ (int) $adjustmentSummary['pending_approval'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Approved') }}</p><p>{{ (int) $adjustmentSummary['approved'] }}</p></div>
                        <div><p class="text-xs uppercase text-gray-500">{{ ui_phrase('Applied') }}</p><p>{{ (int) $adjustmentSummary['applied'] }}</p></div>
                    </div>
                    @php
                        $recentAdjustments = $booking->adjustments->sortByDesc('created_at')->take(5);
                    @endphp
                    @if ($recentAdjustments->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="app-table w-full text-sm">
                                <thead>
                                    <tr>
                                        <th class="px-3 py-2 text-left">{{ ui_phrase('Number') }}</th>
                                        <th class="px-3 py-2 text-left">{{ ui_phrase('Type') }}</th>
                                        <th class="px-3 py-2 text-left">{{ ui_phrase('Status') }}</th>
                                        <th class="px-3 py-2 text-right">{{ ui_phrase('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentAdjustments as $adjustment)
                                        <tr>
                                            <td class="px-3 py-2">
                                                <a href="{{ route('booking-adjustments.show', $adjustment) }}" class="text-primary-600">
                                                    {{ $adjustment->adjustment_number }}
                                                </a>
                                            </td>
                                            <td class="px-3 py-2">{{ ui_phrase((string) ($adjustment->type ?: $adjustment->adjustment_type)) }}</td>
                                            <td class="px-3 py-2"><x-ui.status-badge :status="(string) $adjustment->status" size="xs" /></td>
                                            <td class="px-3 py-2 text-right"><x-ui.money :amount="(float) ($adjustment->calculated_amount ?? $adjustment->amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @endcan

                @can('booking_settlements.view')
                <div class="module-card p-6 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Settlement Summary') }}</h3>
                        <a href="{{ route('bookings.settlement.show', $booking) }}" class="btn-ghost">{{ ui_phrase('Open Settlement') }}</a>
                    </div>
                    @php
                        $settlementStatus = (string) ($booking->settlement?->status ?? 'pending_review');
                        $settlementBlockers = (array) data_get($booking->settlement?->metadata, 'blockers', []);
                    @endphp
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Settlement Status') }}</p>
                            <p class="mt-1"><x-ui.status-badge :status="$settlementStatus" size="xs" /></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Outstanding Amount') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100"><x-ui.money :amount="(float) ($booking->settlement?->outstanding_amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Overpaid Amount') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100"><x-ui.money :amount="(float) ($booking->settlement?->overpaid_amount ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Blockers') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ count($settlementBlockers) }}</p>
                        </div>
                    </div>
                    @if ($settlementBlockers !== [])
                        <div class="rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                            {{ ui_phrase('Settlement blockers: :list', ['list' => implode(', ', $settlementBlockers)]) }}
                        </div>
                    @endif
                </div>
                @endcan

                @can('bookings.operation.dispatch')
                <div class="module-card p-6 mb-5">
                    <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Service Dispatch Checklist') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="app-table w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Service Item') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Vendor Confirmation') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Dispatch Status') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Driver / Guide') }}</th>
                                    <th class="px-3 py-2 text-right">{{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($booking->items as $dispatchIndex => $dispatchItem)
                                    @php
                                        $dispatchVendorStatus = (string) ($dispatchItem->vendor_confirmation_status ?? \App\Models\BookingItem::VENDOR_CONFIRMATION_PENDING);
                                        $dispatchStatus = (string) ($dispatchItem->dispatch_status ?? 'pending');
                                        $dispatchAssigned = trim(implode(' | ', array_filter([
                                            trim((string) ($dispatchItem->assigned_driver_name ?? '')),
                                            trim((string) ($dispatchItem->assigned_guide_name ?? '')),
                                        ])));
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">{{ $dispatchIndex + 1 }}</td>
                                        <td class="px-3 py-2">{{ $dispatchItem->description }}</td>
                                        <td class="px-3 py-2">
                                            <x-ui.status-badge :status="$dispatchVendorStatus" size="xs" />
                                            @if ($dispatchVendorStatus === \App\Models\BookingItem::VENDOR_CONFIRMATION_NOT_AVAILABLE && !empty($dispatchItem->vendor_unavailable_reason))
                                                <p class="mt-1 text-xs text-rose-600">{{ $dispatchItem->vendor_unavailable_reason }}</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">{{ ui_phrase($dispatchStatus) }}</td>
                                        <td class="px-3 py-2">{{ $dispatchAssigned !== '' ? $dispatchAssigned : '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                @can('bookings.operation.vendor_confirm')
                                                    @if ($dispatchVendorStatus !== \App\Models\BookingItem::VENDOR_CONFIRMATION_CONFIRMED)
                                                        <form method="POST" action="{{ route('bookings.items.vendor-confirm', ['booking' => $booking, 'bookingItem' => $dispatchItem]) }}">
                                                            @csrf
                                                            <button type="submit" class="btn-outline-sm">{{ ui_phrase('Confirm Vendor') }}</button>
                                                        </form>
                                                    @endif
                                                    @if ($dispatchVendorStatus !== \App\Models\BookingItem::VENDOR_CONFIRMATION_NOT_AVAILABLE)
                                                        <form method="POST" action="{{ route('bookings.items.vendor-not-available', ['booking' => $booking, 'bookingItem' => $dispatchItem]) }}" class="flex items-center gap-2">
                                                            @csrf
                                                            <input type="text" name="vendor_unavailable_reason" class="app-input" placeholder="{{ ui_phrase('Vendor not available reason') }}" required>
                                                            <button type="submit" class="btn-danger">{{ ui_phrase('Not Available') }}</button>
                                                        </form>
                                                    @endif
                                                @endcan
                                                <form method="POST" action="{{ route('bookings.items.dispatch.ready', ['booking' => $booking, 'bookingItem' => $dispatchItem]) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-outline-sm">{{ ui_phrase('Mark Item Ready') }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('bookings.items.dispatch.complete', ['booking' => $booking, 'bookingItem' => $dispatchItem]) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-outline-sm">{{ ui_phrase('Mark Item Completed') }}</button>
                                                </form>
                                            </div>
                                            <form method="POST" action="{{ route('bookings.items.dispatch.update', ['booking' => $booking, 'bookingItem' => $dispatchItem]) }}" class="mt-2 grid grid-cols-1 gap-2">
                                                @csrf
                                                @method('PATCH')
                                                @can('bookings.operation.assign_driver')
                                                    <input type="text" name="assigned_driver_name" class="app-input" placeholder="{{ ui_phrase('Driver Name') }}" value="{{ (string) ($dispatchItem->assigned_driver_name ?? '') }}">
                                                    <input type="text" name="assigned_driver_phone" class="app-input" placeholder="{{ ui_phrase('Driver Phone') }}" value="{{ (string) ($dispatchItem->assigned_driver_phone ?? '') }}">
                                                @endcan
                                                @can('bookings.operation.assign_guide')
                                                    <input type="text" name="assigned_guide_name" class="app-input" placeholder="{{ ui_phrase('Guide Name') }}" value="{{ (string) ($dispatchItem->assigned_guide_name ?? '') }}">
                                                    <input type="text" name="assigned_guide_phone" class="app-input" placeholder="{{ ui_phrase('Guide Phone') }}" value="{{ (string) ($dispatchItem->assigned_guide_phone ?? '') }}">
                                                @endcan
                                                <textarea name="operation_notes" class="app-input" rows="2" placeholder="{{ ui_phrase('Operation notes') }}">{{ (string) ($dispatchItem->operation_notes ?? '') }}</textarea>
                                                <button type="submit" class="btn-ghost">{{ ui_phrase('Update Dispatch') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('bookings.items.dispatch.issue', ['booking' => $booking, 'bookingItem' => $dispatchItem]) }}" class="mt-2 flex gap-2">
                                                @csrf
                                                <input type="text" name="issue_note" class="app-input" placeholder="{{ ui_phrase('Item issue note') }}" required>
                                                <button type="submit" class="btn-danger">{{ ui_phrase('Report Item Issue') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-3 text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Items')]) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endcan

                <div class="module-card p-6">
                    <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Booking Services') }}</h3>
                    @php
                        $bookedItemsByQuotationItemId = $booking->items->filter(fn ($item) => !empty($item->quotation_item_id))->keyBy('quotation_item_id');
                        $quotationItems = ($booking->quotation?->items ?? collect())
                            ->filter(fn ($item) => (string) ($item->itinerary_item_type ?? '') !== 'manual')
                            ->values();
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="app-table w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Service Date') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Description') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Status') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Vendor Confirmation') }}</th>
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Qty') }}</th>
                                    <th class="px-3 py-2 text-right actions-compact">{{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($quotationItems as $index => $quotationItem)
                                    @php
                                        $item = $bookedItemsByQuotationItemId->get($quotationItem->id);
                                        $latestBookingLog = $item?->latestBookingLog;
                                        $displayBookingLog = $latestBookingLog;
                                        $isBooked = $latestBookingLog !== null;
                                        $vendorStatus = (string) ($item?->vendor_confirmation_status ?? \App\Models\BookingItem::VENDOR_CONFIRMATION_PENDING);
                                        $qty = $isBooked ? (int) ($item->qty ?? 0) : (int) ($quotationItem->qty ?? 0);
                                        $serviceDate = '-';
                                        $travelDate = $booking->travel_date;
                                        $dayNumber = max(1, (int) ($quotationItem->day_number ?? 1));
                                        if ($travelDate) {
                                            $serviceDate = $travelDate->copy()->addDays($dayNumber - 1)->format('Y-m-d');
                                        } else {
                                            $serviceDate = optional($quotationItem->service_date)->format('Y-m-d')
                                                ?? optional($latestBookingLog?->service_date)->format('Y-m-d')
                                                ?? '-';
                                        }
                                        $serviceable = $quotationItem->serviceable;
                                        $rawItemType = class_basename((string) ($quotationItem->serviceable_type ?? ''));
                                        $itemType = trim((string) (preg_replace('/(?<!^)([A-Z])/', ' $1', $rawItemType) ?: ''));
                                        $vendorName = '';
                                        if ($serviceable && method_exists($serviceable, 'vendor')) {
                                            $vendorName = trim((string) ($serviceable?->vendor?->name ?? ''));
                                        }
                                        $itemName = trim((string) ($serviceable?->name ?? '')) ?: trim((string) ($quotationItem->description ?? '-'));
                                        $displayDescription = '-';
                                        if ($rawItemType === 'TransportUnit') {
                                            $itemType = ui_phrase('Transport Unit');
                                            $brand = trim((string) ($serviceable?->brand ?? ''));
                                            $transportName = trim((string) ($serviceable?->name ?? ''));
                                            $transportLabel = trim($brand . ' ' . $transportName);
                                            $parts = array_values(array_filter([$vendorName, $transportLabel], fn ($value) => $value !== ''));
                                            $displayDescription = $itemType . ': ' . ($parts !== [] ? implode(' - ', $parts) : '-');
                                        } elseif ($rawItemType === 'HotelRoom') {
                                            $itemType = ui_phrase('Hotel');
                                            $hotelName = trim((string) ($serviceable?->hotel?->name ?? ''));
                                            $roomName = trim((string) ($serviceable?->rooms ?? ''));
                                            if ($hotelName === '') {
                                                $descriptionText = trim((string) ($quotationItem->description ?? ''));
                                                if (preg_match('/Hotel:\s*(.+)$/i', $descriptionText, $matches) === 1) {
                                                    $hotelName = trim((string) ($matches[1] ?? ''));
                                                }
                                            }
                                            if ($roomName !== '') {
                                                $roomName = preg_replace('/^\s*Day\s+\d+\s*[-:]\s*/i', '', $roomName) ?? $roomName;
                                                $roomName = preg_replace('/^\s*Hotel\s*:\s*/i', '', $roomName) ?? $roomName;
                                                if ($hotelName !== '') {
                                                    $roomName = preg_replace('/^\s*' . preg_quote($hotelName, '/') . '\s*[-:|]\s*/i', '', $roomName) ?? $roomName;
                                                }
                                                $roomName = trim((string) $roomName);
                                            }
                                            $parts = array_values(array_filter([$hotelName, $roomName], fn ($value) => $value !== ''));
                                            $displayDescription = $itemType . ': ' . ($parts !== [] ? implode(' - ', $parts) : $itemName);
                                        } elseif ($rawItemType === 'Activity') {
                                            $itemType = ui_phrase('Activity');
                                            $parts = array_values(array_filter([$vendorName, $itemName], fn ($value) => $value !== ''));
                                            $displayDescription = $itemType . ': ' . ($parts !== [] ? implode(' - ', $parts) : '-');
                                        } elseif ($rawItemType === 'TouristAttraction') {
                                            $itemType = ui_phrase('Tourist Attraction');
                                            $displayDescription = $itemType . ': ' . ($itemName !== '' ? $itemName : '-');
                                        } elseif ($rawItemType === 'FoodBeverage') {
                                            $itemType = ui_phrase('Food and Beverage');
                                            $parts = array_values(array_filter([$vendorName, $itemName], fn ($value) => $value !== ''));
                                            $displayDescription = $itemType . ': ' . ($parts !== [] ? implode(' - ', $parts) : '-');
                                        } elseif ($rawItemType === 'IslandTransfer') {
                                            $itemType = ui_phrase('Island Transfer');
                                            $parts = array_values(array_filter([$vendorName, $itemName], fn ($value) => $value !== ''));
                                            $displayDescription = $itemType . ': ' . ($parts !== [] ? implode(' - ', $parts) : '-');
                                        } else {
                                            $parts = array_values(array_filter([$vendorName, $itemName], fn ($value) => $value !== ''));
                                            $displayDescription = ($itemType !== '' ? ($itemType . ': ') : '') . ($parts !== [] ? implode(' - ', $parts) : '-');
                                        }
                                        $providerDisplayName = $rawItemType === 'HotelRoom'
                                            ? trim((string) ($serviceable?->hotel?->name ?? ''))
                                            : trim((string) ($vendorName ?? ''));
                                        $baseDisplayName = trim((string) ($serviceable?->name ?? $quotationItem->description ?? '-'));
                                        if ($baseDisplayName === '') {
                                            $baseDisplayName = '-';
                                        }
                                        if ($rawItemType === 'HotelRoom') {
                                            $hotelName = trim((string) ($serviceable?->hotel?->name ?? ''));
                                            $roomName = trim((string) ($serviceable?->rooms ?? ''));
                                            if ($roomName === '') {
                                                $roomName = trim((string) ($serviceable?->name ?? $quotationItem->description ?? ''));
                                            }
                                            if ($roomName !== '') {
                                                $roomName = preg_replace('/^\s*Day\s+\d+\s*[-:]\s*/i', '', $roomName) ?? $roomName;
                                                $roomName = preg_replace('/^\s*Hotel\s*:\s*/i', '', $roomName) ?? $roomName;
                                                if ($hotelName !== '') {
                                                    $roomName = preg_replace('/^\s*' . preg_quote($hotelName, '/') . '\s*[-:|]\s*/i', '', $roomName) ?? $roomName;
                                                }
                                                $roomName = trim((string) $roomName);
                                            }
                                            $hotelParts = array_values(array_filter([$hotelName, $roomName], fn ($value) => trim((string) $value) !== ''));
                                            $baseDisplayName = $hotelParts !== [] ? implode(' - ', $hotelParts) : $baseDisplayName;
                                            $itemType = ui_phrase('Hotel');
                                        } elseif ($providerDisplayName !== '' && ! str_contains(mb_strtolower($baseDisplayName), mb_strtolower($providerDisplayName))) {
                                            $baseDisplayName = trim($baseDisplayName . ' | ' . $providerDisplayName);
                                        }
                                        $displayDescription = ($itemType !== '' ? ($itemType . ': ') : '') . $baseDisplayName;
                                        $vendorProviderValue = $vendorName;
                                        $itemTypeValue = $itemType !== '' ? $itemType : '';
                                        $vendorContactDetailValue = '';
                                        $contactedPersonValue = '';
                                        if ($serviceable && method_exists($serviceable, 'vendor')) {
                                            $vendor = $serviceable?->vendor;
                                            $contactParts = array_values(array_filter([
                                                trim((string) ($vendor?->contact_phone ?? '')),
                                                trim((string) ($vendor?->contact_email ?? '')),
                                            ], fn ($value) => $value !== ''));
                                            $vendorContactDetailValue = $contactParts !== [] ? implode(' | ', $contactParts) : '';
                                            $contactedPersonValue = trim((string) ($vendor?->contact_name ?? ''));
                                        }
                                        if ($rawItemType === 'HotelRoom') {
                                            $hotelPhone = trim((string) ($serviceable?->hotel?->phone ?? ''));
                                            $hotelContactPerson = trim((string) ($serviceable?->hotel?->contact_person ?? ''));
                                            $vendorContactDetailValue = $hotelPhone;
                                            $contactedPersonValue = $hotelContactPerson;
                                        }
                                        $serviceDateValue = $serviceDate !== '-' ? $serviceDate : '';
                                        $serviceDateReadable = $serviceDateValue !== '' ? \Illuminate\Support\Carbon::parse($serviceDateValue)->format('d M Y') : '';
                                        $vendorProviderReadonly = $vendorProviderValue !== '';
                                        $contactDetailReadonly = $vendorContactDetailValue !== '';
                                        $contactedPersonReadonly = $contactedPersonValue !== '';
                                        $serviceTypeReadonly = $itemTypeValue !== '';
                                        $serviceDateReadonly = $serviceDateValue !== '';
                                        $paxAdultValue = $booking->quotation?->pax_adult;
                                        $paxChildValue = $booking->quotation?->pax_child;
                                        $paxAdultReadonly = $paxAdultValue !== null;
                                        $paxChildReadonly = $paxChildValue !== null;
                                        $isTransportLikeType = in_array($rawItemType, ['TransportUnit', 'Activity', 'IslandTransfer'], true);
                                        $isFoodBeverageType = $rawItemType === 'FoodBeverage';
                                        $isHotelType = $rawItemType === 'HotelRoom';
                                        $isTouristAttractionType = $rawItemType === 'TouristAttraction';
                                        $transportServiceName = '';
                                        if ($rawItemType === 'TransportUnit') {
                                            $transportBrand = trim((string) ($serviceable?->brand_model ?? $serviceable?->brand ?? ''));
                                            $transportName = trim((string) ($serviceable?->name ?? ''));
                                            $transportServiceName = trim($transportBrand . ' ' . $transportName);
                                        } elseif ($isTransportLikeType || $isFoodBeverageType) {
                                            $transportServiceName = trim((string) ($serviceable?->name ?? ''));
                                        }
                                        $mealPeriodValue = $isFoodBeverageType
                                            ? (trim((string) ($serviceable?->meal_period ?? '')) ?: trim((string) data_get($quotationItem->serviceable_meta, 'meal_period', '')))
                                            : '';
                                        $hotelNameValue = $isHotelType ? trim((string) ($serviceable?->hotel?->name ?? '')) : '';
                                        $roomNameValue = $isHotelType ? trim((string) ($serviceable?->rooms ?? '')) : '';
                                        $roomNumberValue = $isHotelType ? (string) ((int) ($quotationItem->qty ?? 0)) : '';
                                        $bookingSummaryForVoucher = '-';
                                        if ($isBooked && $latestBookingLog) {
                                            $bookingServiceName = '';
                                            if ($rawItemType === 'TransportUnit') {
                                                $bookingServiceName = trim((string) $transportServiceName);
                                            } elseif ($rawItemType === 'FoodBeverage') {
                                                $bookingServiceName = trim((string) ($transportServiceName ?: $itemName));
                                            } elseif ($rawItemType === 'HotelRoom') {
                                                $bookingServiceName = trim((string) ($roomNameValue !== '' ? $roomNameValue : $hotelNameValue));
                                            } elseif (in_array($rawItemType, ['Activity', 'IslandTransfer', 'TouristAttraction'], true)) {
                                                $bookingServiceName = trim((string) $itemName);
                                            }
                                            if ($bookingServiceName === '') {
                                                $bookingServiceName = trim((string) ($displayBookingLog?->vendor_provider_item_name ?: $item->description ?: $quotationItem->description ?: 'Service'));
                                            }
                                            $bookingCreatedBy = trim((string) ($displayBookingLog?->creator?->name ?: 'Unknown user'));
                                            $bookingCreatedAt = optional($displayBookingLog?->created_at)->format('Y-m-d (H:i)') ?? '-';
                                            $bookingSummaryForVoucher = ui_phrase(':service was booked by :user on :datetime.', [
                                                'service' => $bookingServiceName,
                                                'user' => $bookingCreatedBy,
                                                'datetime' => $bookingCreatedAt,
                                            ]);
                                        }
                                        $voucherTourName = trim((string) ($item?->voucher?->tour_name ?? ''));
                                        if ($voucherTourName === '') {
                                            $orderNumberForTour = trim((string) ($booking->quotation?->order_number ?? ''));
                                            $customerNameForTour = trim((string) ($booking->quotation?->inquiry?->customer?->name ?? ''));
                                            $agentNameForTour = trim((string) ($booking->quotation?->inquiry?->customer?->company_name ?? ''));
                                            $customerOrAgentForTour = $agentNameForTour !== '' ? $agentNameForTour : $customerNameForTour;
                                            $voucherTourName = trim($orderNumberForTour . ' - ' . $customerOrAgentForTour);
                                        }
                                        $voucherServiceDate = optional($latestBookingLog?->service_date)->format('Y-m-d')
                                            ?? optional($item?->voucher?->service_date)->format('Y-m-d')
                                            ?? optional($booking->travel_date)->format('Y-m-d')
                                            ?? '-';
                                        $voucherServiceItem = trim((string) ($latestBookingLog?->vendor_provider_item_name ?? ''));
                                        if ($voucherServiceItem === '') {
                                            $voucherServiceItem = trim((string) ($displayDescription ?: $item?->description ?: $quotationItem->description ?: '-'));
                                        }
                                        $voucherVendorName = trim((string) ($latestBookingLog?->vendor_provider_item_name ?? ''));
                                        if ($voucherVendorName === '') {
                                            $voucherVendorName = trim((string) ($item?->voucher?->vendor_contact_name ?? $vendorProviderValue ?? '-'));
                                        }
                                        $voucherVendorPhone = trim(implode(' | ', array_filter([
                                            trim((string) ($latestBookingLog?->contact_channel ?? '')),
                                            trim((string) ($latestBookingLog?->contact_value ?? '')),
                                        ])));
                                        if ($voucherVendorPhone === '' && ! $latestBookingLog) {
                                            $voucherVendorPhone = trim((string) ($item?->voucher?->vendor_contact_phone ?? ''));
                                        }
                                        if ($voucherVendorPhone === '' && ! $latestBookingLog) {
                                            $voucherVendorPhone = trim((string) ($vendorContactDetailValue ?: '-'));
                                        }
                                        $voucherVendorEmail = trim((string) ($item?->voucher?->vendor_contact_email ?? '-'));
                                        if (strtolower(trim((string) ($latestBookingLog?->contact_channel ?? ''))) === 'email' && trim((string) ($latestBookingLog?->contact_value ?? '')) !== '') {
                                            $voucherVendorEmail = trim((string) $latestBookingLog?->contact_value);
                                        }
                                        $voucherConfirmation = trim((string) ($latestBookingLog?->confirmation_number ?? ''));
                                        if ($voucherConfirmation === '') {
                                            $voucherConfirmation = trim((string) ($item?->voucher?->confirmation_code ?? '-'));
                                        }
                                        $voucherQtyText = (string) (int) ($item?->qty ?? 0);
                                        if ($latestBookingLog) {
                                            $voucherQtyText = (string) ((int) ($latestBookingLog->pax_adult ?? 0) + (int) ($latestBookingLog->pax_child ?? 0));
                                        }
                                        $bookingAtValue = optional($displayBookingLog?->booked_at)->format('Y-m-d (H:i)') ?? '-';
                                        $bookingChannelValue = trim((string) ($displayBookingLog?->contact_channel ?? '')) ?: '-';
                                        $bookingContactedValue = trim((string) ($displayBookingLog?->contacted_person_name ?? '')) ?: '-';
                                        $bookingContactDetailValue = trim((string) ($displayBookingLog?->contact_value ?? '')) ?: '-';
                                        $voucherToName = trim((string) ($latestBookingLog?->vendor_provider_item_name ?? ''));
                                        if ($voucherToName === '') {
                                            $voucherToName = trim((string) ($voucherVendorName ?: $displayDescription ?: '-'));
                                        }
                                        $voucherToLocation = '';
                                        if ($rawItemType === 'HotelRoom') {
                                            $voucherToLocation = trim(implode(', ', array_filter([
                                                trim((string) ($serviceable?->hotel?->address ?? '')),
                                                trim((string) ($serviceable?->hotel?->city ?? '')),
                                                trim((string) ($serviceable?->hotel?->province ?? '')),
                                            ])));
                                        } elseif (method_exists($serviceable, 'vendor')) {
                                            $vendorLocationText = trim((string) ($serviceable?->vendor?->location ?? ''));
                                            $vendorAddressText = trim((string) ($serviceable?->vendor?->address ?? ''));
                                            $voucherToLocation = $vendorLocationText !== '' ? $vendorLocationText : $vendorAddressText;
                                        }
                                        if ($voucherToLocation === '' && ! $latestBookingLog) {
                                            $voucherToLocation = '-';
                                        } elseif ($voucherToLocation === '') {
                                            $voucherToLocation = '-';
                                        }
                                        $voucherToContact = trim(implode(' | ', array_filter([
                                            trim((string) ($latestBookingLog?->contact_channel ?? '')),
                                            trim((string) ($latestBookingLog?->contact_value ?? '')),
                                        ])));
                                        if ($voucherToContact === '' && ! $latestBookingLog) {
                                            $contactParts = [];
                                            if ($rawItemType === 'HotelRoom') {
                                                $contactParts = array_values(array_filter([
                                                    trim((string) ($serviceable?->hotel?->phone ?? '')),
                                                    trim((string) ($serviceable?->hotel?->email ?? '')),
                                                    trim((string) ($serviceable?->hotel?->whatsapp ?? '')),
                                                ]));
                                            } elseif (method_exists($serviceable, 'vendor')) {
                                                $vendor = $serviceable?->vendor;
                                                $contactParts = array_values(array_filter([
                                                    trim((string) ($vendor?->contact_phone ?? '')),
                                                    trim((string) ($vendor?->contact_email ?? '')),
                                                    trim((string) ($vendor?->website ?? '')),
                                                ]));
                                            }
                                            $voucherToContact = $contactParts !== [] ? implode(' | ', $contactParts) : '-';
                                        }
                                    @endphp
                                    <tr class="odd:bg-gray-50 even:bg-white hover:bg-amber-50 dark:odd:bg-gray-800/40 dark:even:bg-gray-900/40 dark:hover:bg-amber-900/20 transition-colors">
                                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                                        <td class="px-3 py-2">{{ $serviceDate }}</td>
                                        <td class="px-3 py-2">{{ $displayDescription }}</td>
                                        <td class="px-3 py-2">
                                            @if ($isBooked)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ ui_phrase('Booked') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ ui_phrase('Unbooked') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2"><x-ui.status-badge :status="$vendorStatus" size="xs" /></td>
                                        <td class="px-3 py-2">{{ $qty }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($isBooked && $item && $item->voucher)
                                                    <x-ui.status-badge :status="(string) ($item->voucher->status ?? \App\Models\BookingItemVoucher::STATUS_DRAFT)" size="xs" />
                                                    @if (!empty($item->voucher->revision_number))
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Revision') }} R{{ (int) $item->voucher->revision_number }}</span>
                                                    @endif
                                                    @if (($sourceUpdatedMap[$item->id] ?? false) === true)
                                                        <span class="text-xs text-amber-600 dark:text-amber-300">{{ ui_phrase('Source updated, voucher needs reissue.') }}</span>
                                                    @endif
                                                    <button
                                                        type="button"
                                                        class="btn-secondary-sm"
                                                        title="{{ ui_phrase('View Voucher') }}"
                                                        aria-label="{{ ui_phrase('View Voucher') }}"
                                                        data-voucher-open="1"
                                                        x-on:click.prevent="openVoucherModal($el)"
                                                        data-voucher-number="{{ $item->voucher->voucher_number }}"
                                                        data-voucher-status="{{ strtoupper((string) $item->voucher->status) }}"
                                                        data-voucher-item="{{ $voucherServiceItem }}"
                                                        data-booking-summary="{{ $bookingSummaryForVoucher }}"
                                                        data-booking-at="{{ $bookingAtValue }}"
                                                        data-booking-channel="{{ $bookingChannelValue }}"
                                                        data-booking-contacted="{{ $bookingContactedValue }}"
                                                        data-booking-contact-detail="{{ $bookingContactDetailValue }}"
                                                        data-voucher-qty="{{ $voucherQtyText }}"
                                                        data-voucher-customer="{{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}"
                                                        data-voucher-tour-name="{{ $voucherTourName !== '' ? $voucherTourName : '-' }}"
                                                        data-voucher-service-date="{{ $voucherServiceDate }}"
                                                        data-voucher-service-time="{{ $item->voucher->service_time ?: '-' }}"
                                                        data-voucher-pickup="{{ $item->voucher->pickup_location ?: '-' }}"
                                                        data-voucher-vendor-name="{{ $voucherToName !== '' ? $voucherToName : '-' }}"
                                                        data-voucher-vendor-phone="{{ $voucherVendorPhone !== '' ? $voucherVendorPhone : '-' }}"
                                                        data-voucher-vendor-email="{{ $voucherVendorEmail !== '' ? $voucherVendorEmail : '-' }}"
                                                        data-voucher-to-location="{{ $voucherToLocation }}"
                                                        data-voucher-to-contact="{{ $voucherToContact }}"
                                                        data-voucher-confirmation="{{ $voucherConfirmation !== '' ? $voucherConfirmation : '-' }}"
                                                    >
                                                        <i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('View Voucher') }}</span>
                                                    </button>
                                                    <a href="{{ route('booking-items.voucher.pdf', $item) }}" target="_blank" rel="noopener" class="btn-outline-sm" title="{{ ui_phrase('Preview Voucher PDF') }}" aria-label="{{ ui_phrase('Preview Voucher PDF') }}">
                                                        <i class="fa-solid fa-file-pdf"></i><span class="sr-only">{{ ui_phrase('Preview Voucher PDF') }}</span>
                                                    </a>
                                                @elseif ($isBooked && $item)
                                                    @if ($item->isVendorConfirmed())
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Voucher is being prepared.') }}</span>
                                                    @else
                                                        <span class="text-xs text-amber-600 dark:text-amber-300">{{ ui_phrase('Voucher is blocked until vendor is confirmed.') }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Manage booking service from Edit Booking page.') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                @empty
                                    <tr class="odd:bg-gray-50 even:bg-white hover:bg-amber-50 dark:odd:bg-gray-800/40 dark:even:bg-gray-900/40 dark:hover:bg-amber-900/20 transition-colors">
                                        <td colspan="7" class="px-3 py-3 text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Items')]) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <aside class="module-grid-side">
                @include('partials._audit-info', ['record' => $booking])
            </aside>
        </div>
    </div>

    <x-modal name="booking-voucher-modal" focusable maxWidth="2xl">
        <div
            class="p-5"
            x-data="{
                voucher: {
                    number: '-',
                    status: '-',
                    item: '-',
                    bookingSummary: '-',
                    customer: '-',
                    tourName: '-',
                    qty: '-',
                    serviceDate: '-',
                    serviceTime: '-',
                pickup: '-',
                vendorName: '-',
                vendorPhone: '-',
                vendorEmail: '-',
                toLocation: '-',
                toContact: '-',
                confirmation: '-',
                contactedPerson: '-',
                contactChannel: '-',
                contactDetail: '-',
                },
            }"
            x-init="
                window.addEventListener('booking-voucher-preview-updated', (event) => {
                    voucher = { ...voucher, ...(event.detail || {}) };
                });
            "
        >
            <div class="border border-gray-900 text-gray-900 dark:border-gray-200 dark:text-gray-100">
                <div class="border-b border-gray-900 px-3 py-2 text-xs dark:border-gray-200">
                    <p id="voucher-booking-summary">{{ ui_phrase('No booking log available.') }}</p>
                </div>
                <div class="grid grid-cols-12 border-b border-gray-900 dark:border-gray-200">
                    <div class="col-span-5 border-r border-gray-900 p-3 dark:border-gray-200">
                        <p class="text-xl font-bold">{{ $companyName }}</p>
                        <p class="text-xs">{{ $companyAddress !== '' ? $companyAddress : '-' }}</p>
                        <p class="text-xs">{{ ui_phrase('E-mail') }} : {{ $companyEmail !== '' ? $companyEmail : '-' }}</p>
                    </div>
                    <div class="col-span-7 p-3">
                        <p class="text-4xl font-bold leading-none text-center">{{ ui_phrase('Voucher') }}</p>
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
                        @if (is_file(public_path('assets/images/stempel_bali_kami.png')))
                            <img
                                src="{{ asset('assets/images/stempel_bali_kami.png') }}"
                                alt="Official Stamp"
                                class="mt-2 h-auto w-28 object-contain"
                            >
                        @else
                            <div class="mt-10 text-xs text-gray-600 dark:text-gray-400">{{ ui_phrase('Stamp image not found') }}</div>
                        @endif
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
                    <div class="col-span-4 border-r border-gray-900 p-2 dark:border-gray-200">
                        <p class="font-semibold">{{ ui_phrase('Tour Guide') }}:</p>
                    </div>
                    <div class="col-span-3 p-2">
                        <p class="font-semibold">{{ ui_phrase('Remarks') }}</p>
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-700 dark:text-gray-300">
                {{ ui_phrase('This voucher not valid unless officially signed & stamp. Please attach original voucher for billing.') }}
            </p>
            <div class="mt-3 flex justify-end">
                <button
                    type="button"
                    class="btn-ghost px-2 py-1 text-xs"
                    x-on:click.prevent="$dispatch('close-modal', 'booking-voucher-modal')"
                >
                    {{ ui_phrase('Close') }}
                </button>
            </div>
        </div>
    </x-modal>
@endsection

