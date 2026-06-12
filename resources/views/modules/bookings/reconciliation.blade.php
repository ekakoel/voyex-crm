@extends('layouts.master')

@section('page_title', ui_phrase('Booking Reconciliation'))
@section('page_subtitle', ui_phrase('Validate actual service usage before final invoice.'))

@section('content')
    @php
        $bookingStatusCurrent = (string) ($booking->status ?? 'created');
        $workflowSteps = [
            ['key' => 'created', 'label' => ui_phrase('Created')],
            ['key' => 'vendor_confirmation', 'label' => ui_phrase('Vendor Confirmation')],
            ['key' => 'voucher_preparation', 'label' => ui_phrase('Voucher Preparation')],
            ['key' => 'ready_to_operate', 'label' => ui_phrase('Ready to Operate')],
            ['key' => 'in_operation', 'label' => ui_phrase('In Operation')],
            ['key' => 'service_completed', 'label' => ui_phrase('Service Completed')],
            ['key' => 'reconciliation', 'label' => ui_phrase('Reconciliation')],
            ['key' => 'invoiced', 'label' => ui_phrase('Invoiced')],
            ['key' => 'closed', 'label' => ui_phrase('Closed')],
        ];
        $isLocked = in_array($bookingStatusCurrent, ['reconciliation', 'completed_settled', 'closed', 'cancelled'], true);

        $items = $booking->items ?? collect();
        $totalItems = (int) $items->count();
        $usedItems = (int) $items->where('status', \App\Models\BookingItem::STATUS_USED)->count();
        $cancelledItems = (int) $items->where('status', \App\Models\BookingItem::STATUS_CANCELLED)->count();
        $adjustmentTotal = (float) $booking->adjustments->sum(fn ($adj) => (float) ($adj->calculated_amount ?? $adj->amount ?? 0));
        $cancellationTotal = (float) $items->sum(fn ($item) => (float) ($item->cancellation_fee ?? 0));
        $usedItemsTotal = (float) $items
            ->where('status', \App\Models\BookingItem::STATUS_USED)
            ->sum(fn ($item) => (float) (($item->unit_price ?? 0) * ($item->qty ?? 0)));
        $addedItemsTotal = (float) $booking->adjustments
            ->where('type', 'add_item')
            ->sum(fn ($adj) => (float) ($adj->calculated_amount ?? $adj->amount ?? 0));
        $discountRefundTotal = (float) $booking->adjustments
            ->whereIn('type', ['discount', 'refund'])
            ->sum(fn ($adj) => (float) ($adj->calculated_amount ?? $adj->amount ?? 0));
        $finalPreviewTotal = $usedItemsTotal + $cancellationTotal + $addedItemsTotal + $adjustmentTotal - abs($discountRefundTotal);
        $invoiceStatus = (string) ($booking->invoices->sortByDesc('id')->first()?->status ?? '-');
        $displayCurrency = (string) ($currentCurrency ?? \App\Support\Currency::current() ?? 'IDR');
        $adjustmentTotalLabel = \App\Support\Currency::format($adjustmentTotal, $displayCurrency);
        $finalPreviewTotalLabel = \App\Support\Currency::format($finalPreviewTotal, $displayCurrency);
    @endphp

    <div class="space-y-6 module-page module-page--bookings">
        <x-ui.page-header :title="ui_phrase('Booking Reconciliation')" :subtitle="ui_phrase('Confirm actual used service and adjustments before generating final invoice.')">
            <x-slot:breadcrumb>
                <a href="{{ route('bookings.index') }}" class="hover:underline">{{ ui_phrase('Bookings') }}</a>
                <span>/</span>
                <a href="{{ route('bookings.show', $booking) }}" class="hover:underline">{{ $booking->booking_number }}</a>
                <span>/</span>
                <span>{{ ui_phrase('Reconciliation') }}</span>
            </x-slot:breadcrumb>
            <x-slot:actions>
                <a href="{{ route('bookings.show', $booking) }}" class="btn-ghost">{{ ui_phrase('Back to Booking Detail') }}</a>
            </x-slot:actions>
        </x-ui.page-header>

        <x-ui.workflow-stepper :steps="$workflowSteps" :current="$bookingStatusCurrent" :title="ui_phrase('Workflow Progress')" />

        @if ($bookingStatusCurrent === 'cancelled')
            <x-ui.lock-alert :title="ui_phrase('Booking Cancelled')" :message="ui_phrase('This booking has been cancelled. Reconciliation update actions are locked.')" type="danger" />
        @elseif ($isLocked)
            <x-ui.lock-alert :title="ui_phrase('Reconciliation Locked')" :message="ui_phrase('Reconciliation has been finalized. Booking items are locked.')" type="warning" />
        @endif

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-5">
            <x-ui.metric-card :title="ui_phrase('Total Booking Items')" :value="$totalItems" icon="fa-solid fa-list" />
            <x-ui.metric-card :title="ui_phrase('Used Items')" :value="$usedItems" icon="fa-solid fa-circle-check" />
            <x-ui.metric-card :title="ui_phrase('Cancelled Items')" :value="$cancelledItems" icon="fa-solid fa-ban" />
            <x-ui.metric-card :title="ui_phrase('Adjustment Total')" :value="$adjustmentTotalLabel" icon="fa-solid fa-sliders" />
            <x-ui.metric-card :title="ui_phrase('Final Invoice Preview')" :value="$finalPreviewTotalLabel" icon="fa-solid fa-file-invoice-dollar" />
        </div>

        <x-ui.section-card :title="ui_phrase('Booking Summary')">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 text-sm">
                <div>
                    <p class="text-xs text-gray-500">{{ ui_phrase('Booking Number') }}</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $booking->booking_number }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ ui_phrase('Quotation Number') }}</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $booking->quotation?->quotation_number ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ ui_phrase('Customer / Agent') }}</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ ui_phrase('Travel Date') }}</p>
                    <p class="font-semibold text-gray-800 dark:text-gray-100"><x-ui.date-display :date="$booking->travel_date" format="Y-m-d" /></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ ui_phrase('Booking Status') }}</p>
                    <p><x-ui.status-badge :status="$bookingStatusCurrent" size="xs" /></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">{{ ui_phrase('Invoice Status') }}</p>
                    <p><x-ui.status-badge :status="$invoiceStatus" size="xs" /></p>
                </div>
            </div>
        </x-ui.section-card>

        <x-ui.section-card :title="ui_phrase('Booking Item Reconciliation')">
            <x-ui.data-table :empty="$items->isEmpty()">
                <x-slot:head>
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Date') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Item Description') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Vendor / Provider') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Voucher Status') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Vendor Confirmation') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Used / Not Used') }}</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Cancellation Fee') }}</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Adjustment Amount') }}</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ ui_phrase('Action') }}</th>
                    </tr>
                </x-slot:head>
                @forelse($items as $index => $item)
                    @php
                        $adjustmentAmount = (float) $item->adjustments->sum(fn ($adj) => (float) ($adj->calculated_amount ?? $adj->amount ?? 0));
                        $vendorName = trim((string) ($item->vendor?->name ?? $item->serviceable?->vendor?->name ?? ''));
                    @endphp
                    <tr>
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2"><x-ui.date-display :date="$item->service_date" format="Y-m-d" /></td>
                        <td class="px-3 py-2">
                            <p class="font-medium text-gray-800 dark:text-gray-100">{{ $item->description }}</p>
                            <p class="text-xs text-gray-500">{{ ui_phrase('Qty') }}: {{ (int) ($item->qty ?? 0) }}</p>
                        </td>
                        <td class="px-3 py-2">{{ $vendorName !== '' ? $vendorName : '-' }}</td>
                        <td class="px-3 py-2">
                            @if ($item->voucher)
                                <x-ui.status-badge :status="(string) ($item->voucher->status ?? 'draft')" size="xs" />
                            @else
                                <span class="text-xs text-gray-500">{{ ui_phrase('Not generated') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2"><x-ui.status-badge :status="(string) ($item->vendor_confirmation_status ?? 'pending_vendor')" size="xs" /></td>
                        <td class="px-3 py-2"><x-ui.status-badge :status="(string) ($item->status ?? 'active')" size="xs" /></td>
                        <td class="px-3 py-2 text-right"><x-ui.money :amount="(float) ($item->cancellation_fee ?? 0)" :currency="$currentCurrency ?? 'IDR'" /></td>
                        <td class="px-3 py-2 text-right"><x-ui.money :amount="$adjustmentAmount" :currency="$currentCurrency ?? 'IDR'" /></td>
                        <td class="px-3 py-2 text-right">
                            @if (! $isLocked)
                                <div class="flex flex-wrap justify-end gap-2">
                                    <form method="POST" action="{{ route('bookings.reconciliation.items.update', [$booking, $item]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="mark_used">
                                        <button class="btn-outline-sm" type="submit">{{ ui_phrase('Mark Used') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('bookings.reconciliation.items.update', [$booking, $item]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="mark_not_used">
                                        <button class="btn-outline-sm" type="submit">{{ ui_phrase('Mark Not Used') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('bookings.reconciliation.items.update', [$booking, $item]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="cancel_free">
                                        <button class="btn-ghost" type="submit">{{ ui_phrase('Cancel Free') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('bookings.reconciliation.items.update', [$booking, $item]) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="cancel_with_charge">
                                        <input type="number" step="0.01" min="0" name="cancellation_fee" class="app-input w-28" placeholder="{{ ui_phrase('Fee') }}">
                                        <button class="btn-secondary" type="submit">{{ ui_phrase('Cancel With Charge') }}</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-xs text-gray-500">{{ ui_phrase('Locked') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-slot:emptyState>
                        <tr>
                            <td colspan="10" class="px-3 py-4">
                                <x-ui.empty-state :title="ui_phrase('No booking items found.')" :description="ui_phrase('This booking does not have reconciliation items yet.')" />
                            </td>
                        </tr>
                    </x-slot:emptyState>
                @endforelse
            </x-ui.data-table>
        </x-ui.section-card>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <x-ui.section-card :title="ui_phrase('Adjustment Summary')">
                @php
                    $groupedAdjustment = $booking->adjustments->groupBy(fn ($adj) => (string) ($adj->type ?? ''));
                @endphp
                @if ($booking->adjustments->isNotEmpty())
                    <div class="space-y-2 text-sm">
                        @foreach ($groupedAdjustment as $type => $rows)
                            <div class="flex items-center justify-between gap-2">
                                <span>{{ ui_phrase($type !== '' ? $type : 'adjustment') }}</span>
                                <x-ui.money :amount="(float) $rows->sum(fn ($adj) => (float) ($adj->calculated_amount ?? $adj->amount ?? 0))" :currency="$currentCurrency ?? 'IDR'" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="ui_phrase('No adjustments available.')" :description="ui_phrase('Adjustment entries will appear after item changes and reconciliation actions.')" />
                @endif
            </x-ui.section-card>

            <x-ui.section-card :title="ui_phrase('Final Invoice Preview')">
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ ui_phrase('Used Items Total') }}</span>
                        <x-ui.money :amount="$usedItemsTotal" :currency="$currentCurrency ?? 'IDR'" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ ui_phrase('Cancellation Charges') }}</span>
                        <x-ui.money :amount="$cancellationTotal" :currency="$currentCurrency ?? 'IDR'" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ ui_phrase('Added Items') }}</span>
                        <x-ui.money :amount="$addedItemsTotal" :currency="$currentCurrency ?? 'IDR'" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ ui_phrase('Discount / Refund') }}</span>
                        <x-ui.money :amount="abs($discountRefundTotal)" :currency="$currentCurrency ?? 'IDR'" />
                    </div>
                    <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>
                    <div class="flex items-center justify-between gap-2 font-semibold">
                        <span>{{ ui_phrase('Grand Total') }}</span>
                        <x-ui.money :amount="$finalPreviewTotal" :currency="$currentCurrency ?? 'IDR'" />
                    </div>
                </div>
            </x-ui.section-card>
        </div>

        <x-ui.action-panel :title="ui_phrase('Finalize Actions')" :description="ui_phrase('Finalize reconciliation before generating final invoice.')">
            @if (! $isLocked)
                <form method="POST" action="{{ route('bookings.reconciliation.finalize', $booking) }}">
                    @csrf
                    <button type="submit" class="btn-primary">{{ ui_phrase('Finalize Reconciliation') }}</button>
                </form>
            @endif
            @if ((string) ($booking->status ?? '') === 'reconciliation')
                <form method="POST" action="{{ route('bookings.invoices.final', $booking) }}">
                    @csrf
                    <button type="submit" class="btn-secondary">{{ ui_phrase('Generate Final Invoice') }}</button>
                </form>
            @endif
            <a href="{{ route('bookings.show', $booking) }}" class="btn-ghost">{{ ui_phrase('Back to Booking') }}</a>
        </x-ui.action-panel>
    </div>
@endsection
