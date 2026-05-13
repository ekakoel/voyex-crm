@extends('layouts.master')

@section('page_title', ui_phrase('Booking Detail'))
@section('page_subtitle', ui_phrase('Review complete booking and quotation information.'))

@section('content')
    <div
        class="space-y-6 module-page module-page--bookings"
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
                confirmation: '-',
                notes: '-',
            },
            openVoucherModal(el) {
                const text = (value) => String(value ?? '').trim() || '-';
                this.voucher.number = text(el.dataset.voucherNumber);
                this.voucher.status = text(el.dataset.voucherStatus);
                this.voucher.item = text(el.dataset.voucherItem);
                const bookingSummary = text(el.dataset.bookingSummary);
                const bookingAt = text(el.dataset.bookingAt);
                const bookingChannel = text(el.dataset.bookingChannel);
                const bookingContacted = text(el.dataset.bookingContacted);
                this.voucher.bookingSummary = bookingSummary !== '-'
                    ? bookingSummary
                    : `${bookingAt} | ${bookingChannel} | ${bookingContacted}`;
                this.voucher.customer = text(el.dataset.voucherCustomer);
                this.voucher.tourName = text(el.dataset.voucherTourName);
                this.voucher.qty = text(el.dataset.voucherQty);
                this.voucher.serviceDate = text(el.dataset.voucherServiceDate);
                this.voucher.serviceTime = text(el.dataset.voucherServiceTime);
                this.voucher.pickup = text(el.dataset.voucherPickup);
                this.voucher.vendorName = text(el.dataset.voucherVendorName);
                this.voucher.vendorPhone = text(el.dataset.voucherVendorPhone);
                this.voucher.vendorEmail = text(el.dataset.voucherVendorEmail);
                this.voucher.confirmation = text(el.dataset.voucherConfirmation);
                this.voucher.notes = text(el.dataset.voucherNotes);
                const bookingSummaryEl = document.getElementById('voucher-booking-summary');
                if (bookingSummaryEl) {
                    bookingSummaryEl.textContent = this.voucher.bookingSummary !== '-'
                        ? this.voucher.bookingSummary
                        : 'No booking log available.';
                }
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
                                    <th class="px-3 py-2 text-left">{{ ui_phrase('Notes') }}</th>
                                    <th class="px-3 py-2 text-right actions-compact">{{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($quotationItems as $index => $quotationItem)
                                    @php
                                        $item = $bookedItemsByQuotationItemId->get($quotationItem->id);
                                        $latestBookingLog = $item?->latestBookingLog;
                                        $displayBookingLog = $item?->bookingLogs?->first() ?? $latestBookingLog;
                                        $isBooked = $latestBookingLog !== null;
                                        $qty = $isBooked ? (int) ($item->qty ?? 0) : (int) ($quotationItem->qty ?? 0);
                                        $notes = $isBooked ? ($item->notes ?: '-') : '-';
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
                                            $itemType = ui_phrase('Hotel Room');
                                            $hotelName = trim((string) ($serviceable?->hotel?->name ?? ''));
                                            $roomName = trim((string) ($serviceable?->rooms ?? ''));
                                            if ($hotelName === '') {
                                                $descriptionText = trim((string) ($quotationItem->description ?? ''));
                                                if (preg_match('/Hotel:\s*(.+)$/i', $descriptionText, $matches) === 1) {
                                                    $hotelName = trim((string) ($matches[1] ?? ''));
                                                }
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
                                            $notes = trim(implode(' | ', array_filter([
                                                optional($latestBookingLog->booked_at)->format('Y-m-d (H:i)'),
                                                $latestBookingLog->contact_channel ?: null,
                                                $latestBookingLog->contacted_person_name ?: null,
                                            ])));
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
                                            $bookingSummaryForVoucher = $bookingServiceName . ' was booked by ' . $bookingCreatedBy . ' on ' . $bookingCreatedAt;
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
                                        <td class="px-3 py-2">{{ $notes }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if ($isBooked && $item && $item->voucher)
                                                    @if (($sourceUpdatedMap[$item->id] ?? false) === true)
                                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ ui_phrase('Source Updated') }}</span>
                                                        <form action="{{ route('booking-items.voucher.generate', $item) }}" method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit" class="btn-outline-sm" title="{{ ui_phrase('Regenerate Voucher') }}" aria-label="{{ ui_phrase('Regenerate Voucher') }}">
                                                                <i class="fa-solid fa-rotate"></i><span class="sr-only">{{ ui_phrase('Regenerate Voucher') }}</span>
                                                            </button>
                                                        </form>
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
                                                        data-voucher-item="{{ $item->description }}"
                                                        data-booking-summary="{{ $bookingSummaryForVoucher }}"
                                                        data-voucher-qty="{{ (int) $item->qty }}"
                                                        data-voucher-customer="{{ $booking->quotation?->inquiry?->customer?->name ?? '-' }}"
                                                        data-voucher-tour-name="{{ $item->voucher->tour_name ?: '-' }}"
                                                        data-voucher-service-date="{{ optional($item->voucher->service_date)->format('Y-m-d') ?? optional($booking->travel_date)->format('Y-m-d') ?? '-' }}"
                                                        data-voucher-service-time="{{ $item->voucher->service_time ?: '-' }}"
                                                        data-voucher-pickup="{{ $item->voucher->pickup_location ?: '-' }}"
                                                        data-voucher-vendor-name="{{ $item->voucher->vendor_contact_name ?: '-' }}"
                                                        data-voucher-vendor-phone="{{ $item->voucher->vendor_contact_phone ?: '-' }}"
                                                        data-voucher-vendor-email="{{ $item->voucher->vendor_contact_email ?: '-' }}"
                                                        data-voucher-confirmation="{{ $item->voucher->confirmation_code ?: '-' }}"
                                                        data-voucher-notes="{{ $item->voucher->notes ?: '-' }}"
                                                    >
                                                        <i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('View Voucher') }}</span>
                                                    </button>
                                                    <a href="{{ route('booking-items.voucher.pdf', $item) }}" class="btn-outline-sm" title="{{ ui_phrase('Download Voucher PDF') }}" aria-label="{{ ui_phrase('Download Voucher PDF') }}">
                                                        <i class="fa-solid fa-file-pdf"></i><span class="sr-only">{{ ui_phrase('Download Voucher PDF') }}</span>
                                                    </a>
                                                    <button
                                                        type="button"
                                                        class="btn-outline-sm"
                                                        title="{{ ui_phrase('Edit Booking Service') }}"
                                                        aria-label="{{ ui_phrase('Edit Booking Service') }}"
                                                        x-on:click.prevent="$dispatch('open-modal', 'edit-book-service-modal-{{ $quotationItem->id }}')"
                                                    >
                                                        <i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('Edit Booking Service') }}</span>
                                                    </button>
                                                @elseif ($isBooked && $item)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Voucher is being prepared.') }}</span>
                                                @else
                                                    <button
                                                        type="button"
                                                        class="btn-secondary-sm"
                                                        title="{{ ui_phrase('Book') }}"
                                                        aria-label="{{ ui_phrase('Book') }}"
                                                        x-on:click.prevent="$dispatch('open-modal', 'book-service-modal-{{ $quotationItem->id }}')"
                                                    >
                                                        <i class="fa-solid fa-cart-plus"></i><span class="sr-only">{{ ui_phrase('Book') }}</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @if (! $isBooked)
                                        <x-modal name="book-service-modal-{{ $quotationItem->id }}" focusable maxWidth="2xl">
                                            <div class="p-5">
                                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Book Service Item') }}</h3>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $quotationItem->description }}</p>

                                                <form method="POST" action="{{ route('bookings.services.book', ['booking' => $booking, 'quotationItem' => $quotationItem]) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    @csrf

                                                    <div class="sm:col-span-2 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Booking Detail') }}</h4>
                                                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                            @php
                                                                $vendorProviderFinal = (string) ($isHotelType ? $hotelNameValue : $vendorProviderValue);
                                                                if ($isTouristAttractionType) {
                                                                    $vendorProviderFinal = trim((string) ($itemName ?? ''));
                                                                }
                                                                $serviceTypeFinal = (string) ($isHotelType ? ($roomNameValue !== '' ? $roomNameValue : 'Hotel Room') : $itemTypeValue);
                                                                $serviceDateFinal = (string) $serviceDateValue;
                                                                $paxAdultFinal = $paxAdultValue !== null ? (string) ((int) $paxAdultValue) : '';
                                                                $paxChildFinal = $paxChildValue !== null ? (string) ((int) $paxChildValue) : '';
                                                                $contactChannelFinal = '';
                                                                $requiresContactFields = ! $isTouristAttractionType;
                                                                $hasVendorProvider = trim($vendorProviderFinal) !== '';
                                                                $hasContactDetail = trim((string) $vendorContactDetailValue) !== '';
                                                                $hasContactPerson = trim((string) $contactedPersonValue) !== '';
                                                                $hasServiceType = trim($serviceTypeFinal) !== '';
                                                                $hasServiceDate = trim($serviceDateFinal) !== '';
                                                                $hasPaxAdult = trim($paxAdultFinal) !== '';
                                                                $hasPaxChild = trim($paxChildFinal) !== '';
                                                            @endphp
                                                            @if (! $isTouristAttractionType || $hasVendorProvider)
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                                                        @if($isHotelType)
                                                                            {{ ui_phrase('Hotel Name') }}
                                                                        @elseif($isTouristAttractionType)
                                                                            {{ ui_phrase('Tourist Attraction Name') }}
                                                                        @else
                                                                            {{ ui_phrase('Vendors/Provider') }}
                                                                        @endif
                                                                    </p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasVendorProvider ? $vendorProviderFinal : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            @if (! $isTouristAttractionType || $hasContactDetail)
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Detail') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasContactDetail ? $vendorContactDetailValue : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            @if (! $isTouristAttractionType || $hasContactPerson)
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Person') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasContactPerson ? $contactedPersonValue : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            @if (! $isTouristAttractionType)
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Type') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasServiceType ? $serviceTypeFinal : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            <div>
                                                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Date') }}</p>
                                                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasServiceDate ? $serviceDateReadable : '-' }}</p>
                                                            </div>

                                                            @if ($isTransportLikeType)
                                                                <div class="sm:col-span-2">
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Name') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim((string) $transportServiceName) !== '' ? $transportServiceName : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            @if ($isFoodBeverageType)
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Menu Name') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim((string) $transportServiceName) !== '' ? $transportServiceName : '-' }}</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Meal Period') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim((string) $mealPeriodValue) !== '' ? $mealPeriodValue : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            @if ($isHotelType)
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Room Name') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim((string) $roomNameValue) !== '' ? $roomNameValue : '-' }}</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Room Number') }}</p>
                                                                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ trim((string) $roomNumberValue) !== '' ? $roomNumberValue : '-' }}</p>
                                                                </div>
                                                            @endif

                                                            <div>
                                                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Adult') }}</p>
                                                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasPaxAdult ? $paxAdultFinal : '-' }}</p>
                                                            </div>

                                                            <div>
                                                                <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Child') }}</p>
                                                                <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ $hasPaxChild ? $paxChildFinal : '-' }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="sm:col-span-2 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                                        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ ui_phrase('Required Data') }}</h4>
                                                        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                            @if ($hasVendorProvider)
                                                                <input type="hidden" name="vendor_provider_item_name" value="{{ $vendorProviderFinal }}">
                                                            @else
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">
                                                                        @if($isHotelType)
                                                                            {{ ui_phrase('Hotel Name') }}
                                                                        @elseif($isTouristAttractionType)
                                                                            {{ ui_phrase('Tourist Attraction Name') }}
                                                                        @else
                                                                            {{ ui_phrase('Vendors/Provider') }}
                                                                        @endif
                                                                    </label>
                                                                    <input type="text" name="vendor_provider_item_name" class="app-input mt-1" @if($requiresContactFields) required @endif>
                                                                </div>
                                                            @endif

                                                            <div>
                                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Channel') }}</label>
                                                                <select name="contact_channel" class="app-input mt-1" @if($requiresContactFields) required @endif>
                                                                    <option value="" @if(! $requiresContactFields) selected @endif>{{ ui_phrase('Select one') }}</option>
                                                                    <option value="Email" @if($requiresContactFields && str_contains(strtolower((string) $vendorContactDetailValue), '@')) selected @endif>Email</option>
                                                                    <option value="WhatsApp" @if($requiresContactFields && !str_contains(strtolower((string) $vendorContactDetailValue), '@')) selected @endif>WhatsApp</option>
                                                                    <option value="WeChat">WeChat</option>
                                                                    <option value="Phone">Phone</option>
                                                                    <option value="Other">Other</option>
                                                                </select>
                                                            </div>

                                                            @if ($hasContactDetail)
                                                                <input type="hidden" name="contact_value" value="{{ $vendorContactDetailValue }}">
                                                            @else
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Detail') }}</label>
                                                                    <input type="text" name="contact_value" class="app-input mt-1" @if($requiresContactFields) required @endif>
                                                                </div>
                                                            @endif

                                                            @if ($hasContactPerson)
                                                                <input type="hidden" name="contacted_person_name" value="{{ $contactedPersonValue }}">
                                                            @else
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Person') }}</label>
                                                                    <input type="text" name="contacted_person_name" class="app-input mt-1" @if($requiresContactFields) required @endif>
                                                                </div>
                                                            @endif

                                                            @if ($hasServiceDate)
                                                                <input type="hidden" name="service_date" value="{{ $serviceDateFinal }}">
                                                            @else
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Date') }}</label>
                                                                    <input type="date" name="service_date" class="app-input mt-1" required>
                                                                </div>
                                                            @endif

                                                            <div>
                                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Confirmation Number') }}</label>
                                                                <input type="text" name="confirmation_number" class="app-input mt-1" value="{{ old('confirmation_number') }}">
                                                            </div>

                                                            @if ($hasPaxAdult)
                                                                <input type="hidden" name="pax_adult" value="{{ $paxAdultFinal }}">
                                                            @else
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Adult') }}</label>
                                                                    <input type="number" name="pax_adult" min="0" class="app-input mt-1" required>
                                                                </div>
                                                            @endif

                                                            @if ($hasPaxChild)
                                                                <input type="hidden" name="pax_child" value="{{ $paxChildFinal }}">
                                                            @else
                                                                <div>
                                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Child') }}</label>
                                                                    <input type="number" name="pax_child" min="0" class="app-input mt-1" required>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Notes') }}</label>
                                                        <textarea name="notes" rows="2" class="app-input mt-1"></textarea>
                                                    </div>

                                                    <div class="sm:col-span-2 flex items-center justify-end gap-2">
                                                        <button type="button" class="btn-ghost" x-on:click.prevent="$dispatch('close-modal', 'book-service-modal-{{ $quotationItem->id }}')">{{ ui_phrase('Cancel') }}</button>
                                                        <button type="submit" class="btn-primary">{{ ui_phrase('Booking') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
                                    @elseif ($item && $latestBookingLog)
                                        <x-modal name="edit-book-service-modal-{{ $quotationItem->id }}" focusable maxWidth="2xl">
                                            <div class="p-5">
                                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Edit Booking Service') }}</h3>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $displayDescription }}</p>

                                                <form method="POST" action="{{ route('bookings.services.update', ['booking' => $booking, 'quotationItem' => $quotationItem]) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    @csrf
                                                    @method('PATCH')

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Vendor/Provider') }}</label>
                                                        <input
                                                            type="text"
                                                            name="vendor_provider_item_name"
                                                            value="{{ old('vendor_provider_item_name', $latestBookingLog->vendor_provider_item_name ?? $vendorProviderValue) }}"
                                                            class="app-input mt-1"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Channel') }}</label>
                                                        <select name="contact_channel" class="app-input mt-1">
                                                            <option value="">{{ ui_phrase('Select one') }}</option>
                                                            @php $currentChannel = old('contact_channel', $latestBookingLog->contact_channel ?? ''); @endphp
                                                            <option value="Email" @selected($currentChannel === 'Email')>Email</option>
                                                            <option value="WhatsApp" @selected($currentChannel === 'WhatsApp')>WhatsApp</option>
                                                            <option value="WeChat" @selected($currentChannel === 'WeChat')>WeChat</option>
                                                            <option value="Phone" @selected($currentChannel === 'Phone')>Phone</option>
                                                            <option value="Other" @selected($currentChannel === 'Other')>Other</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Detail') }}</label>
                                                        <input
                                                            type="text"
                                                            name="contact_value"
                                                            value="{{ old('contact_value', $latestBookingLog->contact_value ?? $vendorContactDetailValue) }}"
                                                            class="app-input mt-1"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Person') }}</label>
                                                        <input
                                                            type="text"
                                                            name="contacted_person_name"
                                                            value="{{ old('contacted_person_name', $latestBookingLog->contacted_person_name ?? $contactedPersonValue) }}"
                                                            class="app-input mt-1"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Date') }}</label>
                                                        <input
                                                            type="date"
                                                            name="service_date"
                                                            value="{{ old('service_date', optional($latestBookingLog->service_date)->format('Y-m-d') ?? $serviceDateValue) }}"
                                                            class="app-input mt-1"
                                                            required
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Confirmation Number') }}</label>
                                                        <input
                                                            type="text"
                                                            name="confirmation_number"
                                                            value="{{ old('confirmation_number', $latestBookingLog->confirmation_number ?? '') }}"
                                                            class="app-input mt-1"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Adult') }}</label>
                                                        <input
                                                            type="number"
                                                            name="pax_adult"
                                                            min="0"
                                                            value="{{ old('pax_adult', (int) ($latestBookingLog->pax_adult ?? $paxAdultValue ?? 0)) }}"
                                                            class="app-input mt-1"
                                                            required
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Child') }}</label>
                                                        <input
                                                            type="number"
                                                            name="pax_child"
                                                            min="0"
                                                            value="{{ old('pax_child', (int) ($latestBookingLog->pax_child ?? $paxChildValue ?? 0)) }}"
                                                            class="app-input mt-1"
                                                            required
                                                        >
                                                    </div>

                                                    <div class="sm:col-span-2">
                                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Notes') }}</label>
                                                        <textarea name="notes" rows="2" class="app-input mt-1">{{ old('notes', $latestBookingLog->notes ?? '') }}</textarea>
                                                    </div>

                                                    <div class="sm:col-span-2 flex items-center justify-end gap-2">
                                                        <button type="button" class="btn-ghost" x-on:click.prevent="$dispatch('close-modal', 'edit-book-service-modal-{{ $quotationItem->id }}')">{{ ui_phrase('Cancel') }}</button>
                                                        <button type="submit" class="btn-primary">{{ ui_phrase('Update Booking Service') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </x-modal>
                                    @endif
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
        <div class="p-5">
            <div class="border border-gray-900 text-gray-900 dark:border-gray-200 dark:text-gray-100">
                <div class="border-b border-gray-900 px-3 py-2 text-xs dark:border-gray-200">
                    <p id="voucher-booking-summary">No booking log available.</p>
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
                                <p x-text="voucher.vendorPhone">-</p>
                            </div>
                            <p class="mt-1 font-semibold">{{ ui_phrase('No') }} :</p>
                            <p class="mt-1 text-2xl font-bold leading-tight" x-text="voucher.number">-</p>
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
                        <p class="mt-4">{{ ui_phrase('Confirmed By') }} : {{ $companyName }}</p>
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






