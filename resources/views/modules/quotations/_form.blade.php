@php
    $buttonLabel = $buttonLabel ?? ui_phrase('Save');
    $itineraries = $itineraries ?? collect();
    $inquiries = $inquiries ?? collect();
    $customers = $customers ?? collect();
    $destinations = $destinations ?? collect();
    $serviceCatalogs = $serviceCatalogs ?? [];
    $prefillItineraryId = $prefillItineraryId ?? null;
    $prefillInquiryId = $prefillInquiryId ?? null;
    $isEditQuotation = isset($quotation) && $quotation instanceof \App\Models\Quotation;
    $isRevisionMode = (bool) ($isRevisionMode ?? false);
    $selectedItineraryIdForDuration = (string) old('itinerary_id', $quotation->itinerary_id ?? $prefillItineraryId ?? '');
    $selectedItineraryForDuration = $itineraries->firstWhere('id', (int) $selectedItineraryIdForDuration);
    $initialDurationDays = 1;
    $initialDurationNights = 0;
    if ($selectedItineraryForDuration) {
        $durationDaysValue = max(1, (int) ($selectedItineraryForDuration->duration_days ?? 1));
        $durationNightsValue = max(0, (int) ($selectedItineraryForDuration->duration_nights ?? max(0, $durationDaysValue - 1)));
        $initialDurationDays = $durationDaysValue;
        $initialDurationNights = $durationNightsValue;
    }
    $initialDurationDays = (int) old('duration_days', $initialDurationDays);
    $initialDurationDays = max(1, $initialDurationDays);
    $initialDurationNights = (int) old('duration_nights', max(0, $initialDurationDays - 1));
    $initialDurationNights = max(0, $initialDurationNights);
    $selectedDestinationId = old('destination_id');
    if ($selectedDestinationId === null || $selectedDestinationId === '') {
        $selectedDestinationId = $selectedItineraryForDuration?->destination_id ?? '';
    }
    $selectedCustomerId = old('customer_id');
    $prefillInquiryCustomerId = '';
    if ((string) ($prefillInquiryId ?? '') !== '') {
        $prefillInquiryMatch = $inquiries->firstWhere('id', (int) $prefillInquiryId);
        $prefillInquiryCustomerId = (string) ($prefillInquiryMatch->customer_id ?? '');
    }
    if ($selectedCustomerId === null || $selectedCustomerId === '') {
        $selectedCustomerId = $quotation->inquiry?->customer_id
            ?? $prefillInquiryCustomerId
            ?? '';
    }
    $selectedInquiryId = old('inquiry_id');
    if ($selectedInquiryId === null || $selectedInquiryId === '') {
        $selectedInquiryId = $quotation->inquiry_id
            ?? $prefillInquiryId
            ?? '';
    }
    $isPrefillInquiryLocked = ! $isEditQuotation && (string) ($prefillInquiryId ?? '') !== '';
@endphp

@php
    $transportServiceableType = \App\Models\TransportUnit::class;
    $hotelRoomServiceableType = \App\Models\HotelRoom::class;
    $transportRateLookup = collect();
    $hotelPriceLookup = collect();

    if (isset($quotation) && $quotation->relationLoaded('items')) {
        $transportIds = $quotation->items
            ->where('serviceable_type', $transportServiceableType)
            ->pluck('serviceable_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($transportIds->isNotEmpty()) {
            $transportRateLookup = \App\Models\TransportUnit::query()
                ->whereIn('id', $transportIds->all())
                ->get(['id', 'contract_rate', 'publish_rate', 'markup_type', 'markup'])
                ->keyBy('id');
        }

        $hotelRoomIds = $quotation->items
            ->where('serviceable_type', $hotelRoomServiceableType)
            ->pluck('serviceable_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($hotelRoomIds->isNotEmpty()) {
            $hotelPriceLookup = \App\Models\HotelPrice::query()
                ->whereIn('rooms_id', $hotelRoomIds->all())
                ->orderByDesc('end_date')
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(['id', 'rooms_id', 'contract_rate', 'markup_type', 'markup', 'publish_rate'])
                ->groupBy('rooms_id')
                ->map(fn ($rows) => $rows->first());
        }
    }
@endphp

@php
    $allItems = old('items', isset($quotation) ? $quotation->items->map(function ($item) use ($transportRateLookup, $hotelPriceLookup) {
        $contractRate = (float) ($item->contract_rate ?? 0);
        $markupType = ($item->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
        $markup = (float) ($item->markup ?? 0);
        $unitPrice = (float) ($item->unit_price ?? 0);
        $serviceableMeta = is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : [];
        $isSelfBookedHotel = ($serviceableMeta['end_hotel_booking_mode'] ?? null) === 'self';
        $hasStoredMarkupType = in_array(($item->markup_type ?? null), ['fixed', 'percent'], true);
        $hasStoredMarkup = $item->markup !== null && $item->markup !== '';

        if ($contractRate <= 0 && ($item->serviceable_type ?? null) === \App\Models\TransportUnit::class) {
            $source = $transportRateLookup->get((int) ($item->serviceable_id ?? 0));
            if ($source) {
                $sourceContract = (float) ($source->contract_rate ?? 0);
                $sourcePublish = (float) ($source->publish_rate ?? 0);
                $contractRate = $sourceContract > 0 ? $sourceContract : ($sourcePublish > 0 ? $sourcePublish : $contractRate);

                if (! $hasStoredMarkup) {
                    $markup = max(0, $sourcePublish - $contractRate);
                }
                if (! $hasStoredMarkupType) {
                    $markupType = ($source->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
                }

                if ($unitPrice <= 0) {
                    $unitPrice = $sourcePublish > 0
                        ? $sourcePublish
                        : ($markupType === 'percent'
                            ? ($contractRate + ($contractRate * ($markup / 100)))
                            : ($contractRate + $markup));
                }
            }
        }

        if (! $isSelfBookedHotel && $contractRate <= 0 && ($item->serviceable_type ?? null) === \App\Models\HotelRoom::class) {
            $source = $hotelPriceLookup->get((int) ($item->serviceable_id ?? 0));
            if ($source) {
                $sourceContract = (float) ($source->contract_rate ?? 0);
                $sourceMarkupType = ($source->markup_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
                $sourceMarkup = (float) ($source->markup ?? 0);
                $sourcePublish = (float) ($source->publish_rate ?? 0);

                $contractRate = $sourceContract > 0 ? $sourceContract : ($sourcePublish > 0 ? $sourcePublish : $contractRate);
                if (! $hasStoredMarkup) {
                    $markup = $sourceMarkup;
                }
                if (! $hasStoredMarkupType) {
                    $markupType = $sourceMarkupType;
                }
                if ($unitPrice <= 0) {
                    $unitPrice = $sourcePublish > 0
                        ? $sourcePublish
                        : ($markupType === 'percent'
                            ? ($contractRate + ($contractRate * ($markup / 100)))
                            : ($contractRate + $markup));
                }
            }
        }

        if ($contractRate <= 0 && $unitPrice > 0) {
            $contractRate = $unitPrice;
        }

        return [
            'id' => $item->id,
            'source_item_id' => $item->id,
            'description' => $item->description,
            'qty' => $item->qty,
            'contract_rate' => $contractRate,
            'markup_type' => $markupType,
            'markup' => $markup,
            'unit_price' => $unitPrice,
            'discount_type' => $item->discount_type ?? 'fixed',
            'discount' => $item->discount,
            'serviceable_type' => $item->serviceable_type,
            'serviceable_id' => $item->serviceable_id,
            'day_number' => $item->day_number,
            'sort_order' => $item->sort_order,
            'serviceable_meta' => $serviceableMeta,
            'itinerary_item_type' => $item->itinerary_item_type,
        ];
    })->toArray() : []);
    $allItems = array_values($allItems);
    usort($allItems, function (array $left, array $right): int {
        $leftSortOrder = (int) ($left['sort_order'] ?? 0);
        $rightSortOrder = (int) ($right['sort_order'] ?? 0);

        if ($leftSortOrder > 0 && $rightSortOrder > 0 && $leftSortOrder !== $rightSortOrder) {
            return $leftSortOrder <=> $rightSortOrder;
        }

        return (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
    });
    $manualItems = collect($allItems)
        ->filter(fn ($row) => ($row['itinerary_item_type'] ?? '') === 'manual')
        ->values()
        ->all();
    $items = collect($allItems)
        ->reject(fn ($row) => ($row['itinerary_item_type'] ?? '') === 'manual')
        ->values()
        ->all();
    $minRows = 0;
    $hasItems = collect($items)
        ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
        ->isNotEmpty();

    $storedSubTotal = (float) old('sub_total', $quotation->sub_total ?? 0);
    $storedDiscountType = (string) old('discount_type', $quotation->discount_type ?? '');
    $storedDiscountValue = (float) old('discount_value', $quotation->discount_value ?? 0);
    $storedGlobalDiscountAmount = 0.0;
    if ($storedDiscountType === 'percent') {
        $storedGlobalDiscountAmount = $storedSubTotal * ($storedDiscountValue / 100);
    } elseif ($storedDiscountType === 'fixed') {
        $storedGlobalDiscountAmount = $storedDiscountValue;
    }

    $storedItemDiscountTotal = 0.0;
    if (isset($quotation) && $quotation->relationLoaded('items')) {
        $storedItemDiscountTotal = (float) $quotation->items->sum(function ($item) {
            $qty = (int) ($item->qty ?? 0);
            $unitPrice = (float) ($item->unit_price ?? 0);
            $discountType = ($item->discount_type ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
            $discountValue = (float) ($item->discount ?? 0);
            if ($discountType === 'percent') {
                return ($qty * $unitPrice) * ($discountValue / 100);
            }

            return $discountValue;
        });
    }
@endphp

<div class="space-y-5 module-form quotation-form-no-labels">
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300">
            <p class="font-semibold">{{ ui_phrase('Failed to save quotation. Please review the following data:') }}</p>
            <ul class="mt-2 list-disc pl-5 space-y-1 text-xs sm:text-sm">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($isRevisionMode && ! ($isEditQuotation && $quotation->isLockedForDirectEdit()))
        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 dark:border-sky-800 dark:bg-sky-900/20">
            <p class="text-sm font-semibold text-sky-800 dark:text-sky-200">{{ ui_phrase('Revision mode') }}</p>
            <p class="mt-1 text-xs text-sky-700 dark:text-sky-300">{{ ui_phrase('Update requested changes, adjust service items, then save the revision so the quotation can continue to revalidation or ready to send.') }}</p>
        </div>
    @endif

    @if ($isEditQuotation && $quotation->isLockedForDirectEdit())
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-900/20">
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">{{ ui_phrase('Locked quotation will be revised') }}</p>
            <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">{{ ui_phrase('Saving changes will create a new quotation revision and keep the current quotation unchanged.') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Destination') }}</label>
            <select
                id="destination-select"
                name="destination_id"
                class="mt-1 app-input"
            >
                <option value="">{{ ui_phrase('All destinations') }}</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}" @selected((string) $selectedDestinationId === (string) $destination->id)>
                        {{ $destination->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-8">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Itinerary') }} <span class="text-xs text-gray-500">{{ ui_phrase('(Optional)') }}</span></label>
            <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
                <select
                    id="itinerary-select"
                    name="itinerary_id"
                    class="app-input"
                    data-endpoint="{{ url('quotations/itinerary-items') }}"
                >
                    <option value="">{{ ui_phrase('Select itinerary (optional)') }}</option>
                    @foreach ($itineraries as $itinerary)
                        <option
                            value="{{ $itinerary->id }}"
                            data-destination-id="{{ $itinerary->destination_id ?? '' }}"
                            data-inquiry-id="{{ $itinerary->reference_inquiry_id ?? '' }}"
                            data-inquiry-number="{{ $itinerary->reference_inquiry_number ?? '' }}"
                            data-customer-id="{{ $itinerary->reference_customer_id ?? '' }}"
                            data-duration-days="{{ (int) ($itinerary->duration_days ?? 1) }}"
                            data-duration-nights="{{ (int) ($itinerary->duration_nights ?? max(0, ((int) ($itinerary->duration_days ?? 1)) - 1)) }}"
                            @selected((string) old('itinerary_id', $quotation->itinerary_id ?? $prefillItineraryId ?? '') === (string) $itinerary->id)
                        >
                            {{ $itinerary->title }}
                        </option>
                    @endforeach
                </select>
                <button
                    type="button"
                    id="itinerary-generate-btn"
                    class="btn-outline-sm min-h-[42px] w-full sm:w-auto"
                >
                    {{ ui_phrase('Generate') }}
                </button>
            </div>
            <p id="itinerary-generate-status" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ ui_phrase('Use Generate to fill items from the selected itinerary.') }}
            </p>
            @error('itinerary_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
            @if ($itineraries->isEmpty())
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    {{ ui_phrase('No active itinerary is ready to use for this quotation yet. Please create or activate an itinerary first.') }}
                </p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Inquiry') }} <span class="text-rose-600">*</span></label>
            @if ($isEditQuotation)
                <input type="hidden" name="inquiry_id" value="{{ $selectedInquiryId }}">
            @endif
            <select
                id="inquiry-select"
                name="{{ $isEditQuotation ? '' : 'inquiry_id' }}"
                class="mt-1 app-input"
                data-prefill-lock="{{ $isPrefillInquiryLocked ? '1' : '0' }}"
                data-prefill-id="{{ (string) ($prefillInquiryId ?? '') }}"
                @disabled($isEditQuotation)
                required
            >
                <option value="">{{ ui_phrase('Select Inquiry (required)') }}</option>
                @foreach ($inquiries as $inquiry)
                    @php
                        $customerCode = strtoupper(trim((string) ($inquiry->customer?->code ?? '')));
                        if ($customerCode === '') {
                            $customerCode = strtoupper(trim((string) ($inquiry->customer?->name ?? '')));
                        }
                        if ($customerCode === '') {
                            $customerCode = '-';
                        }
                        $inquiryNumber = trim((string) ($inquiry->inquiry_number ?? ''));
                        if ($inquiryNumber === '') {
                            $inquiryNumber = '#' . $inquiry->id;
                        }
                        $inquiryDeadline = $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-';
                        $quotationCount = (int) ($inquiry->quotation_count ?? 0);
                        $inquiryLabel = $customerCode . ' | ' . $inquiryNumber . ' - ' . $inquiryDeadline . ' | ' . ui_phrase('Quotation') . ': ' . $quotationCount;
                    @endphp
                    <option
                        value="{{ $inquiry->id }}"
                        data-customer-id="{{ $inquiry->customer_id ?? '' }}"
                        @selected((string) $selectedInquiryId === (string) $inquiry->id)
                    >
                        {{ $inquiryLabel }}
                    </option>
                @endforeach
            </select>
            @error('inquiry_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Customer/Agent') }} <span class="text-rose-600">*</span></label>
            <select
                id="customer-agent-select"
                name="customer_id"
                class="mt-1 app-input"
                required
            >
                <option value="">{{ ui_phrase('Select Customer/Agent') }}</option>
                @foreach ($customers as $customer)
                    @php
                        $customerName = trim((string) ($customer->name ?? ''));
                        $customerCompany = trim((string) ($customer->company_name ?? ''));
                        $customerLabel = $customerName !== '' ? $customerName : '-';
                        if ($customerName !== '' && $customerCompany !== '') {
                            $customerLabel = $customerName . ' | ' . $customerCompany;
                        }
                    @endphp
                    <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>
                        {{ $customerLabel }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Order Number') }} <span class="text-rose-600">*</span></label>
            <input
                id="quotation-order-number"
                name="order_number"
                value="{{ old('order_number', $quotation->order_number ?? '') }}"
                class="mt-1 app-input"
                placeholder="{{ ui_phrase('Example: ABC260423A') }}"
                required
            >
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ ui_phrase('Use alphanumeric format without spaces.') }}
            </p>
            @error('order_number')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Pax Adult') }} <span class="text-rose-600">*</span></label>
            <input
                name="pax_adult"
                type="number"
                min="0"
                value="{{ old('pax_adult', isset($quotation->pax_adult) ? (int) $quotation->pax_adult : 0) }}"
                class="mt-1 app-input"
                required
            >
            @error('pax_adult')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Pax Child') }}</label>
            <input
                name="pax_child"
                type="number"
                min="0"
                value="{{ old('pax_child', isset($quotation->pax_child) ? (int) $quotation->pax_child : 0) }}"
                class="mt-1 app-input"
            >
            @error('pax_child')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Service Date') }} <span class="text-rose-600">*</span></label>
            <input
                name="service_date"
                type="date"
                value="{{ old('service_date', isset($quotation->service_date) ? $quotation->service_date->format('Y-m-d') : '') }}"
                class="mt-1 app-input"
                required
            >
            @error('service_date')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Duration (Days)') }} <span class="text-rose-600">*</span></label>
            <input
                id="quotation-duration-days"
                name="duration_days"
                type="number"
                min="1"
                value="{{ $initialDurationDays }}"
                class="mt-1 app-input"
                required
            >
            @error('duration_days')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Duration (Nights)') }}</label>
            <input
                id="quotation-duration-nights"
                name="duration_nights"
                type="number"
                min="0"
                value="{{ $initialDurationNights }}"
                class="mt-1 app-input bg-gray-50 dark:bg-gray-900/40"
                readonly
            >
            @error('duration_nights')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Validity Date') }} <span class="text-rose-600">*</span></label>
            <input
                name="validity_date"
                type="date"
                value="{{ old('validity_date', isset($quotation->validity_date) ? $quotation->validity_date->format('Y-m-d') : '') }}"
                class="mt-1 app-input"
                required
            >
            @error('validity_date')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div id="quotation-items-section" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700 {{ $hasItems ? '' : 'hidden' }}">
        @if ($errors->has('items') || $errors->has('items.*.description') || $errors->has('items.*.qty') || $errors->has('items.*.unit_price'))
            <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300">
                {{ ui_phrase('Please review quotation items again. Make sure Description, Qty, and Unit Price are filled correctly.') }}
            </div>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Items') }}</p>
            <span id="itinerary-items-summary" class="text-xs text-gray-500 dark:text-gray-400"></span>
        </div>
        <div id="quotation-items" class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
            <div class="hidden sm:grid sm:grid-cols-12 sm:gap-2 sticky top-0 z-10 mb-2 rounded-md border border-slate-800 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-white bg-slate-900">
                <div></div>
                <div class="sm:col-span-4">{{ ui_phrase('Description') }}</div>
                <div>{{ ui_phrase('Qty') }}</div>
                <div class="sm:col-span-2">{{ ui_phrase('Rate') }}</div>
                <div class="sm:col-span-3">{{ ui_phrase('Unit Price') }}</div>
                <div></div>
            </div>
            @for ($i = 0; $i < max($minRows, count($items)); $i++)
                @php
                    $row = $items[$i] ?? ['description' => '', 'qty' => 1, 'contract_rate' => 0, 'markup_type' => 'fixed', 'markup' => 0, 'unit_price' => 0, 'discount_type' => 'fixed', 'discount' => 0];
                    $serviceableMetaValue = $row['serviceable_meta'] ?? '';
                    if (is_array($serviceableMetaValue)) {
                        $serviceableMetaValue = json_encode($serviceableMetaValue);
                    }
                @endphp
                <div class="grid grid-cols-1 gap-2 px-2 py-2 sm:grid-cols-12 sm:items-start quotation-item-row" data-row-mode="quotation_item">
                    <div class="hidden sm:flex sm:items-start sm:justify-center">
                        <button
                            type="button"
                            data-quotation-item-drag-handle="1"
                            class="inline-flex h-[42px] min-h-[42px] w-[42px] cursor-grab items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 text-gray-500 active:cursor-grabbing dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            title="{{ ui_phrase('Drag to reorder or move between days') }}"
                            aria-label="{{ ui_phrase('Drag to reorder or move between days') }}"
                        >
                            <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Description') }}</label>
                        <input data-field="description" name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" class="quotation-item-control dark:border-gray-600 app-input" required>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Qty') }}</label>
                        <input data-field="qty" name="items[{{ $i }}][qty]" type="number" min="1" value="{{ $row['qty'] ?? 1 }}" class="quotation-item-control dark:border-gray-600 app-input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <x-money-input
                            label="{{ ui_phrase('Rate') }}"
                            label-class="block text-xs text-gray-500 sm:hidden"
                            wrapper-class="quotation-item-money-field"
                            :value="$row['unit_price'] ?? 0"
                            data-field="rate"
                            input-class="quotation-item-control"
                            step="0.01"
                            :readonly="(float) ($row['unit_price'] ?? 0) > 0"
                            compact
                        />
                    </div>
                    <div class="sm:col-span-3">
                        <x-money-input
                            label="{{ ui_phrase('Unit Price') }}"
                            label-class="block text-xs text-gray-500 sm:hidden"
                            wrapper-class="quotation-item-money-field"
                            :value="max(1, (int) ($row['qty'] ?? 1)) * (float) ($row['unit_price'] ?? 0)"
                            data-field="unit_price"
                            input-class="quotation-item-control"
                            step="0.01"
                            readonly
                            compact
                        />
                    </div>
                    <div class="flex items-start sm:justify-center">
                        <button
                            type="button"
                            data-remove-service-item="1"
                            class="btn-danger-sm inline-flex h-[42px] min-h-[42px] w-[42px] items-center justify-center"
                            title="{{ ui_phrase('Remove') }}"
                            aria-label="{{ ui_phrase('Remove') }}"
                        >
                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </div>
                    <input type="hidden" data-field="day_number" name="items[{{ $i }}][day_number]" value="{{ $row['day_number'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="sort_order" name="items[{{ $i }}][sort_order]" value="{{ $row['sort_order'] ?? ($i + 1) }}">
                    <input type="hidden" data-field="id" name="items[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                    <input type="hidden" data-field="contract_rate" name="items[{{ $i }}][contract_rate]" value="{{ $row['contract_rate'] ?? 0 }}">
                    <input type="hidden" data-field="source_item_id" name="items[{{ $i }}][source_item_id]" value="{{ $row['source_item_id'] ?? '' }}">
                    <input type="hidden" data-field="markup_type" name="items[{{ $i }}][markup_type]" value="{{ ($row['markup_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed' }}">
                    <input type="hidden" data-field="markup" name="items[{{ $i }}][markup]" value="{{ $row['markup'] ?? 0 }}">
                    <input type="hidden" data-field="discount_type" name="items[{{ $i }}][discount_type]" value="{{ ($row['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed' }}">
                    <input type="hidden" data-field="discount" name="items[{{ $i }}][discount]" value="{{ $row['discount'] ?? 0 }}">
                    <input type="hidden" value="{{ $row['unit_price'] ?? 0 }}">
                    <input type="hidden" data-field="serviceable_type" name="items[{{ $i }}][serviceable_type]" value="{{ $row['serviceable_type'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="serviceable_id" name="items[{{ $i }}][serviceable_id]" value="{{ $row['serviceable_id'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="serviceable_meta" name="items[{{ $i }}][serviceable_meta]" value="{{ $serviceableMetaValue }}" class="app-input">
                    <input type="hidden" data-field="itinerary_item_type" name="items[{{ $i }}][itinerary_item_type]" value="{{ $row['itinerary_item_type'] ?? '' }}" class="app-input">
                </div>
            @endfor
        </div>
        <template id="quotation-item-row-template">
            <div class="grid grid-cols-1 gap-2 px-2 py-2 sm:grid-cols-12 sm:items-start quotation-item-row" data-row-mode="quotation_item">
                <div class="hidden sm:flex sm:items-start sm:justify-center">
                    <button
                        type="button"
                        data-quotation-item-drag-handle="1"
                        class="inline-flex h-[42px] min-h-[42px] w-[42px] cursor-grab items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 text-gray-500 active:cursor-grabbing dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                        title="{{ ui_phrase('Drag to reorder or move between days') }}"
                        aria-label="{{ ui_phrase('Drag to reorder or move between days') }}"
                    >
                        <i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Description') }}</label>
                    <input data-field="description" class="quotation-item-control dark:border-gray-600 app-input" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Qty') }}</label>
                    <input data-field="qty" type="number" min="1" class="quotation-item-control dark:border-gray-600 app-input" required>
                </div>
                <div class="sm:col-span-2">
                    <x-money-input
                        label="{{ ui_phrase('Rate') }}"
                        label-class="block text-xs text-gray-500 sm:hidden"
                        wrapper-class="quotation-item-money-field"
                        data-field="rate"
                        input-class="quotation-item-control"
                        step="0.01"
                        compact
                    />
                </div>
                <div class="sm:col-span-3">
                    <x-money-input
                        label="{{ ui_phrase('Unit Price') }}"
                        label-class="block text-xs text-gray-500 sm:hidden"
                        wrapper-class="quotation-item-money-field"
                        data-field="unit_price"
                        input-class="quotation-item-control"
                        step="0.01"
                        readonly
                        compact
                    />
                </div>
                <div class="flex items-start sm:justify-center">
                    <button
                        type="button"
                        data-remove-service-item="1"
                        class="btn-danger-sm inline-flex h-[42px] min-h-[42px] w-[42px] items-center justify-center"
                        title="{{ ui_phrase('Remove') }}"
                        aria-label="{{ ui_phrase('Remove') }}"
                    >
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    </button>
                </div>
                <input type="hidden" data-field="day_number" class="app-input">
                <input type="hidden" data-field="sort_order" value="">
                <input type="hidden" data-field="id" value="">
                <input type="hidden" data-field="contract_rate" value="0">
                <input type="hidden" data-field="source_item_id" value="">
                <input type="hidden" data-field="markup_type" value="fixed">
                <input type="hidden" data-field="markup" value="0">
                <input type="hidden" data-field="discount_type" value="fixed">
                <input type="hidden" data-field="discount" value="0">
                <input type="hidden" data-field="serviceable_type" class="app-input">
                <input type="hidden" data-field="serviceable_id" class="app-input">
                <input type="hidden" data-field="serviceable_meta" class="app-input">
                <input type="hidden" data-field="itinerary_item_type" class="app-input">
            </div>
        </template>
    </div>

    <div id="quotation-service-items-section" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Service Items') }}</p>
        </div>
        <div class="mt-3 grid grid-cols-1 items-start gap-3 md:grid-cols-12">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Type') }}</label>
                <select id="service-item-type-select" class="mt-1 app-input">
                    <option value="">{{ ui_phrase('Select Service Type') }}</option>
                    <option value="activity">{{ ui_phrase('Activities') }}</option>
                    <option value="island_transfer">{{ ui_phrase('Island Transfer') }}</option>
                    <option value="fnb">{{ ui_phrase('F&B') }}</option>
                    <option value="hotel_room">{{ ui_phrase('Hotels') }}</option>
                    <option value="transport">{{ ui_phrase('Transport') }}</option>
                    <option value="attraction">{{ ui_phrase('Tourist Attraction') }}</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">{{ ui_phrase('Service Item') }}</label>
                <div class="relative">
                    <input
                        id="service-item-input"
                        type="text"
                        class="mt-1 app-input"
                        placeholder="{{ ui_phrase('Type or select item') }}"
                        autocomplete="off"
                    >
                    <div
                        id="service-item-dropdown"
                        class="absolute z-20 mt-1 hidden max-h-56 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    ></div>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">{{ ui_phrase('Qty') }}</label>
                <input id="service-item-qty" type="number" min="1" value="1" class="mt-1 app-input">
            </div>
            <div id="service-item-pax-type-wrap" class="hidden md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">{{ ui_phrase('Passenger Type') }}</label>
                <select id="service-item-pax-type" class="mt-1 app-input">
                    <option value="adult">{{ ui_phrase('Adult') }}</option>
                    <option value="child">{{ ui_phrase('Child') }}</option>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">{{ ui_phrase('Day') }}</label>
                <select id="service-item-day" class="mt-1 app-input"></select>
            </div>
            <div class="md:col-span-2 md:self-start">
                <span class="hidden h-4 text-xs font-medium md:block" aria-hidden="true">&nbsp;</span>
                <button type="button" id="service-item-add-btn" class="btn-outline-sm mt-2 flex h-[42px] min-h-[42px] w-full items-center justify-center">
                    {{ ui_phrase('Add Service') }}
                </button>
            </div>
        </div>
    </div>

    <div id="quotation-manual-items-section" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Additional Items') }}</p>
            <button
                type="button"
                id="quotation-add-item-btn"
                class="btn-outline-sm"
            >
                {{ ui_phrase('Add Item') }}
            </button>
        </div>

        <div id="quotation-manual-items" class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
            <div class="hidden sm:grid sm:grid-cols-12 sm:gap-2 sticky top-0 z-10 mb-2 rounded-md border border-slate-800 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-white bg-slate-900">
                <div class="sm:col-span-5">{{ ui_phrase('Description') }}</div>
                <div class="sm:col-span-2">{{ ui_phrase('Qty') }}</div>
                <div class="sm:col-span-2">{{ ui_phrase('Rate') }}</div>
                <div class="sm:col-span-2">{{ ui_phrase('Unit Price') }}</div>
                <div class="sm:col-span-1"></div>
            </div>
            @for ($j = 0; $j < count($manualItems); $j++)
                @php
                    $row = $manualItems[$j] ?? ['description' => '', 'qty' => 1, 'contract_rate' => 0, 'markup_type' => 'fixed', 'markup' => 0, 'discount_type' => 'fixed', 'discount' => 0, 'unit_price' => 0];
                    $idx = count($items) + $j;
                    $manualQty = max(1, (int) ($row['qty'] ?? 1));
                    $manualRate = (float) ($row['unit_price'] ?? 0);
                    $manualTotal = $manualQty * $manualRate;
                @endphp
                <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-12 quotation-manual-row" data-row-mode="manual">
                    <div class="sm:col-span-5">
                        <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Description') }}</label>
                        <input data-field="description" name="items[{{ $idx }}][description]" value="{{ $row['description'] ?? '' }}" class="quotation-item-control dark:border-gray-600 app-input" required></div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Qty') }}</label>
                        <input data-field="qty" name="items[{{ $idx }}][qty]" type="number" min="1" value="{{ $manualQty }}" class="quotation-item-control dark:border-gray-600 app-input" required>
                    </div>
                    <div class="sm:col-span-2">
                        <x-money-input
                            label="{{ ui_phrase('Rate') }}"
                            label-class="block text-xs text-gray-500 sm:hidden"
                            wrapper-class="quotation-item-money-field w-full"
                            name="items[{{ $idx }}][rate]"
                            :value="$manualRate"
                            data-field="rate"
                            input-class="quotation-item-control"
                            step="0.01"
                            compact
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <x-money-input
                            label="{{ ui_phrase('Unit Price') }}"
                            label-class="block text-xs text-gray-500 sm:hidden"
                            wrapper-class="quotation-item-money-field w-full"
                            :value="$manualTotal"
                            data-field="unit_price"
                            input-class="quotation-item-control"
                            step="0.01"
                            required
                            readonly
                            compact
                        />
                    </div>
                    <div class="sm:col-span-1 flex items-end">
                        <button
                            type="button"
                            data-remove-manual-item="1"
                            class="btn-danger-sm inline-flex h-[42px] min-h-[42px] w-full items-center justify-center"
                        >
                            {{ ui_phrase('Remove') }}
                        </button>
                    </div>
                    <input type="hidden" data-field="contract_rate" value="">
                    <input type="hidden" data-field="id" name="items[{{ $idx }}][id]" value="{{ $row['id'] ?? '' }}">
                    <input type="hidden" data-field="source_item_id" name="items[{{ $idx }}][source_item_id]" value="{{ $row['source_item_id'] ?? '' }}">
                    <input type="hidden" data-field="markup_type" value="fixed">
                    <input type="hidden" data-field="markup" value="0">
                    <input type="hidden" data-field="discount_type" value="fixed">
                    <input type="hidden" data-field="discount" value="0">
                    <input type="hidden" data-field="serviceable_type" name="items[{{ $idx }}][serviceable_type]" value="{{ $row['serviceable_type'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="serviceable_id" name="items[{{ $idx }}][serviceable_id]" value="{{ $row['serviceable_id'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="day_number" name="items[{{ $idx }}][day_number]" value="{{ $row['day_number'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="sort_order" name="items[{{ $idx }}][sort_order]" value="{{ $row['sort_order'] ?? ($idx + 1) }}">
                    <input type="hidden" data-field="serviceable_meta" name="items[{{ $idx }}][serviceable_meta]" value="{{ is_array($row['serviceable_meta'] ?? null) ? json_encode($row['serviceable_meta']) : ($row['serviceable_meta'] ?? '') }}" class="app-input">
                    <input type="hidden" data-field="itinerary_item_type" name="items[{{ $idx }}][itinerary_item_type]" value="manual" class="app-input">
                </div>
            @endfor
        </div>

        <template id="quotation-manual-row-template">
            <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-12 quotation-manual-row" data-row-mode="manual">
                <div class="sm:col-span-5">
                    <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Description') }}</label>
                    <input data-field="description" class="quotation-item-control dark:border-gray-600 app-input" required></div>
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500 sm:hidden">{{ ui_phrase('Qty') }}</label>
                    <input data-field="qty" type="number" min="1" class="quotation-item-control dark:border-gray-600 app-input" required>
                </div>
                <div class="sm:col-span-2">
                    <x-money-input
                        label="{{ ui_phrase('Rate') }}"
                        label-class="block text-xs text-gray-500 sm:hidden"
                        wrapper-class="quotation-item-money-field w-full"
                        data-field="rate"
                        input-class="quotation-item-control"
                        step="0.01"
                        compact
                    />
                </div>
                <div class="sm:col-span-2">
                    <x-money-input
                        label="{{ ui_phrase('Unit Price') }}"
                        label-class="block text-xs text-gray-500 sm:hidden"
                        wrapper-class="quotation-item-money-field w-full"
                        data-field="unit_price"
                        input-class="quotation-item-control"
                        step="0.01"
                        required
                        readonly
                        compact
                    />
                </div>
                <div class="sm:col-span-1 flex items-end">
                    <button
                        type="button"
                        data-remove-manual-item="1"
                        class="btn-danger-sm inline-flex h-[42px] min-h-[42px] w-full items-center justify-center"
                    >
                        {{ ui_phrase('Remove') }}
                    </button>
                </div>
                <input type="hidden" data-field="contract_rate" value="">
                <input type="hidden" data-field="id" value="">
                <input type="hidden" data-field="source_item_id" value="">
                <input type="hidden" data-field="markup_type" value="fixed">
                <input type="hidden" data-field="markup" value="0">
                <input type="hidden" data-field="discount_type" value="fixed">
                <input type="hidden" data-field="discount" value="0">
                <input type="hidden" data-field="serviceable_type" class="app-input">
                <input type="hidden" data-field="serviceable_id" class="app-input">
                <input type="hidden" data-field="day_number" class="app-input">
                <input type="hidden" data-field="sort_order" value="">
                <input type="hidden" data-field="serviceable_meta" class="app-input">
                <input type="hidden" data-field="itinerary_item_type" value="manual" class="app-input">
            </div>
        </template>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <input type="hidden" id="main-global-discount-type" name="discount_type" value="{{ old('discount_type', $quotation->discount_type ?? '') }}">
        <input type="hidden" id="main-global-discount-value" name="discount_value" value="{{ old('discount_value', $quotation->discount_value ?? 0) }}">
        <div>
            <x-money-input
                label="{{ ui_phrase('Final Amount (Auto)') }}"
                name="final_amount"
                id="quotation-final-amount"
                step="0.01"
                :value="old('final_amount', $quotation->final_amount ?? 0)"
                readonly
            />
            @error('final_amount')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button type="submit" class="btn-primary inline-flex items-center gap-2">
            <i class="fa-solid fa-floppy-disk w-4 text-center"></i>
            <span>{{ $buttonLabel }}</span>
        </button>
        <a href="{{ route('quotations.index') }}" class="btn-secondary inline-flex items-center gap-2">
            <i class="fa-solid fa-xmark w-4 text-center"></i>
            <span>{{ ui_phrase('Cancel') }}</span>
        </a>
    </div>
</div>

@once
    @push('styles')
        <style>
            .quotation-form-no-labels #quotation-items-section label {
                display: block !important;
            }

            @media (min-width: 640px) {
                .quotation-form-no-labels #quotation-items-section label {
                    display: none !important;
                }
            }

            .quotation-form-no-labels #quotation-manual-items-section .app-input,
            .quotation-form-no-labels #quotation-manual-items-section .quotation-item-money-field {
                min-width: 0 !important;
                width: 100% !important;
            }

            .quotation-form-no-labels .quotation-item-row {
                align-items: flex-start;
            }

            .quotation-form-no-labels .quotation-item-row .quotation-item-control,
            .quotation-form-no-labels .quotation-item-row .quotation-item-money-field,
            .quotation-form-no-labels .quotation-item-row [data-quotation-item-drag-handle="1"],
            .quotation-form-no-labels .quotation-item-row [data-remove-service-item="1"] {
                min-height: 42px;
                height: 42px;
            }

            .quotation-form-no-labels .quotation-item-row [data-quotation-item-drag-handle="1"],
            .quotation-form-no-labels .quotation-item-row [data-remove-service-item="1"] {
                min-width: 42px;
                width: 42px;
                max-width: 42px;
                padding-left: 0;
                padding-right: 0;
            }

            .quotation-form-no-labels .quotation-item-row .quotation-item-money-field {
                display: block;
                min-width: 0;
                width: 100%;
            }
        </style>
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            (function() {
                const itemsContainer = document.getElementById('quotation-items');
                const itemsTemplate = document.getElementById('quotation-item-row-template');
                const manualItemsContainer = document.getElementById('quotation-manual-items');
                const manualItemsTemplate = document.getElementById('quotation-manual-row-template');
                if (!itemsContainer || !itemsTemplate || !manualItemsContainer || !manualItemsTemplate) return;

                const itinerarySelect = document.getElementById('itinerary-select');
                const destinationSelect = document.getElementById('destination-select');
                const inquirySelect = document.getElementById('inquiry-select');
                const customerSelect = document.getElementById('customer-agent-select');
                const generateBtn = document.getElementById('itinerary-generate-btn');
                const statusEl = document.getElementById('itinerary-generate-status');
                const summaryEl = document.getElementById('itinerary-items-summary');
                const itemsSection = document.getElementById('quotation-items-section');
                const addItemBtn = document.getElementById('quotation-add-item-btn');
                const serviceItemsSection = document.getElementById('quotation-service-items-section');
                const serviceItemTypeSelect = document.getElementById('service-item-type-select');
                const serviceItemInput = document.getElementById('service-item-input');
                const serviceItemDropdown = document.getElementById('service-item-dropdown');
                const serviceItemQtyInput = document.getElementById('service-item-qty');
                const serviceItemPaxTypeWrap = document.getElementById('service-item-pax-type-wrap');
                const serviceItemPaxTypeSelect = document.getElementById('service-item-pax-type');
                const serviceItemDaySelect = document.getElementById('service-item-day');
                const serviceItemAddBtn = document.getElementById('service-item-add-btn');
                const itemDiscountTotalInput = document.getElementById('quotation-item-discount-total');
                const subTotalInput = document.getElementById('quotation-sub-total');
                const discountAmountInput = document.getElementById('quotation-global-discount-amount');
                const finalAmountInput = document.getElementById('quotation-final-amount');
                const formEl = itemsContainer.closest('form');
                const discountTypeInput = formEl?.querySelector('#main-global-discount-type');
                const discountValueInput = formEl?.querySelector('#main-global-discount-value');

                const endpoint = itinerarySelect ? (itinerarySelect.dataset.endpoint || '') : '';
                const canUseItinerary = Boolean(itinerarySelect && generateBtn);
                const itineraryOptionDataset = itinerarySelect
                    ? Array.from(itinerarySelect.options).map((option, index) => ({
                        value: String(option.value || ''),
                        text: option.textContent || '',
                        destinationId: String(option.dataset.destinationId || ''),
                        inquiryId: String(option.dataset.inquiryId || ''),
                        inquiryNumber: String(option.dataset.inquiryNumber || ''),
                        customerId: String(option.dataset.customerId || ''),
                        durationDays: String(option.dataset.durationDays || ''),
                        durationNights: String(option.dataset.durationNights || ''),
                        isPlaceholder: index === 0,
                    }))
                    : [];
                const currencyCode = String(window.appCurrency || 'IDR').toUpperCase();
                const rateToIdr = Number(window.appCurrencyRateToIdr || 1);
                const i18n = {
                    selectItineraryFirst: @json(ui_phrase('Please select an itinerary first.')),
                    replaceExistingItemsConfirm: @json(ui_phrase('Existing items will be replaced. Continue?')),
                    fetchingItems: @json(ui_phrase('Fetching items from itinerary...')),
                    fetchItemsFailed: @json(ui_phrase('Failed to fetch items from itinerary.')),
                    itemsLoadedPattern: @json(ui_phrase('Items loaded: :count.')),
                    missingPricePattern: @json(ui_phrase('Note: :count items have zero price. Please review.')),
                    manualItemAdded: @json(ui_phrase('Manual item added successfully.')),
                    selectServiceTypeFirst: @json(ui_phrase('Please select service type first.')),
                    selectServiceItemFirst: @json(ui_phrase('Please type/select service item first.')),
                    serviceItemNotFound: @json(ui_phrase('Service item not found. Please choose from suggestion list.')),
                    serviceItemAmbiguous: @json(ui_phrase('Multiple service items match. Please type more specific name.')),
                    serviceItemAdded: @json(ui_phrase('Service item added successfully.')),
                    descriptionLabel: @json(ui_phrase('Description')),
                    qtyLabel: @json(ui_phrase('Qty')),
                    rateLabel: @json(ui_phrase('Rate')),
                    unitPriceLabel: @json(ui_phrase('Unit Price')),
                    paxTypeLabel: @json(ui_phrase('Passenger Type')),
                    adultLabel: @json(ui_phrase('Adult')),
                    childLabel: @json(ui_phrase('Child')),
                    activityLabelPrefix: @json(ui_phrase('Activity') . ': '),
                    islandTransferLabelPrefix: @json(ui_phrase('Island Transfer') . ': '),
                    fnbLabelPrefix: @json(ui_phrase('F&B') . ': '),
                    hotelLabelPrefix: @json(ui_phrase('Hotel') . ': '),
                    transportLabelPrefix: @json(ui_phrase('Transport') . ': '),
                    attractionLabelPrefix: @json(ui_phrase('Attraction') . ': '),
                };
                const serviceCatalogs = @json($serviceCatalogs);
                let currentServiceItemOptions = [];
                const serviceTypeConfig = {
                    activity: {
                        key: 'activities',
                        labelPrefix: i18n.activityLabelPrefix,
                        serviceableType: @json(\App\Models\Activity::class),
                        itineraryItemType: 'activity',
                        supportsPaxType: true,
                    },
                    island_transfer: {
                        key: 'island_transfers',
                        labelPrefix: i18n.islandTransferLabelPrefix,
                        serviceableType: @json(\App\Models\IslandTransfer::class),
                        itineraryItemType: 'transfer',
                    },
                    fnb: {
                        key: 'food_beverages',
                        labelPrefix: i18n.fnbLabelPrefix,
                        serviceableType: @json(\App\Models\FoodBeverage::class),
                        itineraryItemType: 'fnb',
                        supportsPaxType: true,
                    },
                    hotel_room: {
                        key: 'hotel_rooms',
                        labelPrefix: i18n.hotelLabelPrefix,
                        serviceableType: @json(\App\Models\HotelRoom::class),
                        itineraryItemType: 'hotel_day_end',
                    },
                    transport: {
                        key: 'transport_units',
                        labelPrefix: i18n.transportLabelPrefix,
                        serviceableType: @json(\App\Models\TransportUnit::class),
                        itineraryItemType: 'transport_day',
                    },
                    attraction: {
                        key: 'tourist_attractions',
                        labelPrefix: i18n.attractionLabelPrefix,
                        serviceableType: @json(\App\Models\TouristAttraction::class),
                        itineraryItemType: 'attraction',
                    },
                };

                const parseInteger = (value) => {
                    const raw = String(value ?? '').trim();
                    if (raw === '') return 0;

                    // Raw decimal from backend, e.g. "1500000.00"
                    if (/^\d+([.,]\d{1,2})?$/.test(raw) && !raw.includes(' ')) {
                        const numeric = Number(raw.replace(',', '.'));
                        if (Number.isFinite(numeric)) {
                            return Math.round(numeric);
                        }
                    }

                    const digits = raw.replace(/[^\d]/g, '');
                    if (digits === '') return 0;
                    const num = Number.parseInt(digits, 10);
                    return Number.isFinite(num) ? num : 0;
                };

                const parsePercent = (value) => {
                    const raw = String(value ?? '').trim();
                    if (raw === '') return 0;

                    // Plain decimal format from DB (e.g. "10.00")
                    if (/^-?\d+(\.\d+)?$/.test(raw)) {
                        const direct = Number.parseFloat(raw);
                        return Number.isFinite(direct) ? direct : 0;
                    }

                    let normalized = raw.replace(/[^\d,.-]/g, '');
                    if (normalized === '') return 0;

                    const hasComma = normalized.includes(',');
                    const hasDot = normalized.includes('.');
                    if (hasComma && hasDot) {
                        // Locale format: 1.234,56
                        normalized = normalized.replace(/\./g, '').replace(',', '.');
                    } else if (hasComma) {
                        // Locale format: 10,5
                        normalized = normalized.replace(',', '.');
                    }

                    const num = Number.parseFloat(normalized);
                    return Number.isFinite(num) ? num : 0;
                };

                const formatMoneyDisplay = (value) => {
                    const safeValue = Math.max(0, Math.round(Number(value) || 0));
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(safeValue);
                };

                const idrToDisplay = (value) => {
                    const amount = Number(value) || 0;
                    if (currencyCode === 'IDR' || !Number.isFinite(rateToIdr) || rateToIdr <= 0) {
                        return amount;
                    }
                    return amount / rateToIdr;
                };

                const displayToIdr = (value) => {
                    const amount = Number(value) || 0;
                    if (currencyCode === 'IDR' || !Number.isFinite(rateToIdr) || rateToIdr <= 0) {
                        return amount;
                    }
                    return amount * rateToIdr;
                };

                const setMoneyInputDisplay = (input, value) => {
                    if (!input) return;
                    const safeValue = Math.max(0, Math.round(Number(value) || 0));
                    // If still number input, grouped format (1.500.000) becomes invalid and value gets cleared.
                    if (String(input.type || '').toLowerCase() === 'number') {
                        input.value = String(safeValue);
                        return;
                    }
                    input.value = formatMoneyDisplay(safeValue);
                };

                const computeRowBaseAmount = (row) => {
                    const contractRate = parseInteger(row.querySelector('[data-field="contract_rate"]')?.value);
                    const qty = Math.max(1, parseInteger(row.querySelector('[data-field="qty"]')?.value) || 1);
                    const markupType = (row.querySelector('[data-field="markup_type"]')?.value || 'fixed');
                    let markup = markupType === 'percent'
                        ? parsePercent(row.querySelector('[data-field="markup"]')?.value)
                        : parseInteger(row.querySelector('[data-field="markup"]')?.value);

                    if (markupType === 'percent' && markup > 100) {
                        markup = 100;
                        const markupInput = row.querySelector('[data-field="markup"]');
                        if (markupInput) markupInput.value = '100';
                    }

                    const baseUnitPrice = markupType === 'percent'
                        ? contractRate + (contractRate * (markup / 100))
                        : contractRate + markup;

                    return Math.max(0, baseUnitPrice * qty);
                };

                const computeRowDiscountAmount = (row, baseAmount) => {
                    const discountInput = row.querySelector('[data-field="discount"]');
                    const discountType = (row.querySelector('[data-field="discount_type"]')?.value || 'fixed');
                    let discount = discountType === 'percent'
                        ? parsePercent(discountInput?.value)
                        : parseInteger(discountInput?.value);
                    if (discountType === 'percent' && discount > 100) {
                        discount = 100;
                        if (discountInput) discountInput.value = '100';
                    }

                    return discountType === 'percent'
                        ? (baseAmount * (discount / 100))
                        : discount;
                };

                const computeRowUnitPrice = (row) => {
                    const rowMode = (row?.dataset?.rowMode || '');
                    const isRateBasedRow = rowMode === 'manual' || rowMode === 'quotation_item';
                    if (isRateBasedRow) {
                        const qty = Math.max(1, parseInteger(row.querySelector('[data-field="qty"]')?.value) || 1);
                        const rate = parseInteger(row.querySelector('[data-field="rate"]')?.value);
                        const totalRate = Math.max(0, qty * rate);
                        const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                        if (unitPriceInput) {
                            setMoneyInputDisplay(unitPriceInput, totalRate);
                        }
                        return totalRate;
                    }

                    const baseAmount = computeRowBaseAmount(row);
                    const discountAmount = computeRowDiscountAmount(row, baseAmount);
                    const unitPrice = Math.max(0, baseAmount - discountAmount);

                    const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                    if (unitPriceInput) {
                        setMoneyInputDisplay(unitPriceInput, unitPrice);
                    }

                    return unitPrice;
                };

                const recalcTotals = () => {
                    let subTotal = 0;
                    let itemDiscountTotal = 0;
                    getAllRows().forEach((row) => {
                        const rowMode = (row?.dataset?.rowMode || '');
                        const isRateBasedRow = rowMode === 'manual' || rowMode === 'quotation_item';
                        const unitPrice = computeRowUnitPrice(row);
                        const baseAmount = isRateBasedRow
                            ? unitPrice
                            : computeRowBaseAmount(row);
                        const rowDiscount = computeRowDiscountAmount(row, baseAmount);
                        itemDiscountTotal += rowDiscount;
                        const rowTotal = isRateBasedRow
                            ? Math.max(0, unitPrice - rowDiscount)
                            : Math.max(0, unitPrice);
                        subTotal += rowTotal;

                    });

                    const discountType = discountTypeInput?.value || '';
                    let rawDiscountValue = discountType === 'percent'
                        ? parsePercent(discountValueInput?.value)
                        : parseInteger(discountValueInput?.value);
                    if (discountType === 'percent') {
                        rawDiscountValue = Math.max(0, Math.min(100, rawDiscountValue));
                    }
                    let discountAmount = 0;
                    if (discountType === 'percent') {
                        discountAmount = subTotal * (rawDiscountValue / 100);
                    } else if (discountType === 'fixed') {
                        // Hidden global discount value is stored in IDR; convert to current display currency.
                        discountAmount = idrToDisplay(rawDiscountValue);
                    }

                    const finalAmount = Math.max(0, subTotal - discountAmount);

                    if (itemDiscountTotalInput) setMoneyInputDisplay(itemDiscountTotalInput, itemDiscountTotal);
                    if (subTotalInput) setMoneyInputDisplay(subTotalInput, subTotal);
                    if (discountAmountInput) setMoneyInputDisplay(discountAmountInput, discountAmount);
                    if (finalAmountInput) setMoneyInputDisplay(finalAmountInput, finalAmount);
                };

                let recalcFrameId = null;
                const scheduleRecalcTotals = () => {
                    if (recalcFrameId !== null) return;
                    const run = () => {
                        recalcFrameId = null;
                        recalcTotals();
                    };
                    if (typeof window.requestAnimationFrame === 'function') {
                        recalcFrameId = window.requestAnimationFrame(run);
                    } else {
                        recalcFrameId = window.setTimeout(run, 0);
                    }
                };

                const getAllRows = () => {
                    return [
                        ...Array.from(itemsContainer.querySelectorAll('.quotation-item-row')),
                        ...Array.from(manualItemsContainer.querySelectorAll('.quotation-manual-row')),
                    ];
                };

                const hasFilledItems = () => {
                    return Array.from(itemsContainer.querySelectorAll('[data-field="description"]'))
                        .some((input) => String(input.value || '').trim() !== '');
                };

                const toggleItemsVisibility = () => {
                    if (!itemsSection) return;
                    itemsSection.classList.toggle('hidden', !hasFilledItems());
                };

                const syncServiceItemsMode = () => {
                    if (serviceItemsSection) {
                        serviceItemsSection.classList.remove('hidden');
                    }
                    [serviceItemTypeSelect, serviceItemInput, serviceItemQtyInput, serviceItemPaxTypeSelect, serviceItemDaySelect, serviceItemAddBtn].forEach((el) => {
                        if (!el) return;
                        el.disabled = false;
                    });
                };

                const syncServiceItemPaxTypeVisibility = () => {
                    const type = String(serviceItemTypeSelect?.value || '').trim();
                    const config = serviceTypeConfig[type] || null;
                    const shouldShow = Boolean(config?.supportsPaxType);
                    serviceItemPaxTypeWrap?.classList.toggle('hidden', !shouldShow);
                    if (serviceItemPaxTypeSelect && !shouldShow) {
                        serviceItemPaxTypeSelect.value = 'adult';
                    }
                };

                const reindexItems = () => {
                    const rows = getAllRows();
                    rows.forEach((row, index) => {
                        row.querySelectorAll('[data-field]').forEach((input) => {
                            const field = input.dataset.field;
                            input.name = `items[${index}][${field}]`;
                            if (field === 'sort_order') {
                                input.value = String(index + 1);
                            }
                        });
                    });
                };

                const dayLabel = (dayNumber) => {
                    const day = Number(dayNumber);
                    if (Number.isFinite(day) && day > 0) {
                        return `Day ${day}`;
                    }
                    return 'Without Day';
                };
                let draggedQuotationItemRow = null;

                const normalizeDayNumber = (value) => {
                    const parsed = Number.parseInt(String(value || ''), 10);
                    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
                };

                const availableDayKeys = () => {
                    const days = Number.parseInt(String(durationDaysInput?.value || '1'), 10);
                    const safeDays = Number.isFinite(days) && days > 0 ? days : 1;
                    return Array.from({ length: safeDays }, (_, index) => String(index + 1));
                };

                const getDropTargetRow = (container, y, draggingRow) => {
                    const candidates = Array.from(container.querySelectorAll('.quotation-item-row'))
                        .filter((row) => row !== draggingRow);
                    let closest = { offset: Number.NEGATIVE_INFINITY, element: null };
                    candidates.forEach((row) => {
                        const box = row.getBoundingClientRect();
                        const offset = y - box.top - (box.height / 2);
                        if (offset < 0 && offset > closest.offset) {
                            closest = { offset, element: row };
                        }
                    });
                    return closest.element;
                };

                const setRowDayNumber = (row, dayNumber) => {
                    if (!row) return;
                    const day = normalizeDayNumber(dayNumber);
                    const dayInput = row.querySelector('[data-field="day_number"]');
                    if (dayInput && day !== null) {
                        dayInput.value = String(day);
                    }
                };

                const syncQuotationItemDayInputBounds = () => {
                    const maxDay = availableDayKeys().length || 1;
                    itemsContainer.querySelectorAll('.quotation-item-row [data-field="day_number"]').forEach((input) => {
                        input.min = '1';
                        input.max = String(maxDay);
                        const day = normalizeDayNumber(input.value);
                        if (day !== null && day > maxDay) {
                            input.value = String(maxDay);
                        }
                    });
                };

                const clearDropZoneStates = () => {
                    itemsContainer.querySelectorAll('[data-role="day-body"][data-accept-quotation-item="1"]').forEach((zone) => {
                        zone.classList.remove('ring-2', 'ring-indigo-300', 'ring-offset-1', 'bg-indigo-50/50', 'dark:bg-indigo-900/10');
                    });
                };

                const wireQuotationItemDragAndDrop = () => {
                    itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => {
                        if (row.dataset.dndBound === '1') return;
                        row.dataset.dndBound = '1';
                        row.draggable = true;
                        const dragHandle = row.querySelector('[data-quotation-item-drag-handle="1"]');
                        if (dragHandle) {
                            dragHandle.addEventListener('mousedown', () => {
                                row.dataset.dragFromHandle = '1';
                            });
                            dragHandle.addEventListener('mouseup', () => {
                                row.dataset.dragFromHandle = '0';
                            });
                            dragHandle.addEventListener('mouseleave', () => {
                                row.dataset.dragFromHandle = '0';
                            });
                        }
                        row.addEventListener('dragstart', (event) => {
                            if (row.dataset.dragFromHandle !== '1') {
                                event.preventDefault();
                                return;
                            }
                            draggedQuotationItemRow = row;
                            row.classList.add('opacity-60');
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', row.querySelector('[data-field="description"]')?.value || '');
                        });
                        row.addEventListener('dragend', () => {
                            row.classList.remove('opacity-60');
                            row.dataset.dragFromHandle = '0';
                            draggedQuotationItemRow = null;
                            clearDropZoneStates();
                            reindexItems();
                            recalcTotals();
                        });
                    });

                    itemsContainer.querySelectorAll('[data-role="day-body"]').forEach((zone) => {
                        if (zone.dataset.dropBound === '1') return;
                        zone.dataset.dropBound = '1';
                        zone.addEventListener('dragover', (event) => {
                            if (!draggedQuotationItemRow) return;
                            event.preventDefault();
                            zone.classList.add('ring-2', 'ring-indigo-300', 'ring-offset-1', 'bg-indigo-50/50', 'dark:bg-indigo-900/10');
                            const nextRow = getDropTargetRow(zone, event.clientY, draggedQuotationItemRow);
                            if (nextRow) {
                                zone.insertBefore(draggedQuotationItemRow, nextRow);
                            } else {
                                zone.appendChild(draggedQuotationItemRow);
                            }
                            setRowDayNumber(draggedQuotationItemRow, zone.dataset.dayNumber);
                            reindexItems();
                        });
                        zone.addEventListener('dragleave', (event) => {
                            if (zone.contains(event.relatedTarget)) return;
                            zone.classList.remove('ring-2', 'ring-indigo-300', 'ring-offset-1', 'bg-indigo-50/50', 'dark:bg-indigo-900/10');
                        });
                        zone.addEventListener('drop', (event) => {
                            if (!draggedQuotationItemRow) return;
                            event.preventDefault();
                            setRowDayNumber(draggedQuotationItemRow, zone.dataset.dayNumber);
                            clearDropZoneStates();
                            reindexItems();
                            recalcTotals();
                        });
                    });
                };

                const regroupItemsByDay = () => {
                    const rows = Array.from(itemsContainer.querySelectorAll('.quotation-item-row'));
                    const groups = new Map();
                    availableDayKeys().forEach((key) => groups.set(key, []));
                    rows.forEach((row) => {
                        const key = String(normalizeDayNumber(row.querySelector('[data-field="day_number"]')?.value) || '');
                        if (!groups.has(key)) {
                            groups.set(key, []);
                        }
                        groups.get(key).push(row);
                    });

                    itemsContainer.innerHTML = '';
                    groups.forEach((groupRows, key) => {
                        const card = document.createElement('div');
                        card.className = 'quotation-day-group mb-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700';

                        const heading = document.createElement('div');
                        heading.className = 'mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300';
                        heading.textContent = dayLabel(key);
                        card.appendChild(heading);

                        const tableHeader = document.createElement('div');
                        tableHeader.className = 'hidden sm:grid sm:grid-cols-12 sm:gap-2 sticky top-0 z-10 mb-2 rounded-md border border-slate-800 px-2 py-2 text-[11px] font-semibold uppercase tracking-wide text-white';
                        tableHeader.style.setProperty('--tw-bg-opacity', '1');
                        tableHeader.style.backgroundColor = 'rgb(15 23 42 / var(--tw-bg-opacity, 1))';
                        tableHeader.innerHTML = `
                            <div></div>
                            <div class="sm:col-span-4">${i18n.descriptionLabel}</div>
                            <div>${i18n.qtyLabel}</div>
                            <div class="sm:col-span-2">${i18n.rateLabel}</div>
                            <div class="sm:col-span-3">${i18n.unitPriceLabel}</div>
                            <div></div>
                        `;
                        card.appendChild(tableHeader);

                        const body = document.createElement('div');
                        body.className = 'min-h-[54px] divide-y divide-gray-200 rounded-md transition-colors dark:divide-gray-700';
                        body.dataset.role = 'day-body';
                        body.dataset.dayNumber = key;
                        body.dataset.acceptQuotationItem = key !== '' ? '1' : '0';
                        groupRows.forEach((row) => body.appendChild(row));
                        card.appendChild(body);

                        itemsContainer.appendChild(card);
                    });
                    syncQuotationItemDayInputBounds();
                    wireQuotationItemDragAndDrop();
                };

                const buildRow = (index, row) => {
                    const node = itemsTemplate.content.firstElementChild.cloneNode(true);
                    const normalizedRow = { ...(row || {}) };
                    const rawContract = Number(normalizedRow.contract_rate ?? 0);
                    const rawUnit = Number(normalizedRow.unit_price ?? 0);
                    const rawMarkup = Number(normalizedRow.markup ?? 0);
                    if ((!Number.isFinite(rawContract) || rawContract <= 0) && Number.isFinite(rawUnit) && rawUnit > 0) {
                        normalizedRow.contract_rate = rawUnit;
                        normalizedRow.markup_type = 'fixed';
                        normalizedRow.markup = Number.isFinite(rawMarkup) && rawMarkup > 0 ? rawMarkup : 0;
                    }
                    const masterRate = Number(normalizedRow?.rate ?? normalizedRow?.unit_price ?? 0);
                    const lockRate = Number.isFinite(masterRate) && masterRate > 0;
                    const setValue = (input, value, fallback) => {
                        const v = value !== undefined && value !== null ? value : fallback;
                        input.value = v;
                    };
                    node.querySelectorAll('[data-field]').forEach((input) => {
                        const field = input.dataset.field;
                        input.name = `items[${index}][${field}]`;
                        if (field === 'qty') {
                            const qty = Number(row?.qty);
                            setValue(input, Number.isFinite(qty) && qty > 0 ? qty : 1, 1);
                            return;
                        }
                        if (field === 'rate') {
                            const rateValue = Number(normalizedRow?.rate ?? normalizedRow?.unit_price ?? normalizedRow?.contract_rate ?? 0);
                            const idrValue = Number.isFinite(rateValue) ? rateValue : 0;
                            const displayValue = idrToDisplay(idrValue);
                            setValue(input, String(Math.max(0, Math.round(displayValue))), '0');
                            input.readOnly = lockRate;
                            return;
                        }
                        if (field === 'contract_rate' || field === 'markup' || field === 'unit_price' || field === 'discount') {
                            const val = Number(normalizedRow?.[field]);
                            const idrValue = Number.isFinite(val) ? val : 0;
                            const displayValue = idrToDisplay(idrValue);
                            setValue(input, String(Math.max(0, Math.round(displayValue))), '0');
                            return;
                        }
                        if (field === 'markup_type') {
                            setValue(input, normalizedRow?.markup_type ?? 'fixed', 'fixed');
                            return;
                        }
                        if (field === 'discount_type') {
                            setValue(input, normalizedRow?.discount_type ?? 'fixed', 'fixed');
                            return;
                        }
                        if (field === 'day_number') {
                            const day = Number(normalizedRow?.day_number);
                            setValue(input, Number.isFinite(day) && day > 0 ? day : '', '');
                            return;
                        }
                        if (field === 'serviceable_meta') {
                            const meta = normalizedRow?.serviceable_meta ?? '';
                            if (meta && typeof meta === 'object') {
                                setValue(input, JSON.stringify(meta), '');
                            } else {
                                setValue(input, meta ?? '', '');
                            }
                            return;
                        }
                        if (field === 'itinerary_item_type') {
                            setValue(input, normalizedRow?.itinerary_item_type ?? '', '');
                            return;
                        }
                        setValue(input, normalizedRow?.[field] ?? '', '');
                    });
                    const markupType = node.querySelector('[data-field="markup_type"]')?.value || 'fixed';
                    const discountType = node.querySelector('[data-field="discount_type"]')?.value || 'fixed';
                    if (markupType === 'percent') {
                        const markupInput = node.querySelector('[data-field="markup"]');
                        if (markupInput) {
                            markupInput.value = String(Math.round(parsePercent(markupInput.value)));
                        }
                    }
                    if (discountType === 'percent') {
                        const discountInput = node.querySelector('[data-field="discount"]');
                        if (discountInput) {
                            discountInput.value = String(Math.round(parsePercent(discountInput.value)));
                        }
                    }
                    return node;
                };

                const renderItems = (items) => {
                    itemsContainer.innerHTML = '';
                    const list = Array.isArray(items) ? items : [];
                    list.forEach((row, index) => {
                        itemsContainer.appendChild(buildRow(index, row));
                    });
                    reindexItems();
                    regroupItemsByDay();
                    recalcTotals();
                    toggleItemsVisibility();
                };

                const convertExistingRowsFromIdrToDisplay = () => {
                    getAllRows().forEach((row) => {
                        const isManualRow = (row?.dataset?.rowMode || '') === 'manual';
                        if (isManualRow) {
                            const qty = Math.max(1, parseInteger(row.querySelector('[data-field="qty"]')?.value) || 1);
                            const rateInput = row.querySelector('[data-field="rate"]');
                            const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                            let perUnitDisplay = idrToDisplay(parseInteger(rateInput?.value));
                            if (perUnitDisplay <= 0) {
                                const fallbackTotalDisplay = idrToDisplay(parseInteger(unitPriceInput?.value));
                                perUnitDisplay = qty > 0 ? (fallbackTotalDisplay / qty) : fallbackTotalDisplay;
                            }
                            if (rateInput) setMoneyInputDisplay(rateInput, perUnitDisplay);
                            setMoneyInputDisplay(unitPriceInput, perUnitDisplay * qty);
                            return;
                        }

                        const isQuotationItemRow = (row?.dataset?.rowMode || '') === 'quotation_item';
                        if (isQuotationItemRow) {
                            const qty = Math.max(1, parseInteger(row.querySelector('[data-field="qty"]')?.value) || 1);
                            const rateInput = row.querySelector('[data-field="rate"]');
                            const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                            let perUnitDisplay = idrToDisplay(parseInteger(rateInput?.value));
                            if (perUnitDisplay <= 0) {
                                const fallbackTotalDisplay = idrToDisplay(parseInteger(unitPriceInput?.value));
                                perUnitDisplay = qty > 0 ? (fallbackTotalDisplay / qty) : fallbackTotalDisplay;
                            }
                            if (rateInput) setMoneyInputDisplay(rateInput, perUnitDisplay);
                            setMoneyInputDisplay(unitPriceInput, perUnitDisplay * qty);
                            return;
                        }

                        const contractInput = row.querySelector('[data-field="contract_rate"]');
                        const markupInput = row.querySelector('[data-field="markup"]');
                        const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                        const discountInput = row.querySelector('[data-field="discount"]');
                        const markupType = row.querySelector('[data-field="markup_type"]')?.value || 'fixed';
                        const discountType = row.querySelector('[data-field="discount_type"]')?.value || 'fixed';

                        setMoneyInputDisplay(contractInput, idrToDisplay(parseInteger(contractInput?.value)));
                        setMoneyInputDisplay(unitPriceInput, idrToDisplay(parseInteger(unitPriceInput?.value)));

                        if (markupType === 'percent') {
                            if (markupInput) markupInput.value = String(Math.round(parsePercent(markupInput.value)));
                        } else {
                            setMoneyInputDisplay(markupInput, idrToDisplay(parseInteger(markupInput?.value)));
                        }

                        if (discountType === 'percent') {
                            if (discountInput) discountInput.value = String(Math.round(parsePercent(discountInput.value)));
                        } else {
                            setMoneyInputDisplay(discountInput, idrToDisplay(parseInteger(discountInput?.value)));
                        }

                    });
                };

                const updateGenerateButtonState = () => {
                    if (!canUseItinerary) return;
                    const shouldDisable = itinerarySelect.value === '';
                    [generateBtn].filter(Boolean).forEach((buttonEl) => {
                        buttonEl.disabled = shouldDisable;
                        buttonEl.classList.toggle('opacity-60', shouldDisable);
                        buttonEl.classList.toggle('cursor-not-allowed', shouldDisable);
                    });
                };

                const setStatus = (message) => {
                    if (statusEl) statusEl.textContent = message || '';
                };

                const updateSummary = (message) => {
                    if (summaryEl) summaryEl.textContent = message || '';
                };
                const durationDaysInput = document.getElementById('quotation-duration-days');
                const durationNightsInput = document.getElementById('quotation-duration-nights');
                const syncServiceItemDayOptions = () => {
                    if (!serviceItemDaySelect || !durationDaysInput) return;
                    const days = Number.parseInt(String(durationDaysInput.value || '1'), 10);
                    const safeDays = Number.isFinite(days) && days > 0 ? days : 1;
                    const currentValue = String(serviceItemDaySelect.value || '1');
                    serviceItemDaySelect.innerHTML = '';
                    for (let day = 1; day <= safeDays; day++) {
                        const option = document.createElement('option');
                        option.value = String(day);
                        option.textContent = `Day ${day}`;
                        serviceItemDaySelect.appendChild(option);
                    }
                    const normalizedCurrent = Number.parseInt(currentValue, 10);
                    if (Number.isFinite(normalizedCurrent) && normalizedCurrent >= 1 && normalizedCurrent <= safeDays) {
                        serviceItemDaySelect.value = String(normalizedCurrent);
                    } else {
                        serviceItemDaySelect.value = '1';
                    }
                };

                const syncDurationNightsFromDays = () => {
                    if (!durationDaysInput || !durationNightsInput) return;
                    const days = Number.parseInt(String(durationDaysInput.value || '1'), 10);
                    const safeDays = Number.isFinite(days) && days > 0 ? days : 1;
                    durationDaysInput.value = String(safeDays);
                    durationNightsInput.value = String(Math.max(0, safeDays - 1));
                    syncServiceItemDayOptions();
                    regroupItemsByDay();
                    reindexItems();
                };

                const updateItineraryDurationDisplay = () => {
                    if (!itinerarySelect || !durationDaysInput) return;
                    const selectedOption = itinerarySelect.options[itinerarySelect.selectedIndex];
                    const days = Number.parseInt(String(selectedOption?.dataset?.durationDays || ''), 10);
                    if (!Number.isFinite(days) || days <= 0) {
                        syncDurationNightsFromDays();
                        return;
                    }
                    durationDaysInput.value = String(days);
                    syncDurationNightsFromDays();
                };

                const emitItinerarySelection = () => {
                    const selectedOption = itinerarySelect?.options[itinerarySelect.selectedIndex];
                    const selectedItineraryId = itinerarySelect?.value || '';
                    const itineraryInquiryId = selectedOption?.dataset?.inquiryId || '';
                    const detail = {
                        itineraryId: selectedItineraryId,
                        inquiryId: itineraryInquiryId,
                        inquiryNumber: selectedOption?.dataset?.inquiryNumber || '',
                    };
                    window.dispatchEvent(new CustomEvent('quotation:itinerary-selected', { detail }));
                };

                const syncItinerarySelectionContext = () => {
                    if (!itinerarySelect) return;
                    emitItinerarySelection();
                    updateItineraryDurationDisplay();
                };

                const syncDestinationFromSelectedItinerary = () => {
                    if (!destinationSelect || !itinerarySelect) return;
                    const option = itinerarySelect.options[itinerarySelect.selectedIndex];
                    const linkedDestinationId = String(option?.dataset?.destinationId || '').trim();
                    if (linkedDestinationId !== '' && String(destinationSelect.value || '').trim() !== linkedDestinationId) {
                        destinationSelect.value = linkedDestinationId;
                    }
                };

                const filterItinerariesByDestination = (preserveCurrent = true) => {
                    if (!itinerarySelect || !destinationSelect) return;
                    const selectedDestinationId = String(destinationSelect.value || '').trim();
                    const currentValue = String(itinerarySelect.value || '').trim();
                    const filtered = itineraryOptionDataset.filter((entry) => {
                        if (entry.isPlaceholder) return true;
                        return selectedDestinationId === '' || String(entry.destinationId || '') === selectedDestinationId;
                    });

                    itinerarySelect.innerHTML = '';
                    filtered.forEach((entry) => {
                        const option = document.createElement('option');
                        option.value = entry.value;
                        option.textContent = entry.text;
                        if (!entry.isPlaceholder) {
                            option.dataset.destinationId = entry.destinationId;
                            option.dataset.inquiryId = entry.inquiryId;
                            option.dataset.inquiryNumber = entry.inquiryNumber;
                            option.dataset.customerId = entry.customerId;
                            option.dataset.durationDays = entry.durationDays;
                            option.dataset.durationNights = entry.durationNights;
                        }
                        itinerarySelect.appendChild(option);
                    });

                    const hasCurrentValue = preserveCurrent && filtered.some((entry) => entry.value === currentValue && !entry.isPlaceholder);
                    if (hasCurrentValue) {
                        itinerarySelect.value = currentValue;
                    } else {
                        itinerarySelect.value = '';
                    }
                };

                const fetchItems = async () => {
                    if (!canUseItinerary || !itinerarySelect) return;
                    const itineraryId = itinerarySelect.value;
                    if (!itineraryId) {
                        setStatus(i18n.selectItineraryFirst);
                        return;
                    }
                    // Ensure duration fields always follow selected itinerary when generating quotation items.
                    updateItineraryDurationDisplay();
                    if (hasFilledItems()) {
                        if (!window.confirm(i18n.replaceExistingItemsConfirm)) {
                            return;
                        }
                    }

                    generateBtn.disabled = true;
                    syncItinerarySelectionContext();
                    setStatus(i18n.fetchingItems);
                    updateSummary('');
                    try {
                        const response = await fetch(`${endpoint}/${itineraryId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });
                        if (!response.ok) {
                            setStatus(i18n.fetchItemsFailed);
                            return;
                        }
                        const payload = await response.json();
                        const items = Array.isArray(payload?.items) ? payload.items : [];
                        renderItems(items);
                        updateItineraryDurationDisplay();
                        const missingCount = Number(payload?.meta?.missing_price_count || 0);
                        setStatus(i18n.itemsLoadedPattern.replace(':count', String(items.length)));
                        if (missingCount > 0) {
                            updateSummary(i18n.missingPricePattern.replace(':count', String(missingCount)));
                        }
                    } catch (err) {
                        setStatus(i18n.fetchItemsFailed);
                    } finally {
                        updateGenerateButtonState();
                    }
                };

                if (canUseItinerary) {
                    generateBtn.addEventListener('click', fetchItems);
                    itinerarySelect.addEventListener('change', () => {
                        syncDestinationFromSelectedItinerary();
                        filterItinerariesByDestination(true);
                        syncItinerarySelectionContext();
                        populateServiceItemOptions(false);
                        updateGenerateButtonState();
                        updateItineraryDurationDisplay();
                        syncServiceItemsMode();
                        if (itinerarySelect.value === '') {
                            updateSummary('');
                        }
                    });
                    destinationSelect?.addEventListener('change', () => {
                        filterItinerariesByDestination(false);
                        syncItinerarySelectionContext();
                        populateServiceItemOptions(false);
                        updateGenerateButtonState();
                        updateItineraryDurationDisplay();
                        syncServiceItemsMode();
                    });
                    filterItinerariesByDestination();
                    syncItinerarySelectionContext();
                    updateGenerateButtonState();
                    updateItineraryDurationDisplay();
                    syncServiceItemsMode();
                }
                customerSelect?.addEventListener('change', () => {
                    updateGenerateButtonState();
                    updateItineraryDurationDisplay();
                });
                inquirySelect?.addEventListener('change', () => {
                    const selectedOption = inquirySelect.options[inquirySelect.selectedIndex];
                    const linkedCustomerId = String(selectedOption?.dataset?.customerId || '').trim();
                    if (customerSelect && linkedCustomerId !== '') {
                        customerSelect.value = linkedCustomerId;
                    }
                    updateGenerateButtonState();
                    updateItineraryDurationDisplay();
                });

                const addManualItem = () => {
                    const node = manualItemsTemplate.content.firstElementChild.cloneNode(true);
                    node.querySelectorAll('[data-field]').forEach((input) => {
                        const field = input.dataset.field;
                        if (field === 'description') {
                            input.value = '';
                            return;
                        }
                        if (field === 'qty') {
                            input.value = '1';
                            return;
                        }
                        if (field === 'rate') {
                            input.value = '0';
                            return;
                        }
                        if (field === 'markup_type' || field === 'discount_type') {
                            input.value = 'fixed';
                            return;
                        }
                        if (field === 'itinerary_item_type') {
                            input.value = 'manual';
                            return;
                        }
                        input.value = '';
                    });
                    manualItemsContainer.appendChild(node);
                    reindexItems();
                    const firstInput = node.querySelector('[data-field="description"]');
                    if (firstInput) firstInput.focus();
                    setStatus(i18n.manualItemAdded);
                    recalcTotals();
                };

                addItemBtn?.addEventListener('click', addManualItem);

                const populateServiceItemOptions = (forceShow = false) => {
                    if (!serviceItemDropdown || !serviceItemTypeSelect) return;
                    const type = String(serviceItemTypeSelect.value || '').trim();
                    const config = serviceTypeConfig[type];
                    const selectedDestinationId = String(destinationSelect?.value || '').trim();
                    const keyword = String(serviceItemInput?.value || '').trim().toLowerCase();
                    const options = config ? (serviceCatalogs?.[config.key] || []) : [];
                    const filteredOptions = options.filter((item) => {
                        const itemDestinationId = String(item?.destination_id || '').trim();
                        const sameDestination = selectedDestinationId === '' || itemDestinationId === selectedDestinationId;
                        if (!sameDestination) return false;
                        if (keyword === '') return true;
                        return String(item?.label || '').toLowerCase().includes(keyword);
                    });
                    currentServiceItemOptions = filteredOptions;
                    serviceItemDropdown.innerHTML = '';

                    if (filteredOptions.length === 0) {
                        serviceItemDropdown.classList.add('hidden');
                        return;
                    }

                    filteredOptions.forEach((item) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 dark:text-gray-100 dark:hover:bg-indigo-900/30';
                        button.textContent = String(item.label || '-');
                        button.dataset.id = String(item.id || '');
                        button.dataset.label = String(item.label || '-');
                        button.dataset.descriptionLabel = String(item.description_label || item.label || '-');
                        button.dataset.vendorName = String(item.vendor_name || '');
                        button.dataset.vendorRegion = String(item.vendor_region || '');
                        button.dataset.rate = String(item.rate ?? 0);
                        button.dataset.contractRate = String(item.contract_rate ?? 0);
                        button.dataset.adultRate = String(item.adult_rate ?? item.rate ?? 0);
                        button.dataset.adultContractRate = String(item.adult_contract_rate ?? item.contract_rate ?? 0);
                        button.dataset.adultMarkupType = String(item.adult_markup_type ?? 'fixed');
                        button.dataset.adultMarkup = String(item.adult_markup ?? 0);
                        button.dataset.childRate = String(item.child_rate ?? item.rate ?? 0);
                        button.dataset.childContractRate = String(item.child_contract_rate ?? item.contract_rate ?? 0);
                        button.dataset.childMarkupType = String(item.child_markup_type ?? 'fixed');
                        button.dataset.childMarkup = String(item.child_markup ?? 0);
                        button.addEventListener('mousedown', (event) => {
                            event.preventDefault();
                            if (serviceItemInput) {
                                serviceItemInput.value = button.dataset.label || '';
                            }
                            serviceItemDropdown.classList.add('hidden');
                        });
                        serviceItemDropdown.appendChild(button);
                    });

                    const isInputFocused = document.activeElement === serviceItemInput;
                    const shouldShow = forceShow || isInputFocused || keyword !== '';
                    if (shouldShow) {
                        serviceItemDropdown.classList.remove('hidden');
                    } else {
                        serviceItemDropdown.classList.add('hidden');
                    }
                };

                const resolveSelectedServiceItem = () => {
                    if (!serviceItemInput) return null;
                    const typed = String(serviceItemInput.value || '').trim();
                    if (typed === '') return null;
                    const options = Array.isArray(currentServiceItemOptions) ? currentServiceItemOptions : [];
                    const exact = options.find((opt) => String(opt?.label || '').trim().toLowerCase() === typed.toLowerCase());
                    if (exact) return exact;
                    const partial = options.filter((opt) => String(opt?.label || '').trim().toLowerCase().includes(typed.toLowerCase()));
                    if (partial.length === 1) return partial[0];
                    if (partial.length > 1) return 'ambiguous';
                    return null;
                };

                const addServiceItem = () => {
                    if (!serviceItemTypeSelect || !serviceItemInput) return;
                    const type = String(serviceItemTypeSelect.value || '').trim();
                    if (!type || !serviceTypeConfig[type]) {
                        setStatus(i18n.selectServiceTypeFirst);
                        return;
                    }
                    const selectedOption = resolveSelectedServiceItem();
                    if (selectedOption === 'ambiguous') {
                        setStatus(i18n.serviceItemAmbiguous);
                        return;
                    }
                    if (!selectedOption) {
                        setStatus(i18n.selectServiceItemFirst);
                        return;
                    }
                    const selectedId = String(selectedOption?.dataset?.id || '').trim();
                    const normalizedSelectedId = selectedId !== '' ? selectedId : String(selectedOption?.id || '');
                    if (!normalizedSelectedId) {
                        setStatus(i18n.serviceItemNotFound);
                        return;
                    }

                    const qty = Math.max(1, parseInteger(serviceItemQtyInput?.value) || 1);
                    const config = serviceTypeConfig[type];
                    const label = String(selectedOption?.label || selectedOption?.value || '-').trim();
                    const selectedPaxType = config?.supportsPaxType
                        ? String(serviceItemPaxTypeSelect?.value || 'adult').trim().toLowerCase()
                        : '';
                    const paxSuffix = selectedPaxType === 'child'
                        ? ` (${i18n.childLabel})`
                        : (selectedPaxType === 'adult' ? ` (${i18n.adultLabel})` : '');
                    const vendorName = String(selectedOption?.vendor_name ?? selectedOption?.dataset?.vendorName ?? '').trim();
                    const vendorRegion = String(selectedOption?.vendor_region ?? selectedOption?.dataset?.vendorRegion ?? '').trim();
                    const baseServiceName = String(selectedOption?.label || selectedOption?.value || '-')
                        .split(' - ')[0]
                        .trim() || '-';
                    const descriptionBase = String(selectedOption?.description_label || `${config.labelPrefix}${baseServiceName}`).trim();
                    const description = config?.supportsPaxType
                        ? [
                            `${config.labelPrefix}${baseServiceName}${paxSuffix}`,
                            vendorName,
                            vendorRegion,
                        ].filter((part) => String(part || '').trim() !== '').join(' - ')
                        : descriptionBase;
                    const rateKey = selectedPaxType === 'child' ? 'child_rate' : 'adult_rate';
                    const contractRateKey = selectedPaxType === 'child' ? 'child_contract_rate' : 'adult_contract_rate';
                    const markupTypeKey = selectedPaxType === 'child' ? 'child_markup_type' : 'adult_markup_type';
                    const markupKey = selectedPaxType === 'child' ? 'child_markup' : 'adult_markup';
                    const rawRate = config?.supportsPaxType
                        ? (selectedOption?.[rateKey] ?? selectedOption?.dataset?.[selectedPaxType === 'child' ? 'childRate' : 'adultRate'] ?? selectedOption?.rate ?? '0')
                        : (selectedOption?.rate ?? selectedOption?.dataset?.rate ?? '0');
                    const rawContractRate = config?.supportsPaxType
                        ? (selectedOption?.[contractRateKey] ?? selectedOption?.dataset?.[selectedPaxType === 'child' ? 'childContractRate' : 'adultContractRate'] ?? selectedOption?.contract_rate ?? '0')
                        : (selectedOption?.contract_rate ?? selectedOption?.dataset?.contractRate ?? '0');
                    const rawMarkupType = config?.supportsPaxType
                        ? (selectedOption?.[markupTypeKey] ?? selectedOption?.dataset?.[selectedPaxType === 'child' ? 'childMarkupType' : 'adultMarkupType'] ?? 'fixed')
                        : 'fixed';
                    const rawMarkup = config?.supportsPaxType
                        ? (selectedOption?.[markupKey] ?? selectedOption?.dataset?.[selectedPaxType === 'child' ? 'childMarkup' : 'adultMarkup'] ?? '0')
                        : '0';
                    const rate = Math.max(0, Number.parseFloat(String(rawRate)) || 0);
                    const contractRate = Math.max(0, Number.parseFloat(String(rawContractRate)) || 0);
                    const markup = Math.max(0, Number.parseFloat(String(rawMarkup)) || 0);
                    const markupType = String(rawMarkupType || 'fixed') === 'percent' ? 'percent' : 'fixed';
                    const node = buildRow(getAllRows().length, {
                        description,
                        qty,
                        rate,
                        unit_price: rate,
                        contract_rate: contractRate,
                        markup_type: markupType,
                        markup,
                        discount_type: 'fixed',
                        discount: 0,
                        day_number: Number.parseInt(String(serviceItemDaySelect?.value || '1'), 10) || 1,
                        serviceable_type: config.serviceableType,
                        serviceable_id: Number.parseInt(normalizedSelectedId, 10) || null,
                        serviceable_meta: config?.supportsPaxType
                            ? {
                                pax_type: selectedPaxType || 'adult',
                                vendor_name: vendorName,
                                vendor_region: vendorRegion,
                            }
                            : {},
                        itinerary_item_type: config.itineraryItemType,
                    });
                    itemsContainer.appendChild(node);
                    reindexItems();
                    regroupItemsByDay();
                    recalcTotals();
                    toggleItemsVisibility();
                    serviceItemInput.value = '';
                    populateServiceItemOptions(false);
                    setStatus(i18n.serviceItemAdded);
                };

                const convertFieldDisplayToIdr = (inputEl) => {
                    if (!inputEl) return;
                    const displayValue = parseInteger(inputEl.value);
                    const idrValue = Math.max(0, Math.round(displayToIdr(displayValue)));
                    inputEl.value = String(idrValue);
                };

                formEl?.addEventListener('submit', () => {
                    reindexItems();
                    getAllRows().forEach((row) => {
                        const rowMode = (row?.dataset?.rowMode || '');
                        const isRateBasedRow = rowMode === 'manual' || rowMode === 'quotation_item';
                        const markupType = row.querySelector('[data-field="markup_type"]')?.value || 'fixed';
                        const discountType = row.querySelector('[data-field="discount_type"]')?.value || 'fixed';
                        const qty = Math.max(1, parseInteger(row.querySelector('[data-field="qty"]')?.value) || 1);

                        convertFieldDisplayToIdr(row.querySelector('[data-field="contract_rate"]'));
                        const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                        if (unitPriceInput) {
                            if (isRateBasedRow) {
                                row.querySelector('[data-field="contract_rate"]').value = '';
                                row.querySelector('[data-field="markup"]').value = '0';
                                row.querySelector('[data-field="discount"]').value = '0';
                                convertFieldDisplayToIdr(row.querySelector('[data-field="rate"]'));
                                const rateIdr = parseInteger(row.querySelector('[data-field="rate"]')?.value);
                                unitPriceInput.value = String(rateIdr);
                            } else {
                                const baseAmountDisplay = Math.max(0, computeRowBaseAmount(row));
                                const baseUnitDisplay = Math.round(baseAmountDisplay / qty);
                                const baseUnitIdr = Math.max(0, Math.round(displayToIdr(baseUnitDisplay)));
                                unitPriceInput.value = String(baseUnitIdr);
                            }
                        }
                        if (markupType !== 'percent') {
                            convertFieldDisplayToIdr(row.querySelector('[data-field="markup"]'));
                        }
                        if (discountType !== 'percent') {
                            convertFieldDisplayToIdr(row.querySelector('[data-field="discount"]'));
                        }
                    });

                    convertFieldDisplayToIdr(itemDiscountTotalInput);
                    convertFieldDisplayToIdr(subTotalInput);
                    convertFieldDisplayToIdr(discountAmountInput);
                    convertFieldDisplayToIdr(finalAmountInput);
                });

                const bindRowEvents = (container) => {
                    container?.addEventListener('input', (event) => {
                        if (event.target.matches('[data-field="qty"], [data-field="rate"], [data-field="contract_rate"], [data-field="markup"], [data-field="discount"], [data-field="unit_price"]')) {
                            scheduleRecalcTotals();
                        }
                    });
                };

                bindRowEvents(itemsContainer);
                bindRowEvents(manualItemsContainer);
                manualItemsContainer?.addEventListener('click', (event) => {
                    const removeBtn = event.target.closest('[data-remove-manual-item="1"]');
                    if (!removeBtn) return;
                    const row = removeBtn.closest('.quotation-manual-row');
                    if (!row) return;
                    row.remove();
                    reindexItems();
                    recalcTotals();
                });
                itemsContainer?.addEventListener('click', (event) => {
                    const removeBtn = event.target.closest('[data-remove-service-item="1"]');
                    if (!removeBtn) return;
                    const row = removeBtn.closest('.quotation-item-row');
                    if (!row) return;
                    row.remove();
                    reindexItems();
                    regroupItemsByDay();
                    recalcTotals();
                    toggleItemsVisibility();
                });
                discountValueInput?.addEventListener('input', scheduleRecalcTotals);
                serviceItemTypeSelect?.addEventListener('change', () => {
                    syncServiceItemPaxTypeVisibility();
                    populateServiceItemOptions(false);
                });
                serviceItemInput?.addEventListener('focus', () => populateServiceItemOptions(true));
                serviceItemInput?.addEventListener('input', () => populateServiceItemOptions(true));
                serviceItemPaxTypeSelect?.addEventListener('change', () => {
                    if (String(serviceItemTypeSelect?.value || '').trim() === 'fnb') {
                        populateServiceItemOptions(true);
                    }
                });
                serviceItemInput?.addEventListener('blur', () => {
                    setTimeout(() => {
                        serviceItemDropdown?.classList.add('hidden');
                    }, 150);
                });
                serviceItemAddBtn?.addEventListener('click', addServiceItem);
                syncServiceItemPaxTypeVisibility();
                destinationSelect?.addEventListener('change', () => populateServiceItemOptions(false));
                populateServiceItemOptions(false);
                syncServiceItemsMode();
                durationDaysInput?.addEventListener('input', syncDurationNightsFromDays);
                durationDaysInput?.addEventListener('change', syncDurationNightsFromDays);
                syncDurationNightsFromDays();
                syncServiceItemDayOptions();

                convertExistingRowsFromIdrToDisplay();
                regroupItemsByDay();
                recalcTotals();
                toggleItemsVisibility();

                if (canUseItinerary && itinerarySelect.value && !hasFilledItems()) {
                    fetchItems();
                }
            })();
        </script>
    @endpush
@endonce
