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
                            #{{ $itinerary->id }} - {{ $itinerary->title }}
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
                     class="w-full rounded-lg border border-indigo-300 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20 sm:w-auto"
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
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Items</p>
            <span id="itinerary-items-summary" class="text-xs text-gray-500 dark:text-gray-400"></span>
        </div>
        <div id="quotation-items" class="mt-3 divide-y divide-gray-200 dark:divide-gray-700">
            @for ($i = 0; $i < max($minRows, count($items)); $i++)
                @php
                    $row = $items[$i] ?? ['description' => '', 'qty' => 1, 'unit_price' => 0, 'discount_type' => 'fixed', 'discount' => 0];
                    $serviceableMetaValue = $row['serviceable_meta'] ?? '';
                    if (is_array($serviceableMetaValue)) {
                        $serviceableMetaValue = json_encode($serviceableMetaValue);
                    }
                @endphp
                <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-6 quotation-item-row">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">Description</label>
                        <input data-field="description" name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Qty</label>
                        <input data-field="qty" name="items[{{ $i }}][qty]" type="number" min="1" value="{{ $row['qty'] ?? 1 }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div>
                        <x-money-input
                            label="Unit Price"
                            label-class="block text-xs text-gray-500"
                            name="items[{{ $i }}][unit_price]"
                            :value="$row['unit_price'] ?? 0"
                            data-field="unit_price"
                            step="0.01"
                            compact
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Discount Type</label>
                        <select data-field="discount_type" name="items[{{ $i }}][discount_type]" class="mt-1 dark:border-gray-600 app-input">
                            <option value="fixed" @selected(($row['discount_type'] ?? 'fixed') === 'fixed')>Fixed</option>
                            <option value="percent" @selected(($row['discount_type'] ?? '') === 'percent')>Percent</option>
                        </select>
                    </div>
                    <div>
                        <x-money-input
                            label="Discount"
                            label-class="block text-xs text-gray-500"
                            name="items[{{ $i }}][discount]"
                            :value="$row['discount'] ?? 0"
                            data-field="discount"
                            step="0.01"
                            compact
                        />
                        <p data-field="discount_preview" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400"></p>
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
            <div class="grid grid-cols-1 gap-2 py-2 sm:grid-cols-6 quotation-item-row">
                <div class="sm:col-span-2">
                    <label class="block text-xs text-gray-500">Description</label>
                    <input data-field="description" class="mt-1 dark:border-gray-600 app-input">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Qty</label>
                    <input data-field="qty" type="number" min="1" class="mt-1 dark:border-gray-600 app-input">
                </div>
                <div>
                    <x-money-input
                        label="Unit Price"
                        label-class="block text-xs text-gray-500"
                        data-field="unit_price"
                        step="0.01"
                        compact
                    />
                </div>
                <div>
                    <label class="block text-xs text-gray-500">Discount Type</label>
                    <select data-field="discount_type" class="mt-1 dark:border-gray-600 app-input">
                        <option value="fixed">Fixed</option>
                        <option value="percent">Percent</option>
                    </select>
                </div>
                <div>
                    <x-money-input
                        label="Discount"
                        label-class="block text-xs text-gray-500"
                        data-field="discount"
                        step="0.01"
                        compact
                    />
                    <p data-field="discount_preview" class="mt-1 text-[11px] text-gray-500 dark:text-gray-400"></p>
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

                const parseNumber = (value) => {
                    const num = Number.parseFloat(String(value ?? '').replace(',', '.'));
                    return Number.isFinite(num) ? num : 0;
                };
                const formatCurrency = (value, from = 'IDR') => {
                    const currency = window.appCurrency || 'IDR';
                    const rate = window.appCurrencyRateToIdr || 1;
                    let num = Number(value) || 0;
                    if (from === 'IDR' && currency !== 'IDR') {
                        num = num / rate;
                    } else if (from !== 'IDR' && currency === 'IDR') {
                        num = num * rate;
                    }
                    return new Intl.NumberFormat(currency === 'USD' ? 'en-US' : 'id-ID', {
                        style: 'currency',
                        currency: currency,
                        minimumFractionDigits: window.appCurrencyDecimals ?? (currency === 'USD' ? 2 : 0),
                        maximumFractionDigits: window.appCurrencyDecimals ?? (currency === 'USD' ? 2 : 0)
                    }).format(num);
                };

                const setBadge = (input, text) => {
                    if (!input) return;
                    const badge = input.parentElement?.querySelector('[data-money-badge="1"]');
                    if (badge) {
                        badge.textContent = text;
                    }
                };

                const updateRowDiscountBadge = (row) => {
                    const currency = window.appCurrency || 'IDR';
                    const discountType = (row.querySelector('[data-field="discount_type"]')?.value || 'fixed');
                    const discountInput = row.querySelector('[data-field="discount"]');
                    setBadge(discountInput, discountType === 'percent' ? '%' : currency);
                };

                const updateOverallDiscountBadge = () => {
                    const currency = window.appCurrency || 'IDR';
                    const type = discountTypeSelect?.value || '';
                    const badgeText = type === 'percent' ? '%' : currency;
                    setBadge(discountValueInput, badgeText);
                };

                const convertDiscountValue = (row, fromType, toType) => {
                    if (!row || fromType === toType) return;
                    const qty = parseNumber(row.querySelector('[data-field="qty"]')?.value);
                    const unitPrice = parseNumber(row.querySelector('[data-field="unit_price"]')?.value);
                    const total = Math.max(0, qty * unitPrice);
                    const discountInput = row.querySelector('[data-field="discount"]');
                    if (!discountInput) return;
                    let value = parseNumber(discountInput.value);

                    if (fromType === 'percent' && toType === 'fixed') {
                        value = total * (value / 100);
                        discountInput.value = value.toFixed(2);
                    } else if (fromType === 'fixed' && toType === 'percent') {
                        if (total <= 0) {
                            discountInput.value = '0';
                        } else {
                            value = Math.min(100, (value / total) * 100);
                            discountInput.value = value.toFixed(2);
                        }
                    }
                };

                const recalcTotals = () => {
                    let subTotal = 0;
                    let itemDiscountTotal = 0;
                    itemsContainer.querySelectorAll('.quotation-item-row').forEach((row) => {
                        updateRowDiscountBadge(row);
                        const qty = parseNumber(row.querySelector('[data-field="qty"]')?.value);
                        const unitPrice = parseNumber(row.querySelector('[data-field="unit_price"]')?.value);
                        const discountInput = row.querySelector('[data-field="discount"]');
                        const discountType = (row.querySelector('[data-field="discount_type"]')?.value || 'fixed');
                        let discount = parseNumber(discountInput?.value);
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

                        const previewEl = row.querySelector('[data-field="discount_preview"]');
                        if (previewEl) {
                            if (discountType === 'percent') {
                                previewEl.textContent = `${discount}%`;
                            } else {
                                previewEl.textContent = formatCurrency(discount, 'IDR');
                            }
                        }
                    });

                    const discountType = discountTypeSelect?.value || '';
                    const discountValue = parseNumber(discountValueInput?.value);
                    let discountAmount = 0;
                    if (discountType === 'percent') {
                        discountAmount = subTotal * (discountValue / 100);
                    } else if (discountType === 'fixed') {
                        discountAmount = discountValue;
                    }

                    const finalAmount = Math.max(0, subTotal - discountAmount);

                    if (itemDiscountTotalInput) itemDiscountTotalInput.value = itemDiscountTotal.toFixed(2);
                    if (subTotalInput) subTotalInput.value = subTotal.toFixed(2);
                    if (discountAmountInput) discountAmountInput.value = discountAmount.toFixed(2);
                    if (finalAmountInput) finalAmountInput.value = finalAmount.toFixed(2);

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
                        if (field === 'unit_price' || field === 'discount') {
                            const val = Number(row?.[field]);
                            setValue(input, Number.isFinite(val) ? val : 0, 0);
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
                    return node;
                };

                const renderItems = (items) => {
                    itemsContainer.innerHTML = '';
                    const list = Array.isArray(items) ? items : [];
                    list.forEach((row, index) => {
                        itemsContainer.appendChild(buildRow(index, row));
                    });
                    reindexItems();
                    recalcTotals();
                    toggleItemsVisibility();
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

                itemsContainer.addEventListener('change', (event) => {
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
                    if (event.target.matches('[data-field="qty"], [data-field="unit_price"], [data-field="discount"]')) {
                        recalcTotals();
                    }
                });
                discountTypeSelect?.addEventListener('change', recalcTotals);
                discountValueInput?.addEventListener('input', recalcTotals);

                itemsContainer.querySelectorAll('[data-field="discount_type"]').forEach((el) => {
                    el.dataset.prevType = el.value || 'fixed';
                });
                recalcTotals();
                toggleItemsVisibility();

                if (canUseItinerary && itinerarySelect.value && !hasFilledItems()) {
                    fetchItems();
                }
            })();
        </script>
    @endpush
@endonce



