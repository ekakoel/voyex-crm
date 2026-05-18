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
            @can('update', $booking)
                @if (! $booking->isFinal())
                    <a href="{{ route('bookings.edit', $booking) }}"  class="btn-primary">
                        {{ ui_phrase('Edit') }}
                    </a>
                @endif
            @endcan
            <a href="{{ route('bookings.index') }}"  class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
        @endsection

        <div class="module-grid-8-4">
            <div class="module-grid-main">
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
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->travel_date?->format('Y-m-d') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Pax (Adult/Child)') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ (int) ($booking->quotation?->pax_adult ?? 0) }} / {{ (int) ($booking->quotation?->pax_child ?? 0) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Destination') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation?->itinerary?->destination?->name ?? $booking->quotation?->itinerary?->destination ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ ui_phrase('Itinerary') }}</p>
                            <p class="text-sm text-gray-800 dark:text-gray-100">{{ $booking->quotation?->itinerary?->title ?? '-' }}</p>
                        </div>
                    </div>
                </div>

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
                                        <td class="px-3 py-2">{{ $qty }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($isBooked && $item && $item->voucher)
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
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Voucher is being prepared.') }}</span>
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Manage booking service from Edit Booking page.') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                @empty
                                    <tr class="odd:bg-gray-50 even:bg-white hover:bg-amber-50 dark:odd:bg-gray-800/40 dark:even:bg-gray-900/40 dark:hover:bg-amber-900/20 transition-colors">
                                        <td colspan="6" class="px-3 py-3 text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Items')]) }}</td>
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






