<div class="module-card p-6">
    <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Booking Services') }}</h3>
    @php
        $bookedItemsByQuotationItemId = $booking->items->filter(fn ($item) => !empty($item->quotation_item_id))->keyBy('quotation_item_id');
        $quotationItems = ($booking->quotation?->items ?? collect())
            ->filter(fn ($item) => (string) ($item->itinerary_item_type ?? '') !== 'manual')
            ->values();
        $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
        $activeCurrencyMeta = \App\Support\Currency::meta($activeCurrencyCode);
        $activeCurrencySymbol = trim((string) (is_array($activeCurrencyMeta) ? ($activeCurrencyMeta['symbol'] ?? '') : ''));
        $cancelFeeCurrencyBadge = $activeCurrencySymbol !== '' ? $activeCurrencySymbol : $activeCurrencyCode;
        $totalServiceItems = $quotationItems->count();
        $bookedServiceItems = $quotationItems->filter(fn ($quotationItem) => $bookedItemsByQuotationItemId->has($quotationItem->id))->count();
        $unbookedServiceItems = max(0, $totalServiceItems - $bookedServiceItems);
        $defaultPaxAdult = (int) ($booking->quotation?->pax_adult ?? 0);
        $defaultPaxChild = (int) ($booking->quotation?->pax_child ?? 0);
        $fallbackPolicyRulesMap = is_array($fallbackPolicyRulesMap ?? null) ? $fallbackPolicyRulesMap : [];
    @endphp
    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
        {{ ui_phrase('Items') }}: {{ $totalServiceItems }} |
        {{ ui_phrase('Booked') }}: {{ $bookedServiceItems }} |
        {{ ui_phrase('Unbooked') }}: {{ $unbookedServiceItems }}
    </p>
    <div class="overflow-x-auto">
        <table class="app-table w-full text-sm">
            <thead>
                <tr>
                    <th class="px-3 py-2 text-left">#</th>
                    <th class="px-3 py-2 text-left">{{ ui_phrase('Service Day') }}</th>
                    <th class="px-3 py-2 text-left">{{ ui_phrase('Description') }}</th>
                    <th class="px-3 py-2 text-left">{{ ui_phrase('Status') }}</th>
                    <th class="px-3 py-2 text-left">{{ ui_phrase('Qty') }}</th>
                    <th class="px-3 py-2 text-right">{{ ui_phrase('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($quotationItems as $index => $quotationItem)
                    @php
                        $item = $bookedItemsByQuotationItemId->get($quotationItem->id);
                        $latestBookingLog = $item?->latestBookingLog;
                        $serviceable = $quotationItem->serviceable;
                        $serviceCancellationPolicyText = '';
                        $hotelCancellationPolicyText = '';
                        if (class_basename((string) ($quotationItem->serviceable_type ?? '')) === 'HotelRoom') {
                            $hotelCancellationPolicyText = trim(implode("\n\n", array_filter([
                                trim((string) ($serviceable?->hotel?->cancellation_policy ?? '')),
                                trim((string) ($serviceable?->hotel?->cancellation_policy_traditional ?? '')),
                                trim((string) ($serviceable?->hotel?->cancellation_policy_simplified ?? '')),
                            ])));
                            $serviceCancellationPolicyText = $hotelCancellationPolicyText;
                        } else {
                            $serviceCancellationPolicyText = trim((string) ($serviceable?->cancellation_policy ?? ''));
                        }
                        $serviceProviderName = trim((string) ($serviceable?->name ?? ''));
                        $providerContactName = '';
                        $providerContactEmail = '';
                        $providerContactPhone = '';
                        $providerContactWebsite = '';
                        $hotelName = '';
                        if ($serviceable && method_exists($serviceable, 'vendor') && $serviceable->vendor) {
                            $provider = $serviceable->vendor;
                            $serviceProviderName = trim((string) ($provider->name ?: $serviceProviderName));
                            $providerContactName = trim((string) ($provider->contact_name ?? ''));
                            $providerContactEmail = trim((string) ($provider->contact_email ?? ''));
                            $providerContactPhone = trim((string) ($provider->contact_phone ?? ''));
                            $providerContactWebsite = trim((string) ($provider->website ?? ''));
                        } elseif ($serviceable && method_exists($serviceable, 'hotel') && $serviceable->hotel) {
                            $hotel = $serviceable->hotel;
                            $hotelName = trim((string) ($hotel->name ?? ''));
                            $serviceProviderName = trim((string) ($hotel->name ?: $serviceProviderName));
                            $providerContactName = trim((string) ($hotel->contact_person ?? ''));
                            $providerContactEmail = '';
                            $providerContactPhone = trim((string) ($hotel->phone ?? ''));
                            $providerContactWebsite = trim((string) ($hotel->web ?? ''));
                        }
                        $serviceableMeta = is_array($quotationItem->serviceable_meta ?? null) ? $quotationItem->serviceable_meta : [];
                        $contactOverride = is_array($serviceableMeta['contact_override'] ?? null) ? $serviceableMeta['contact_override'] : [];
                        if ($providerContactName === '') {
                            $providerContactName = trim((string) ($contactOverride['contact_name'] ?? ''));
                        }
                        if ($providerContactPhone === '') {
                            $providerContactPhone = trim((string) ($contactOverride['contact_phone'] ?? ''));
                        }
                        if ($providerContactEmail === '') {
                            $providerContactEmail = trim((string) ($contactOverride['contact_email'] ?? ''));
                        }
                        $baseServiceName = trim((string) ($serviceable?->name ?? $quotationItem->description ?? '-'));
                        if ($baseServiceName === '') {
                            $baseServiceName = '-';
                        }
                        $displayDescription = $baseServiceName;
                        if ($hotelName !== '') {
                            $roomName = trim((string) ($serviceable?->rooms ?? ''));
                            if ($roomName === '') {
                                $roomName = trim((string) ($serviceable?->name ?? $baseServiceName));
                            }
                            if ($roomName !== '') {
                                $roomName = preg_replace('/^\s*Day\s+\d+\s*[-:]\s*/i', '', $roomName) ?? $roomName;
                                $roomName = preg_replace('/^\s*Hotel\s*:\s*/i', '', $roomName) ?? $roomName;
                                if ($hotelName !== '') {
                                    $roomName = preg_replace('/^\s*' . preg_quote($hotelName, '/') . '\s*[-:|]\s*/i', '', $roomName) ?? $roomName;
                                }
                                $roomName = trim((string) $roomName);
                            }
                            $hotelDescriptionParts = array_filter([
                                $hotelName !== '' ? $hotelName : null,
                                $roomName !== '' ? $roomName : null,
                            ], fn ($part) => trim((string) $part) !== '');
                            $hotelDescription = implode(' - ', $hotelDescriptionParts);
                            $displayDescription = trim(ui_phrase('Hotel') . ' : ' . ($hotelDescription !== '' ? $hotelDescription : $baseServiceName));
                        } elseif ($serviceProviderName !== '') {
                            $displayDescription = str_contains(mb_strtolower($baseServiceName), mb_strtolower($serviceProviderName))
                                ? $baseServiceName
                                : trim($baseServiceName . ' | ' . $serviceProviderName);
                        }
                        if ($serviceProviderName === '') {
                            $serviceProviderName = $displayDescription;
                        }
                        $defaultContactChannel = '';
                        $defaultContactValue = '';
                        if ($providerContactEmail !== '') {
                            $defaultContactChannel = 'Email';
                            $defaultContactValue = $providerContactEmail;
                        } elseif ($providerContactPhone !== '') {
                            $defaultContactChannel = 'Phone';
                            $defaultContactValue = $providerContactPhone;
                        } elseif ($providerContactWebsite !== '') {
                            $defaultContactChannel = 'Other';
                            $defaultContactValue = $providerContactWebsite;
                        }
                        $isCancelled = (bool) ($item?->isCancelled() ?? false);
                        $isBooked = $latestBookingLog !== null;
                        $quotationDayNumberRaw = $quotationItem->day_number;
                        $quotationDayNumber = is_numeric($quotationDayNumberRaw) ? (int) $quotationDayNumberRaw : null;
                        if ($quotationDayNumber !== null && $quotationDayNumber > 0) {
                            $serviceDayLabel = trim(ui_phrase('Day') . ' ' . $quotationDayNumber);
                        } else {
                            $serviceDayLabel = '-';
                        }
                        $serviceDate = optional($quotationItem->service_date)->format('Y-m-d');
                        if ($serviceDate === null && $booking->travel_date && $quotationDayNumber !== null && $quotationDayNumber > 0) {
                            $serviceDate = $booking->travel_date->copy()->addDays($quotationDayNumber - 1)->format('Y-m-d');
                        }
                        if ($serviceDate === null) {
                            $serviceDate = optional($latestBookingLog?->service_date)->format('Y-m-d') ?? '-';
                        }
                        $qty = $isBooked ? ((int) ($item->qty ?? 0)) : ((int) ($quotationItem->qty ?? 0));
                        $tourName = trim((string) ($item?->voucher?->tour_name ?? ''));
                        if ($tourName === '') {
                            $tourName = trim((string) ($booking->quotation?->order_number ?? '') . ' - ' . (string) ($booking->quotation?->inquiry?->customer?->company_name ?: $booking->quotation?->inquiry?->customer?->name ?: ''));
                        }
                        $voucherServiceDate = optional($latestBookingLog?->service_date)->format('Y-m-d')
                            ?? optional($item?->voucher?->service_date)->format('Y-m-d')
                            ?? $serviceDate;
                        $voucherToName = trim((string) ($latestBookingLog?->vendor_provider_item_name ?? $item?->voucher?->vendor_contact_name ?? $displayDescription));
                        $voucherToContact = trim(implode(' | ', array_filter([
                            trim((string) ($latestBookingLog?->contact_channel ?? '')),
                            trim((string) ($latestBookingLog?->contact_value ?? '')),
                        ])));
                        if ($voucherToContact === '') {
                            $voucherToContact = trim((string) ($item?->voucher?->vendor_contact_phone ?? '-'));
                        }
                        $bookingAtValue = optional($latestBookingLog?->booked_at)->format('Y-m-d (H:i)') ?? '-';
                        $bookingChannelValue = trim((string) ($latestBookingLog?->contact_channel ?? '')) ?: '-';
                        $bookingContactedValue = trim((string) ($latestBookingLog?->contacted_person_name ?? '')) ?: '-';
                        $bookingContactDetailValue = trim((string) ($latestBookingLog?->contact_value ?? '')) ?: '-';
                        $prefillCancellationFeeType = 'nominal';
                        $prefillCancellationFeeValue = 0.0;
                        $activeCurrency = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
                        $snapshotRules = collect($item?->cancellation_policy_snapshot['rules'] ?? [])->values();
                        if ($snapshotRules->isEmpty()) {
                            $policyType = (string) ($quotationItem->serviceable_type ?? '');
                            $policyId = (int) ($quotationItem->serviceable_id ?? 0);
                            if ($policyType === \App\Models\HotelRoom::class || class_basename($policyType) === 'HotelRoom') {
                                $hotelId = (int) ($quotationItem->serviceable?->hotel?->id ?? 0);
                                if ($hotelId > 0) {
                                    $policyType = (new \App\Models\Hotel())->getMorphClass();
                                    $policyId = $hotelId;
                                }
                            }
                            if ($policyType !== '' && $policyId > 0) {
                                $policyMapKey = $policyType . '#' . $policyId;
                                $snapshotRules = collect($fallbackPolicyRulesMap[$policyMapKey] ?? [])->values();
                            }
                        }
                        if ($snapshotRules->isNotEmpty()) {
                            $serviceDateForRule = $latestBookingLog?->service_date;
                            $daysBefore = null;
                            if ($serviceDateForRule) {
                                $serviceDateCarbon = $serviceDateForRule instanceof \Illuminate\Support\Carbon
                                    ? $serviceDateForRule->copy()->startOfDay()
                                    : \Illuminate\Support\Carbon::parse((string) $serviceDateForRule)->startOfDay();
                                $daysBefore = now()->startOfDay()->diffInDays($serviceDateCarbon, false);
                            }
                            $matchedRule = $snapshotRules->first(function (array $rule) use ($daysBefore) {
                                if ($daysBefore === null) {
                                    return false;
                                }
                                $min = array_key_exists('min_days_before', $rule) && $rule['min_days_before'] !== null ? (int) $rule['min_days_before'] : null;
                                $max = array_key_exists('max_days_before', $rule) && $rule['max_days_before'] !== null ? (int) $rule['max_days_before'] : null;
                                if ($min !== null && $daysBefore < $min) {
                                    return false;
                                }
                                if ($max !== null && $daysBefore > $max) {
                                    return false;
                                }
                                return true;
                            });
                            if (! is_array($matchedRule)) {
                                $matchedRule = $snapshotRules->first();
                            }
                            if (is_array($matchedRule)) {
                                $matchedType = strtolower(trim((string) ($matchedRule['fee_type'] ?? 'fixed')));
                                $matchedValue = max(0, (float) ($matchedRule['fee_value'] ?? 0));
                                if ($matchedType === 'percent') {
                                    $prefillCancellationFeeType = 'percent';
                                    $prefillCancellationFeeValue = $matchedValue;
                                } else {
                                    $prefillCancellationFeeType = 'nominal';
                                    $prefillCancellationFeeValue = (float) \App\Support\Currency::convert($matchedValue, 'IDR', $activeCurrency);
                                }
                            }
                        }
                        $hasCancellationPolicyReference = $snapshotRules->isNotEmpty() || $serviceCancellationPolicyText !== '';
                    @endphp
                    <tr>
                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">
                            <div class="leading-tight">
                                <div class="font-semibold">{{ $serviceDayLabel }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $serviceDate }}</div>
                            </div>
                        </td>
                        <td class="px-3 py-2">{{ $displayDescription }}</td>
                        <td class="px-3 py-2">
                            @if ($isCancelled)
                                <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700">{{ ui_phrase('Cancelled') }}</span>
                            @elseif ($isBooked)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">{{ ui_phrase('Booked') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">{{ ui_phrase('Unbooked') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $qty }}</td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($isBooked && $item && ! $isCancelled && $item->voucher)
                                    @if (($sourceUpdatedMap[$item->id] ?? false) === true)
                                        <form action="{{ route('booking-items.voucher.generate', $item) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-outline-sm" title="{{ ui_phrase('Regenerate Voucher') }}" aria-label="{{ ui_phrase('Regenerate Voucher') }}">
                                                <i class="fa-solid fa-rotate"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button
                                        type="button"
                                        class="btn-secondary-sm"
                                        title="{{ ui_phrase('View Voucher') }}"
                                        x-on:click.prevent="openVoucherModal($el)"
                                        data-voucher-number="{{ $item->voucher->voucher_number }}"
                                        data-voucher-item="{{ $displayDescription }}"
                                        data-booking-summary="{{ ui_phrase(':service was booked by :user on :datetime.', ['service' => $voucherToName, 'user' => ($latestBookingLog?->creator?->name ?? 'Unknown user'), 'datetime' => (optional($latestBookingLog?->created_at)->format('Y-m-d (H:i)') ?? '-')]) }}"
                                        data-booking-at="{{ $bookingAtValue }}"
                                        data-booking-channel="{{ $bookingChannelValue }}"
                                        data-booking-contacted="{{ $bookingContactedValue }}"
                                        data-booking-contact-detail="{{ $bookingContactDetailValue }}"
                                        data-voucher-tour-name="{{ $tourName !== '' ? $tourName : '-' }}"
                                        data-voucher-qty="{{ $latestBookingLog ? ((int) ($latestBookingLog->pax_adult ?? 0) + (int) ($latestBookingLog->pax_child ?? 0)) : $qty }}"
                                        data-voucher-service-date="{{ $voucherServiceDate }}"
                                        data-voucher-vendor-name="{{ $voucherToName }}"
                                        data-voucher-to-location="-"
                                        data-voucher-to-contact="{{ $voucherToContact }}"
                                        data-voucher-confirmation="{{ trim((string) ($latestBookingLog?->confirmation_number ?? $item->voucher->confirmation_code ?? '-')) }}"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <a href="{{ route('booking-items.voucher.pdf', $item) }}" target="_blank" rel="noopener" class="btn-outline-sm" title="{{ ui_phrase('Preview Voucher PDF') }}">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <button type="button" class="btn-outline-sm" title="{{ ui_phrase('Edit Booking Service') }}" x-on:click.prevent="$dispatch('open-modal', 'edit-book-service-modal-edit-{{ $quotationItem->id }}')">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" class="btn-outline-sm border-rose-300 text-rose-700" title="{{ ui_phrase('Cancel Item') }}" x-on:click.prevent="$dispatch('open-modal', 'cancel-book-service-modal-edit-{{ $quotationItem->id }}')">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                @elseif ($isBooked && $item)
                                    @if ($isCancelled)
                                        <span class="text-xs text-rose-600">{{ ui_phrase('Cancellation Fee') }}: {{ \App\Support\Currency::format((float) ($item->cancellation_fee ?? 0), 'IDR') }}</span>
                                        <button type="button" class="btn-outline-sm" title="{{ ui_phrase('Edit Booking Service') }}" x-on:click.prevent="$dispatch('open-modal', 'edit-book-service-modal-edit-{{ $quotationItem->id }}')">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">{{ ui_phrase('Voucher is being prepared.') }}</span>
                                    @endif
                                @else
                                    <button type="button" class="btn-secondary-sm" title="{{ ui_phrase('Book') }}" x-on:click.prevent="$dispatch('open-modal', 'book-service-modal-edit-{{ $quotationItem->id }}')">
                                        <i class="fa-solid fa-cart-plus"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>

                    @if (! $isBooked)
                        <x-modal name="book-service-modal-edit-{{ $quotationItem->id }}" focusable maxWidth="2xl">
                            <div class="p-5">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Book Service Item') }}</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $displayDescription }}</p>
                                <form method="POST" action="{{ route('bookings.services.book', ['booking' => $booking, 'quotationItem' => $quotationItem]) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Vendors/Provider') }}</label>
                                        <input type="text" name="vendor_provider_item_name" value="{{ old('vendor_provider_item_name', $serviceProviderName) }}" class="app-input mt-1" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Channel') }}</label>
                                        @php $defaultBookChannel = old('contact_channel', $defaultContactChannel); @endphp
                                        <select name="contact_channel" class="app-input mt-1" required>
                                            <option value="">{{ ui_phrase('Select one') }}</option>
                                            <option value="Email" @selected($defaultBookChannel==='Email')>Email</option>
                                            <option value="WhatsApp" @selected($defaultBookChannel==='WhatsApp')>WhatsApp</option>
                                            <option value="WeChat" @selected($defaultBookChannel==='WeChat')>WeChat</option>
                                            <option value="Phone" @selected($defaultBookChannel==='Phone')>Phone</option>
                                            <option value="Other" @selected($defaultBookChannel==='Other')>Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Detail') }}</label>
                                        <input type="text" name="contact_value" value="{{ old('contact_value', $defaultContactValue) }}" class="app-input mt-1" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Person') }}</label>
                                        <input type="text" name="contacted_person_name" value="{{ old('contacted_person_name', $providerContactName) }}" class="app-input mt-1" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Date') }}</label>
                                        <input type="date" name="service_date" value="{{ $serviceDate !== '-' ? $serviceDate : '' }}" class="app-input mt-1" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Confirmation Number') }}</label>
                                        <input type="text" name="confirmation_number" class="app-input mt-1">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Adult') }}</label>
                                        <input type="number" name="pax_adult" min="0" value="{{ $defaultPaxAdult }}" class="app-input mt-1" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Child') }}</label>
                                        <input type="number" name="pax_child" min="0" value="{{ $defaultPaxChild }}" class="app-input mt-1" required>
                                    </div>
                                    <div class="sm:col-span-2 flex justify-end gap-2">
                                        <button type="button" class="btn-ghost" x-on:click.prevent="$dispatch('close-modal', 'book-service-modal-edit-{{ $quotationItem->id }}')">{{ ui_phrase('Cancel') }}</button>
                                        <button type="submit" class="btn-primary">{{ ui_phrase('Booking') }}</button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
                    @elseif ($item && $latestBookingLog)
                        <x-modal name="edit-book-service-modal-edit-{{ $quotationItem->id }}" focusable maxWidth="2xl">
                            <div class="p-5">
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Edit Booking Service') }}</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $displayDescription }}</p>
                                <form method="POST" action="{{ route('bookings.services.update', ['booking' => $booking, 'quotationItem' => $quotationItem]) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @csrf
                                    @method('PATCH')
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Vendors/Provider') }}</label><input type="text" name="vendor_provider_item_name" value="{{ old('vendor_provider_item_name', $latestBookingLog->vendor_provider_item_name ?? '') }}" class="app-input mt-1" required></div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Channel') }}</label>
                                        @php $channel = old('contact_channel', $latestBookingLog->contact_channel ?? ''); @endphp
                                        <select name="contact_channel" class="app-input mt-1" required>
                                            <option value="">{{ ui_phrase('Select one') }}</option>
                                            <option value="Email" @selected($channel==='Email')>Email</option>
                                            <option value="WhatsApp" @selected($channel==='WhatsApp')>WhatsApp</option>
                                            <option value="WeChat" @selected($channel==='WeChat')>WeChat</option>
                                            <option value="Phone" @selected($channel==='Phone')>Phone</option>
                                            <option value="Other" @selected($channel==='Other')>Other</option>
                                        </select>
                                    </div>
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Detail') }}</label><input type="text" name="contact_value" value="{{ old('contact_value', $latestBookingLog->contact_value ?? '') }}" class="app-input mt-1" required></div>
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Contact Person') }}</label><input type="text" name="contacted_person_name" value="{{ old('contacted_person_name', $latestBookingLog->contacted_person_name ?? '') }}" class="app-input mt-1" required></div>
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Date') }}</label><input type="date" name="service_date" value="{{ old('service_date', optional($latestBookingLog->service_date)->format('Y-m-d')) }}" class="app-input mt-1" required></div>
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Confirmation Number') }}</label><input type="text" name="confirmation_number" value="{{ old('confirmation_number', $latestBookingLog->confirmation_number ?? '') }}" class="app-input mt-1"></div>
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Adult') }}</label><input type="number" name="pax_adult" min="0" value="{{ old('pax_adult', (int) ($latestBookingLog->pax_adult ?? 0)) }}" class="app-input mt-1" required></div>
                                    <div><label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Pax Child') }}</label><input type="number" name="pax_child" min="0" value="{{ old('pax_child', (int) ($latestBookingLog->pax_child ?? 0)) }}" class="app-input mt-1" required></div>
                                    <div class="sm:col-span-2 flex justify-end gap-2">
                                        <button type="button" class="btn-ghost" x-on:click.prevent="$dispatch('close-modal', 'edit-book-service-modal-edit-{{ $quotationItem->id }}')">{{ ui_phrase('Cancel') }}</button>
                                        <button type="submit" class="btn-primary">{{ ui_phrase('Update Booking Service') }}</button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
                        @if (! $isCancelled)
                            <x-modal name="cancel-book-service-modal-edit-{{ $quotationItem->id }}" focusable maxWidth="2xl">
                                <div class="p-5">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Cancel Item') }}</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $displayDescription }}</p>
                                    <form method="POST" action="{{ route('bookings.services.cancel', ['booking' => $booking, 'quotationItem' => $quotationItem]) }}" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="space-y-3">
                                            @if ($serviceCancellationPolicyText !== '')
                                                <div class="rounded border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200">
                                                    <p class="font-semibold">{{ ui_phrase('Cancellation Policy') }}</p>
                                                    <div class="mt-1 space-y-1 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-1">
                                                        {!! $serviceCancellationPolicyText !!}
                                                    </div>
                                                </div>
                                            @endif
                                            @if (! $hasCancellationPolicyReference)
                                                <div class="rounded border border-blue-200 bg-blue-50 p-3 text-xs text-blue-800 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-200">
                                                    {{ ui_phrase('Cancellation policy is not available for this service item. Please input cancellation fee manually. The value will be saved as default policy for this service item.') }}
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Cancellation Policy') }}</label>
                                                    <textarea name="cancellation_policy_text" rows="6" class="app-input mt-1" data-wysiwyg="true">{{ old('cancellation_policy_text') }}</textarea>
                                                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">{{ ui_phrase('This policy will be saved to the related service item.') }}</p>
                                                </div>
                                            @endif
                                        </div>
                                        <div
                                            class="space-y-3"
                                            x-data="{
                                                feeType: @js(old('cancellation_fee_type', $prefillCancellationFeeType)),
                                                currencyBadge: @js($cancelFeeCurrencyBadge),
                                                baseLabel: @js(ui_phrase('Cancellation Fee')),
                                                get isPercent() {
                                                    return String(this.feeType || '').toLowerCase() === 'percent';
                                                }
                                            }"
                                        >
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">{{ ui_phrase('Type') }}</label>
                                                <select
                                                    name="cancellation_fee_type"
                                                    class="app-input mt-1"
                                                    required
                                                    x-model="feeType"
                                                    data-cancel-fee-type
                                                    data-item-id="{{ $quotationItem->id }}"
                                                    data-currency-badge="{{ $cancelFeeCurrencyBadge }}"
                                                >
                                                    <option value="nominal" @selected($prefillCancellationFeeType==='nominal')>{{ ui_phrase('Fixed') }}</option>
                                                    <option value="percent" @selected($prefillCancellationFeeType==='percent')>{{ ui_phrase('Percent') }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label
                                                    id="cancel-fee-label-{{ $quotationItem->id }}"
                                                    data-cancel-fee-label
                                                    class="block text-xs font-semibold text-gray-600 dark:text-gray-300"
                                                    x-text="isPercent ? `${baseLabel} (%)` : baseLabel"
                                                >{{ old('cancellation_fee_type', $prefillCancellationFeeType) === 'percent' ? ui_phrase('Cancellation Fee') . ' (%)' : ui_phrase('Cancellation Fee') }}</label>
                                                <div class="relative">
                                                    <span
                                                        id="cancel-fee-badge-{{ $quotationItem->id }}"
                                                        class="input-left-affix pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                                        data-cancel-fee-badge
                                                        data-currency-badge="{{ $cancelFeeCurrencyBadge }}"
                                                        x-text="isPercent ? '%' : currencyBadge"
                                                    >{{ old('cancellation_fee_type', $prefillCancellationFeeType) === 'percent' ? '%' : $cancelFeeCurrencyBadge }}</span>
                                                    <input
                                                        type="number"
                                                        name="cancellation_fee"
                                                        min="0"
                                                        step="0.01"
                                                        value="{{ old('cancellation_fee', $prefillCancellationFeeValue) }}"
                                                        class="app-input mt-1 pl-14 pr-3 text-right"
                                                        inputmode="decimal"
                                                        data-money-skip="1"
                                                        required
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-end gap-2 lg:col-span-2">
                                            <button type="button" class="btn-ghost" x-on:click.prevent="$dispatch('close-modal', 'cancel-book-service-modal-edit-{{ $quotationItem->id }}')">{{ ui_phrase('Close') }}</button>
                                            <button type="submit" class="btn-primary">{{ ui_phrase('Save') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </x-modal>
                        @endif
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-3 text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Items')]) }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const baseLabel = @json(ui_phrase('Cancellation Fee'));
                const syncCancellationField = (select) => {
                    const itemId = select?.dataset?.itemId;
                    if (!itemId) return;
                    const badge = document.getElementById(`cancel-fee-badge-${itemId}`);
                    const label = document.getElementById(`cancel-fee-label-${itemId}`);
                    const currency = select.dataset.currencyBadge || 'IDR';
                    const isPercent = String(select.value || '').toLowerCase() === 'percent';
                    if (badge) badge.textContent = isPercent ? '%' : currency;
                    if (label) label.textContent = isPercent ? `${baseLabel} (%)` : baseLabel;
                };

                const syncCancellationFeeBadges = () => {
                    document.querySelectorAll('select[data-cancel-fee-type]').forEach((select) => {
                        syncCancellationField(select);
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', syncCancellationFeeBadges);
                } else {
                    syncCancellationFeeBadges();
                }

                document.addEventListener('open-modal', () => {
                    setTimeout(syncCancellationFeeBadges, 0);
                });

                document.addEventListener('change', (event) => {
                    const target = event.target;
                    if (target && target.matches('select[data-cancel-fee-type]')) {
                        syncCancellationField(target);
                    }
                });

                document.addEventListener('input', (event) => {
                    const target = event.target;
                    if (target && target.matches('select[data-cancel-fee-type]')) {
                        syncCancellationField(target);
                    }
                });
            })();
        </script>
    @endpush
@endonce
