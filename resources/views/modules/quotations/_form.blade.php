@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $itineraries = $itineraries ?? collect();
    $prefillItineraryId = $prefillItineraryId ?? null;
    $showStatus = $showStatus ?? true;
@endphp

@php
    $items = old('items', isset($quotation) ? $quotation->items->map(function ($item) {
        return [
            'description' => $item->description,
            'qty' => $item->qty,
            'contract_rate' => $item->contract_rate,
            'markup_type' => $item->markup_type ?? 'fixed',
            'markup' => $item->markup,
            'unit_price' => $item->unit_price,
            'discount_type' => $item->discount_type ?? 'fixed',
            'discount' => $item->discount,
            'serviceable_type' => $item->serviceable_type,
            'serviceable_id' => $item->serviceable_id,
            'day_number' => $item->day_number,
            'serviceable_meta' => $item->serviceable_meta,
            'itinerary_item_type' => $item->itinerary_item_type,
        ];
    })->toArray() : []);
    $items = array_values($items);
    $minRows = 0;
    $hasItems = collect($items)
        ->filter(fn ($row) => trim((string) ($row['description'] ?? '')) !== '')
        ->isNotEmpty();
@endphp

<div class="space-y-5 module-form">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Itinerary</label>
            <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
                <select
                    id="itinerary-select"
                    name="itinerary_id"
                    class="app-input"
                    data-endpoint="{{ url('quotations/itinerary-items') }}"
                    required
                >
                    <option value="">Select itinerary</option>
                    @foreach ($itineraries as $itinerary)
                        <option
                            value="{{ $itinerary->id }}"
                            data-inquiry-id="{{ $itinerary->inquiry_id ?? '' }}"
                            @selected((string) old('itinerary_id', $quotation->itinerary_id ?? $prefillItineraryId ?? '') === (string) $itinerary->id)
                        >
                            {{ $itinerary->title }}
                            @if (!empty($itinerary->destination))
                                | {{ $itinerary->destination }}
                            @endif
                            @if (!empty($itinerary->inquiry?->inquiry_number))
                                | {{ $itinerary->inquiry?->inquiry_number }}
                            @endif
                            @if (!empty($itinerary->inquiry?->customer?->name))
                                - {{ $itinerary->inquiry?->customer?->name }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <button
                    type="button"
                    id="itinerary-generate-btn"
                    class="btn-outline-sm min-h-[42px] w-full sm:w-auto"
                >
                    Generate
                </button>
            </div>
            <p id="itinerary-generate-status" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Klik generate untuk mengisi item dari itinerary.
            </p>
            @error('itinerary_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
            @if ($itineraries->isEmpty())
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    Belum ada itinerary aktif yang siap dipakai untuk quotation.
                </p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @if ($showStatus)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                <select name="status" class="mt-1 app-input" required>
                @foreach (\App\Models\Quotation::STATUS_OPTIONS as $status)
                        <option value="{{ $status }}" @selected(old('status', $quotation->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>
        @elseif (isset($quotation))
            <input type="hidden" name="status" value="{{ old('status', $quotation->status ?? 'draft') }}">
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Validity Date</label>
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
                Mohon cek kembali item quotation. Pastikan Description, Qty, dan Unit Price sudah terisi benar.
            </div>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Items</p>
            <span id="itinerary-items-summary" class="text-xs text-gray-500 dark:text-gray-400"></span>
        </div>
        <div id="quotation-items" class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
            @for ($i = 0; $i < max($minRows, count($items)); $i++)
                @php
                    $row = $items[$i] ?? ['description' => '', 'qty' => 1, 'contract_rate' => 0, 'markup_type' => 'fixed', 'markup' => 0, 'unit_price' => 0, 'discount_type' => 'fixed', 'discount' => 0];
                    $serviceableMetaValue = $row['serviceable_meta'] ?? '';
                    $serviceableMetaArray = [];
                    if (is_array($serviceableMetaValue)) {
                        $serviceableMetaArray = $serviceableMetaValue;
                    } elseif (is_string($serviceableMetaValue) && $serviceableMetaValue !== '') {
                        $decodedMeta = json_decode($serviceableMetaValue, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedMeta)) {
                            $serviceableMetaArray = $decodedMeta;
                        }
                    }
                    $paxType = strtolower((string) ($serviceableMetaArray['pax_type'] ?? ''));
                    $paxBadgeLabel = $paxType === 'adult' ? 'Adult Publish Rate' : ($paxType === 'child' ? 'Child Publish Rate' : '');
                    if (is_array($serviceableMetaValue)) {
                        $serviceableMetaValue = json_encode($serviceableMetaValue);
                    }
                @endphp
                <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-9 quotation-item-row">
                    <div class="sm:col-span-2">
                        <label class="quotation-item-label block text-xs text-gray-500">Description</label>
                        <div
                            data-role="description-text"
                            class="quotation-item-control flex min-h-[42px] items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        >
                            {{ $row['description'] ?? '-' }}
                        </div>
                        <input type="hidden" data-field="description" name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}">
                        <span
                            data-field="pax_type_badge"
                            class="{{ $paxBadgeLabel !== '' ? 'inline-flex' : 'hidden' }} mt-1 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $paxType === 'child' ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }}"
                        >
                            {{ $paxBadgeLabel }}
                        </span>
                    </div>
                    <div>
                        <label class="quotation-item-label block text-xs text-gray-500">Qty</label>
                        <input data-field="qty" name="items[{{ $i }}][qty]" type="number" min="1" value="{{ $row['qty'] ?? 1 }}" class="quotation-item-control dark:border-gray-600 app-input" required>
                    </div>
                    <div>
                        <x-money-input
                            label="Contract Rate"
                            label-class="quotation-item-label block text-xs text-gray-500"
                            wrapper-class="quotation-item-money-field"
                            name="items[{{ $i }}][contract_rate]"
                            :value="$row['contract_rate'] ?? 0"
                            data-field="contract_rate"
                            input-class="quotation-item-control"
                            step="0.01"
                            readonly
                            compact
                        />
                    </div>
                    <div>
                        <label class="quotation-item-label block text-xs text-gray-500">Markup Type</label>
                        <select data-field="markup_type" name="items[{{ $i }}][markup_type]" class="quotation-item-control dark:border-gray-600 app-input">
                            <option value="fixed" @selected(($row['markup_type'] ?? 'fixed') === 'fixed')>Fixed</option>
                            <option value="percent" @selected(($row['markup_type'] ?? '') === 'percent')>Percent</option>
                        </select>
                    </div>
                    <div>
                        <x-money-input
                            label="Markup"
                            label-class="quotation-item-label block text-xs text-gray-500"
                            wrapper-class="quotation-item-money-field"
                            name="items[{{ $i }}][markup]"
                            :value="$row['markup'] ?? 0"
                            data-field="markup"
                            input-class="quotation-item-control"
                            step="0.01"
                            compact
                        />
                    </div>
                    <div>
                        <x-money-input
                            label="Unit Price"
                            label-class="quotation-item-label block text-xs text-gray-500"
                            wrapper-class="quotation-item-money-field"
                            name="items[{{ $i }}][unit_price]"
                            :value="$row['unit_price'] ?? 0"
                            data-field="unit_price"
                            input-class="quotation-item-control"
                            step="0.01"
                            required
                            readonly
                            compact
                        />
                    </div>
                    <div>
                        <label class="quotation-item-label block text-xs text-gray-500">Discount Type</label>
                        <select data-field="discount_type" name="items[{{ $i }}][discount_type]" class="quotation-item-control dark:border-gray-600 app-input">
                            <option value="fixed" @selected(($row['discount_type'] ?? 'fixed') === 'fixed')>Fixed</option>
                            <option value="percent" @selected(($row['discount_type'] ?? '') === 'percent')>Percent</option>
                        </select>
                    </div>
                    <div>
                        <x-money-input
                            label="Discount"
                            label-class="quotation-item-label block text-xs text-gray-500"
                            wrapper-class="quotation-item-money-field"
                            name="items[{{ $i }}][discount]"
                            :value="$row['discount'] ?? 0"
                            data-field="discount"
                            input-class="quotation-item-control"
                            step="0.01"
                            compact
                        />
                    </div>
                    <input type="hidden" data-field="serviceable_type" name="items[{{ $i }}][serviceable_type]" value="{{ $row['serviceable_type'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="serviceable_id" name="items[{{ $i }}][serviceable_id]" value="{{ $row['serviceable_id'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="day_number" name="items[{{ $i }}][day_number]" value="{{ $row['day_number'] ?? '' }}" class="app-input">
                    <input type="hidden" data-field="serviceable_meta" name="items[{{ $i }}][serviceable_meta]" value="{{ $serviceableMetaValue }}" class="app-input">
                    <input type="hidden" data-field="itinerary_item_type" name="items[{{ $i }}][itinerary_item_type]" value="{{ $row['itinerary_item_type'] ?? '' }}" class="app-input">
                </div>
            @endfor
        </div>
        <template id="quotation-item-row-template">
            <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-9 quotation-item-row">
                <div class="sm:col-span-2">
                    <label class="quotation-item-label block text-xs text-gray-500">Description</label>
                    <div
                        data-role="description-text"
                        class="quotation-item-control flex min-h-[42px] items-center rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >-</div>
                    <input type="hidden" data-field="description">
                    <span data-field="pax_type_badge" class="hidden mt-1 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"></span>
                </div>
                <div>
                    <label class="quotation-item-label block text-xs text-gray-500">Qty</label>
                    <input data-field="qty" type="number" min="1" class="quotation-item-control dark:border-gray-600 app-input" required>
                </div>
                <div>
                    <x-money-input
                        label="Contract Rate"
                        label-class="quotation-item-label block text-xs text-gray-500"
                        wrapper-class="quotation-item-money-field"
                        data-field="contract_rate"
                        input-class="quotation-item-control"
                        step="0.01"
                        readonly
                        compact
                    />
                </div>
                <div>
                    <label class="quotation-item-label block text-xs text-gray-500">Markup Type</label>
                    <select data-field="markup_type" class="quotation-item-control dark:border-gray-600 app-input">
                        <option value="fixed">Fixed</option>
                        <option value="percent">Percent</option>
                    </select>
                </div>
                <div>
                    <x-money-input
                        label="Markup"
                        label-class="quotation-item-label block text-xs text-gray-500"
                        wrapper-class="quotation-item-money-field"
                        data-field="markup"
                        input-class="quotation-item-control"
                        step="0.01"
                        compact
                    />
                </div>
                <div>
                    <x-money-input
                        label="Unit Price"
                        label-class="quotation-item-label block text-xs text-gray-500"
                        wrapper-class="quotation-item-money-field"
                        data-field="unit_price"
                        input-class="quotation-item-control"
                        step="0.01"
                        required
                        readonly
                        compact
                    />
                </div>
                <div>
                    <label class="quotation-item-label block text-xs text-gray-500">Discount Type</label>
                    <select data-field="discount_type" class="quotation-item-control dark:border-gray-600 app-input">
                        <option value="fixed">Fixed</option>
                        <option value="percent">Percent</option>
                    </select>
                </div>
                <div>
                    <x-money-input
                        label="Discount"
                        label-class="quotation-item-label block text-xs text-gray-500"
                        wrapper-class="quotation-item-money-field"
                        data-field="discount"
                        input-class="quotation-item-control"
                        step="0.01"
                        compact
                    />
                </div>
                <input type="hidden" data-field="serviceable_type" class="app-input">
                <input type="hidden" data-field="serviceable_id" class="app-input">
                <input type="hidden" data-field="day_number" class="app-input">
                <input type="hidden" data-field="serviceable_meta" class="app-input">
                <input type="hidden" data-field="itinerary_item_type" class="app-input">
            </div>
        </template>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Discount Type</label>
            <select name="discount_type" class="mt-1 app-input">
                <option value="">-</option>
                <option value="percent" @selected(old('discount_type', $quotation->discount_type ?? '') === 'percent')>Percent</option>
                <option value="fixed" @selected(old('discount_type', $quotation->discount_type ?? '') === 'fixed')>Fixed</option>
            </select>
            @error('discount_type')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <x-money-input
                label="Discount Value"
                name="discount_value"
                :value="old('discount_value', $quotation->discount_value ?? 0)"
                step="0.01"
            />
            @error('discount_value')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <x-money-input
                label="Discount Amount (Auto)"
                id="quotation-discount-amount"
                step="0.01"
                value="0"
                readonly
            />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-money-input
                label="Item Discount (Auto)"
                id="quotation-item-discount-total"
                step="0.01"
                value="0"
                readonly
            />
        </div>
        <div>
            <x-money-input
                label="Sub Total (Auto)"
                id="quotation-sub-total"
                step="0.01"
                :value="old('sub_total', $quotation->sub_total ?? 0)"
                readonly
            />
        </div>
        <div>
            <x-money-input
                label="Final Amount (Auto)"
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
        <button type="submit"  class="btn-primary">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('quotations.index') }}"  class="btn-secondary">
            Cancel
        </a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function() {
                const itemsContainer = document.getElementById('quotation-items');
                const itemsTemplate = document.getElementById('quotation-item-row-template');
                if (!itemsContainer || !itemsTemplate) return;

                const itinerarySelect = document.getElementById('itinerary-select');
                const generateBtn = document.getElementById('itinerary-generate-btn');
                const statusEl = document.getElementById('itinerary-generate-status');
                const summaryEl = document.getElementById('itinerary-items-summary');
                const itemsSection = document.getElementById('quotation-items-section');
                const itemDiscountTotalInput = document.getElementById('quotation-item-discount-total');
                const subTotalInput = document.getElementById('quotation-sub-total');
                const discountAmountInput = document.getElementById('quotation-discount-amount');
                const finalAmountInput = document.getElementById('quotation-final-amount');
                const discountTypeSelect = document.querySelector('select[name="discount_type"]');
                const discountValueInput = document.querySelector('input[name="discount_value"]');

                const endpoint = itinerarySelect ? (itinerarySelect.dataset.endpoint || '') : '';
                const canUseItinerary = Boolean(itinerarySelect && generateBtn);
                const currencyCode = String(window.appCurrency || 'IDR').toUpperCase();
                const rateToIdr = Number(window.appCurrencyRateToIdr || 1);
                const formEl = itemsContainer.closest('form');

                const parseInteger = (value) => {
                    const digits = String(value ?? '').replace(/[^\d]/g, '');
                    if (digits === '') return 0;
                    const num = Number.parseInt(digits, 10);
                    return Number.isFinite(num) ? num : 0;
                };

                const parsePercent = (value) => {
                    const normalized = String(value ?? '')
                        .replace(/[^\d,.-]/g, '')
                        .replace(/\./g, '')
                        .replace(',', '.');
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
                    input.value = formatMoneyDisplay(value);
                };

                const setBadge = (input, text) => {
                    if (!input) return;
                    const badge = input.parentElement?.querySelector('[data-money-badge="1"]');
                    if (badge) {
                        badge.textContent = text;
                    }
                };

                const updateRowDiscountBadge = (row) => {
                    const currency = window.appCurrencySymbol || window.appCurrency || 'IDR';
                    const discountType = (row.querySelector('[data-field="discount_type"]')?.value || 'fixed');
                    const discountInput = row.querySelector('[data-field="discount"]');
                    setBadge(discountInput, discountType === 'percent' ? '%' : currency);
                };

                const updateRowMarkupBadge = (row) => {
                    const currency = window.appCurrencySymbol || window.appCurrency || 'IDR';
                    const markupType = (row.querySelector('[data-field="markup_type"]')?.value || 'fixed');
                    const markupInput = row.querySelector('[data-field="markup"]');
                    setBadge(markupInput, markupType === 'percent' ? '%' : currency);
                };

                const updateOverallDiscountBadge = () => {
                    const currency = window.appCurrencySymbol || window.appCurrency || 'IDR';
                    const type = discountTypeSelect?.value || '';
                    const badgeText = type === 'percent' ? '%' : currency;
                    setBadge(discountValueInput, badgeText);
                };

                const convertDiscountValue = (row, fromType, toType) => {
                    if (!row || fromType === toType) return;
                    const qty = parseInteger(row.querySelector('[data-field="qty"]')?.value);
                    const unitPrice = parseInteger(row.querySelector('[data-field="unit_price"]')?.value);
                    const total = Math.max(0, qty * unitPrice);
                    const discountInput = row.querySelector('[data-field="discount"]');
                    if (!discountInput) return;
                    let value = fromType === 'percent'
                        ? parsePercent(discountInput.value)
                        : parseInteger(discountInput.value);

                    if (fromType === 'percent' && toType === 'fixed') {
                        value = total * (value / 100);
                        setMoneyInputDisplay(discountInput, value);
                    } else if (fromType === 'fixed' && toType === 'percent') {
                        if (total <= 0) {
                            discountInput.value = '0';
                        } else {
                            value = Math.min(100, (value / total) * 100);
                            discountInput.value = String(Math.round(value));
                        }
                    }
                };

                const convertMarkupValue = (row, fromType, toType) => {
                    if (!row || fromType === toType) return;
                    const contractRate = parseInteger(row.querySelector('[data-field="contract_rate"]')?.value);
                    const markupInput = row.querySelector('[data-field="markup"]');
                    if (!markupInput) return;
                    let value = fromType === 'percent'
                        ? parsePercent(markupInput.value)
                        : parseInteger(markupInput.value);

                    if (fromType === 'percent' && toType === 'fixed') {
                        value = contractRate * (value / 100);
                        setMoneyInputDisplay(markupInput, value);
                    } else if (fromType === 'fixed' && toType === 'percent') {
                        if (contractRate <= 0) {
                            markupInput.value = '0';
                        } else {
                            value = Math.min(100, (value / contractRate) * 100);
                            markupInput.value = String(Math.round(value));
                        }
                    }
                };

                const computeRowUnitPrice = (row) => {
                    const contractRate = parseInteger(row.querySelector('[data-field="contract_rate"]')?.value);
                    const markupType = (row.querySelector('[data-field="markup_type"]')?.value || 'fixed');
                    let markup = markupType === 'percent'
                        ? parsePercent(row.querySelector('[data-field="markup"]')?.value)
                        : parseInteger(row.querySelector('[data-field="markup"]')?.value);
                    if (markupType === 'percent' && markup > 100) {
                        markup = 100;
                        const markupInput = row.querySelector('[data-field="markup"]');
                        if (markupInput) markupInput.value = '100';
                    }

                    const unitPrice = markupType === 'percent'
                        ? contractRate + (contractRate * (markup / 100))
                        : contractRate + markup;

                    const unitPriceInput = row.querySelector('[data-field="unit_price"]');
                    if (unitPriceInput) {
                        setMoneyInputDisplay(unitPriceInput, unitPrice);
                    }

                    return Math.max(0, unitPrice);
                };

                const recalcTotals = () => {
                    let subTotal = 0;
                    let itemDiscountTotal = 0;
                    itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => {
                        updateRowDiscountBadge(row);
                        updateRowMarkupBadge(row);
                        const qty = parseInteger(row.querySelector('[data-field="qty"]')?.value);
                        const unitPrice = computeRowUnitPrice(row);
                        const discountInput = row.querySelector('[data-field="discount"]');
                        const discountType = (row.querySelector('[data-field="discount_type"]')?.value || 'fixed');
                        let discount = discountType === 'percent'
                            ? parsePercent(discountInput?.value)
                            : parseInteger(discountInput?.value);
                        if (discountType === 'percent' && discount > 100) {
                            discount = 100;
                            if (discountInput) discountInput.value = '100';
                        }
                        const rowDiscount = discountType === 'percent'
                            ? ((qty * unitPrice) * (discount / 100))
                            : discount;
                        itemDiscountTotal += rowDiscount;
                        const rowTotal = Math.max(0, (qty * unitPrice) - rowDiscount);
                        subTotal += rowTotal;

                    });

                    const discountType = discountTypeSelect?.value || '';
                    const discountValue = discountType === 'percent'
                        ? parsePercent(discountValueInput?.value)
                        : parseInteger(discountValueInput?.value);
                    let discountAmount = 0;
                    if (discountType === 'percent') {
                        discountAmount = subTotal * (discountValue / 100);
                    } else if (discountType === 'fixed') {
                        discountAmount = discountValue;
                    }

                    const finalAmount = Math.max(0, subTotal - discountAmount);

                    if (itemDiscountTotalInput) setMoneyInputDisplay(itemDiscountTotalInput, itemDiscountTotal);
                    if (subTotalInput) setMoneyInputDisplay(subTotalInput, subTotal);
                    if (discountAmountInput) setMoneyInputDisplay(discountAmountInput, discountAmount);
                    if (finalAmountInput) setMoneyInputDisplay(finalAmountInput, finalAmount);

                    updateOverallDiscountBadge();
                };

                const hasFilledItems = () => {
                    return Array.from(itemsContainer.querySelectorAll('[data-field="description"]'))
                        .some((input) => String(input.value || '').trim() !== '');
                };

                const toggleItemsVisibility = () => {
                    if (!itemsSection) return;
                    itemsSection.classList.toggle('hidden', !hasFilledItems());
                };

                const reindexItems = () => {
                    const rows = itemsContainer.querySelectorAll('.quotation-item-row');
                    rows.forEach((row, index) => {
                        row.querySelectorAll('[data-field]').forEach((input) => {
                            const field = input.dataset.field;
                            input.name = `items[${index}][${field}]`;
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

                const descriptionForDisplay = (value) => {
                    const text = String(value || '').trim();
                    if (text === '') return '-';
                    const cleaned = text.replace(/^day\s+\d+\s*[:\-]\s*/i, '').trim();
                    return cleaned !== '' ? cleaned : '-';
                };

                const regroupItemsByDay = () => {
                    const rows = Array.from(itemsContainer.querySelectorAll('.quotation-item-row'));
                    if (rows.length === 0) {
                        itemsContainer.innerHTML = '';
                        return;
                    }

                    const groups = new Map();
                    rows.forEach((row) => {
                        const key = String(row.querySelector('[data-field="day_number"]')?.value || '');
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

                        const body = document.createElement('div');
                        body.className = 'divide-y divide-gray-200 dark:divide-gray-700';
                        groupRows.forEach((row) => body.appendChild(row));
                        card.appendChild(body);

                        itemsContainer.appendChild(card);
                    });
                };

                const parseMetaValue = (value) => {
                    if (value && typeof value === 'object') {
                        return value;
                    }
                    const raw = String(value ?? '').trim();
                    if (!raw) return null;
                    try {
                        const parsed = JSON.parse(raw);
                        return parsed && typeof parsed === 'object' ? parsed : null;
                    } catch (e) {
                        return null;
                    }
                };

                const applyPaxTypeBadge = (rowEl, sourceRow = null) => {
                    if (!rowEl) return;
                    const badgeEl = rowEl.querySelector('[data-field="pax_type_badge"]');
                    if (!badgeEl) return;

                    const metaFromSource = sourceRow?.serviceable_meta ?? null;
                    const metaInputValue = rowEl.querySelector('[data-field="serviceable_meta"]')?.value ?? null;
                    const meta = parseMetaValue(metaFromSource) || parseMetaValue(metaInputValue);
                    const paxType = String(meta?.pax_type ?? '').toLowerCase();

                    if (paxType !== 'adult' && paxType !== 'child') {
                        badgeEl.textContent = '';
                        badgeEl.className = 'hidden mt-1 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide';
                        return;
                    }

                    const isChild = paxType === 'child';
                    badgeEl.textContent = isChild ? 'Child Publish Rate' : 'Adult Publish Rate';
                    badgeEl.className = `inline-flex mt-1 items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${
                        isChild
                            ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                            : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                    }`;
                };

                const buildRow = (index, row) => {
                    const node = itemsTemplate.content.firstElementChild.cloneNode(true);
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
                        if (field === 'contract_rate' || field === 'markup' || field === 'unit_price' || field === 'discount') {
                            const val = Number(row?.[field]);
                            const idrValue = Number.isFinite(val) ? val : 0;
                            const displayValue = idrToDisplay(idrValue);
                            setValue(input, formatMoneyDisplay(displayValue), formatMoneyDisplay(0));
                            return;
                        }
                        if (field === 'markup_type') {
                            setValue(input, row?.markup_type ?? 'fixed', 'fixed');
                            return;
                        }
                        if (field === 'discount_type') {
                            setValue(input, row?.discount_type ?? 'fixed', 'fixed');
                            return;
                        }
                        if (field === 'day_number') {
                            const day = Number(row?.day_number);
                            setValue(input, Number.isFinite(day) && day > 0 ? day : '', '');
                            return;
                        }
                        if (field === 'serviceable_meta') {
                            const meta = row?.serviceable_meta ?? '';
                            if (meta && typeof meta === 'object') {
                                setValue(input, JSON.stringify(meta), '');
                            } else {
                                setValue(input, meta ?? '', '');
                            }
                            return;
                        }
                        if (field === 'itinerary_item_type') {
                            setValue(input, row?.itinerary_item_type ?? '', '');
                            return;
                        }
                        setValue(input, row?.[field] ?? '', '');
                    });
                    const descriptionInput = node.querySelector('[data-field="description"]');
                    const descriptionText = node.querySelector('[data-role="description-text"]');
                    if (descriptionText) {
                        descriptionText.textContent = descriptionForDisplay(descriptionInput?.value);
                    }
                    applyPaxTypeBadge(node, row);
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
                    itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => {
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
                    generateBtn.disabled = itinerarySelect.value === '';
                    generateBtn.classList.toggle('opacity-60', generateBtn.disabled);
                    generateBtn.classList.toggle('cursor-not-allowed', generateBtn.disabled);
                };

                const setStatus = (message) => {
                    if (statusEl) statusEl.textContent = message || '';
                };

                const updateSummary = (message) => {
                    if (summaryEl) summaryEl.textContent = message || '';
                };

                const fetchItems = async () => {
                    if (!canUseItinerary || !itinerarySelect) return;
                    const itineraryId = itinerarySelect.value;
                    if (!itineraryId) {
                        setStatus('Pilih itinerary terlebih dahulu.');
                        return;
                    }
                    if (hasFilledItems()) {
                        const ok = window.confirm('Item yang ada akan diganti. Lanjutkan?');
                        if (!ok) return;
                    }

                    generateBtn.disabled = true;
                    setStatus('Mengambil item dari itinerary...');
                    updateSummary('');
                    try {
                        const response = await fetch(`${endpoint}/${itineraryId}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                        });
                        if (!response.ok) {
                            setStatus('Gagal mengambil item dari itinerary.');
                            return;
                        }
                        const payload = await response.json();
                        const items = Array.isArray(payload?.items) ? payload.items : [];
                        renderItems(items);
                        const missingCount = Number(payload?.meta?.missing_price_count || 0);
                        setStatus(`Item terisi: ${items.length}.`);
                        if (missingCount > 0) {
                            updateSummary(`Catatan: ${missingCount} item harga 0, mohon cek ulang.`);
                        }
                    } catch (err) {
                        setStatus('Gagal mengambil item dari itinerary.');
                    } finally {
                        updateGenerateButtonState();
                    }
                };

                if (canUseItinerary) {
                    generateBtn.addEventListener('click', fetchItems);
                    itinerarySelect.addEventListener('change', () => {
                        updateGenerateButtonState();
                        if (itinerarySelect.value === '') {
                            updateSummary('');
                        }
                    });
                    updateGenerateButtonState();
                }

                const convertFieldDisplayToIdr = (inputEl) => {
                    if (!inputEl) return;
                    const displayValue = parseInteger(inputEl.value);
                    const idrValue = Math.max(0, Math.round(displayToIdr(displayValue)));
                    inputEl.value = String(idrValue);
                };

                formEl?.addEventListener('submit', () => {
                    itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => {
                        const markupType = row.querySelector('[data-field="markup_type"]')?.value || 'fixed';
                        const discountType = row.querySelector('[data-field="discount_type"]')?.value || 'fixed';

                        convertFieldDisplayToIdr(row.querySelector('[data-field="contract_rate"]'));
                        convertFieldDisplayToIdr(row.querySelector('[data-field="unit_price"]'));
                        if (markupType !== 'percent') {
                            convertFieldDisplayToIdr(row.querySelector('[data-field="markup"]'));
                        }
                        if (discountType !== 'percent') {
                            convertFieldDisplayToIdr(row.querySelector('[data-field="discount"]'));
                        }
                    });

                    if ((discountTypeSelect?.value || '') !== 'percent') {
                        convertFieldDisplayToIdr(discountValueInput);
                    }
                    convertFieldDisplayToIdr(itemDiscountTotalInput);
                    convertFieldDisplayToIdr(subTotalInput);
                    convertFieldDisplayToIdr(discountAmountInput);
                    convertFieldDisplayToIdr(finalAmountInput);
                });

                itemsContainer.addEventListener('change', (event) => {
                    if (event.target.matches('[data-field="markup_type"]')) {
                        const row = event.target.closest('.quotation-item-row');
                        const fromType = event.target.dataset.prevType || 'fixed';
                        const toType = event.target.value || 'fixed';
                        convertMarkupValue(row, fromType, toType);
                        event.target.dataset.prevType = toType;
                        recalcTotals();
                        return;
                    }
                    if (event.target.matches('[data-field="discount_type"]')) {
                        const row = event.target.closest('.quotation-item-row');
                        const fromType = event.target.dataset.prevType || 'fixed';
                        const toType = event.target.value || 'fixed';
                        convertDiscountValue(row, fromType, toType);
                        event.target.dataset.prevType = toType;
                        recalcTotals();
                        return;
                    }
                });
                itemsContainer.addEventListener('input', (event) => {
                    if (event.target.matches('[data-field="qty"], [data-field="contract_rate"], [data-field="markup"], [data-field="discount"]')) {
                        recalcTotals();
                    }
                });
                discountTypeSelect?.addEventListener('change', recalcTotals);
                discountValueInput?.addEventListener('input', recalcTotals);

                itemsContainer.querySelectorAll('[data-field="markup_type"]').forEach((el) => {
                    el.dataset.prevType = el.value || 'fixed';
                });
                itemsContainer.querySelectorAll('[data-field="discount_type"]').forEach((el) => {
                    el.dataset.prevType = el.value || 'fixed';
                });
                convertExistingRowsFromIdrToDisplay();
                if ((discountTypeSelect?.value || '') !== 'percent') {
                    setMoneyInputDisplay(discountValueInput, idrToDisplay(parseInteger(discountValueInput?.value)));
                }
                itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => {
                    const descriptionInput = row.querySelector('[data-field="description"]');
                    const descriptionText = row.querySelector('[data-role="description-text"]');
                    if (descriptionText) {
                        descriptionText.textContent = descriptionForDisplay(descriptionInput?.value);
                    }
                });
                regroupItemsByDay();
                itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => applyPaxTypeBadge(row));
                recalcTotals();
                toggleItemsVisibility();

                if (canUseItinerary && itinerarySelect.value && !hasFilledItems()) {
                    fetchItems();
                }
            })();
        </script>
    @endpush
@endonce
