@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $editing = isset($booking) && $booking;
    $existingItems = old('items');
    if (! is_array($existingItems)) {
        $existingItems = $editing && $booking->relationLoaded('items')
            ? $booking->items->map(fn ($item) => [
                'quotation_item_id' => $item->quotation_item_id,
                'description' => $item->description,
                'qty' => (int) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'serviceable_type' => $item->serviceable_type,
                'serviceable_id' => $item->serviceable_id,
                'day_number' => $item->day_number,
                'notes' => $item->notes,
            ])->toArray()
            : [];
    }

    $quotationItemsMap = collect($quotations ?? [])->mapWithKeys(function ($quotation) {
        $items = collect($quotation->items ?? [])->map(fn ($item) => [
            'id' => (int) $item->id,
            'description' => (string) ($item->description ?? ''),
            'qty' => (int) ($item->qty ?? 1),
            'unit_price' => (float) ($item->unit_price ?? 0),
            'serviceable_type' => $item->serviceable_type,
            'serviceable_id' => $item->serviceable_id,
            'day_number' => $item->day_number,
            'serviceable_meta' => is_array($item->serviceable_meta ?? null) ? $item->serviceable_meta : null,
        ])->values()->all();

        return [(string) $quotation->id => $items];
    })->all();
    $quotationTravelDateMap = collect($quotations ?? [])->mapWithKeys(function ($quotation) {
        $serviceDate = $quotation->service_date instanceof \Illuminate\Support\Carbon
            ? $quotation->service_date->format('Y-m-d')
            : (is_string($quotation->service_date ?? null) ? (string) $quotation->service_date : '');

        return [(string) $quotation->id => $serviceDate];
    })->all();
    $quotationOrderNumberMap = collect($quotations ?? [])->mapWithKeys(function ($quotation) {
        return [(string) $quotation->id => (string) ($quotation->order_number ?? '')];
    })->all();
    $quotationCustomerNameMap = collect($quotations ?? [])->mapWithKeys(function ($quotation) {
        return [(string) $quotation->id => (string) ($quotation->inquiry?->customer?->name ?? '')];
    })->all();
    $quotationPaxAdultMap = collect($quotations ?? [])->mapWithKeys(function ($quotation) {
        return [(string) $quotation->id => (int) ($quotation->pax_adult ?? 0)];
    })->all();
    $quotationPaxChildMap = collect($quotations ?? [])->mapWithKeys(function ($quotation) {
        return [(string) $quotation->id => (int) ($quotation->pax_child ?? 0)];
    })->all();
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $activeCurrencyMeta = \App\Support\Currency::meta($activeCurrencyCode);
    $activeCurrencyLabel = (string) ($activeCurrencyMeta['symbol'] ?? $activeCurrencyCode);
@endphp

<div class="space-y-5 module-form">
    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Quotation') }} <span class="text-rose-600">*</span></label>
        <div class="mt-1 flex flex-col gap-2 sm:flex-row sm:items-center">
            <select id="booking-quotation-id" name="quotation_id" class="app-input" required>
                <option value="">{{ ui_phrase('Select quotation') }}</option>
                @foreach ($quotations as $quotation)
                    <option value="{{ $quotation->id }}" @selected(old('quotation_id', $booking->quotation_id ?? null) == $quotation->id)>
                        {{ $quotation->order_number ?: '-' }}
                        | {{ ui_phrase((string) ($quotation->status ?? '-')) }}
                        | {{ method_exists($quotation, 'items') ? $quotation->items->count() : 0 }} {{ ui_phrase('Items') }}
                    </option>
                @endforeach
            </select>
            <button type="button" id="booking-generate-items" class="btn-outline-sm min-h-[42px] w-full sm:w-auto">
                {{ ui_phrase('Generate') }}
            </button>
        </div>
        @error('quotation_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Order Number') }}</label>
            <input
                id="booking-order-number"
                type="text"
                value="{{ old('order_number', $booking->quotation->order_number ?? '') }}"
                class="mt-1 app-input bg-gray-50 dark:bg-gray-900/30"
                readonly
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Customer Name') }}</label>
            <input
                id="booking-customer-name"
                type="text"
                value="{{ old('customer_name', $booking->quotation?->inquiry?->customer?->name ?? '') }}"
                class="mt-1 app-input bg-gray-50 dark:bg-gray-900/30"
                readonly
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Pax Adult') }}</label>
            <input
                id="booking-pax-adult"
                type="text"
                value="{{ old('pax_adult', (string) ($booking->quotation->pax_adult ?? '0')) }}"
                class="mt-1 app-input bg-gray-50 dark:bg-gray-900/30"
                readonly
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Pax Child') }}</label>
            <input
                id="booking-pax-child"
                type="text"
                value="{{ old('pax_child', (string) ($booking->quotation->pax_child ?? '0')) }}"
                class="mt-1 app-input bg-gray-50 dark:bg-gray-900/30"
                readonly
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Travel Date') }} <span class="text-rose-600">*</span></label>
            <input
                id="booking-travel-date"
                name="travel_date"
                type="date"
                value="{{ old('travel_date', isset($booking->travel_date) ? $booking->travel_date->format('Y-m-d') : '') }}"
                class="mt-1 app-input"
                required
            >
            @error('travel_date')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

    </div>

    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Items') }} <span class="text-rose-600">*</span></p>
            <p id="booking-items-summary" class="text-xs text-gray-500 dark:text-gray-400"></p>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-3 py-2 text-left">{{ ui_phrase('Description') }}</th>
                        <th class="px-3 py-2 text-left">{{ ui_phrase('Qty') }}</th>
                        <th class="px-3 py-2 text-left">{{ ui_phrase('Rate') }}</th>
                        <th class="px-3 py-2 text-left">{{ ui_phrase('Total') }}</th>
                        <th class="px-3 py-2 text-left">{{ ui_phrase('Notes') }}</th>
                    </tr>
                </thead>
                <tbody id="booking-items-body">
                    @forelse ($existingItems as $i => $item)
                        @php
                            $qty = max(1, (int) ($item['qty'] ?? 1));
                            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
                        @endphp
                        <tr class="booking-item-row">
                            <td class="px-3 py-2">
                                <input type="hidden" name="items[{{ $i }}][quotation_item_id]" value="{{ $item['quotation_item_id'] ?? '' }}">
                                <input type="hidden" name="items[{{ $i }}][serviceable_type]" value="{{ $item['serviceable_type'] ?? '' }}">
                                <input type="hidden" name="items[{{ $i }}][serviceable_id]" value="{{ $item['serviceable_id'] ?? '' }}">
                                <input type="hidden" name="items[{{ $i }}][day_number]" value="{{ $item['day_number'] ?? '' }}">
                                <input type="hidden" name="items[{{ $i }}][serviceable_meta]" value="{{ isset($item['serviceable_meta']) ? json_encode($item['serviceable_meta']) : '' }}">
                                <input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" class="app-input" required>
                            </td>
                            <td class="px-3 py-2"><input type="number" min="1" name="items[{{ $i }}][qty]" value="{{ $qty }}" class="app-input booking-item-qty" required></td>
                            <td class="px-3 py-2"><input type="text" inputmode="numeric" name="items[{{ $i }}][unit_price]" value="{{ number_format($unitPrice, 0, ',', '.') }}" class="app-input booking-item-rate text-right" required></td>
                            <td class="px-3 py-2">
                                <div class="input-with-left-affix">
                                    <input type="text" value="{{ number_format($qty * $unitPrice, 0, ',', '.') }}" class="app-input booking-item-rate booking-item-total booking-item-total-value pl-14 text-right bg-gray-50 dark:bg-gray-900/30" readonly>
                                    <span class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ $activeCurrencyLabel }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-2"><input type="text" name="items[{{ $i }}][notes]" value="{{ $item['notes'] ?? '' }}" class="app-input"></td>
                        </tr>
                    @empty
                        <tr id="booking-items-empty">
                            <td colspan="5" class="px-3 py-3 text-sm text-gray-500">{{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Items')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @error('items')
            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes / Reason') }}</label>
        <textarea
            name="notes"
            rows="3"
            class="mt-1 w-full app-input"
        >{{ old('notes', $booking->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        <button type="submit"  class="btn-primary">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('bookings.index') }}"  class="btn-secondary">
            {{ ui_phrase('Cancel') }}
        </a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const quotationItemsMap = @json($quotationItemsMap);
                const quotationTravelDateMap = @json($quotationTravelDateMap);
                const quotationOrderNumberMap = @json($quotationOrderNumberMap);
                const quotationCustomerNameMap = @json($quotationCustomerNameMap);
                const quotationPaxAdultMap = @json($quotationPaxAdultMap);
                const quotationPaxChildMap = @json($quotationPaxChildMap);
                const activeCurrencyCode = @json($activeCurrencyCode);
                const activeCurrencyLabel = @json($activeCurrencyLabel);
                const tbody = document.getElementById('booking-items-body');
                const select = document.getElementById('booking-quotation-id');
                const generateBtn = document.getElementById('booking-generate-items');
                const summary = document.getElementById('booking-items-summary');
                const travelDateInput = document.getElementById('booking-travel-date');
                const orderNumberInput = document.getElementById('booking-order-number');
                const customerNameInput = document.getElementById('booking-customer-name');
                const paxAdultInput = document.getElementById('booking-pax-adult');
                const paxChildInput = document.getElementById('booking-pax-child');
                if (!tbody || !select || !generateBtn) return;

                const recalcTotals = () => {
                    let grandTotal = 0;
                    const rows = Array.from(tbody.querySelectorAll('.booking-item-row'));
                    rows.forEach((row) => {
                        const qty = Math.max(1, parseInt(row.querySelector('.booking-item-qty')?.value || '1', 10));
                        const rate = parseIdrNumber(row.querySelector('.booking-item-rate')?.value || '0');
                        const total = qty * rate;
                        const totalInput = row.querySelector('.booking-item-total');
                        if (totalInput) totalInput.value = formatThousands(Math.round(total));
                        grandTotal += total;
                    });
                    if (summary) summary.textContent = `{{ ui_phrase('Total') }}: ${formatMoney(Math.round(grandTotal))} | {{ ui_phrase('Items') }}: ${rows.length}`;
                };

                const bindRowEvents = () => {
                    tbody.querySelectorAll('.booking-item-qty, .booking-item-rate').forEach((input) => {
                        input.addEventListener('input', recalcTotals);
                    });
                };

                const clearRows = () => {
                    tbody.innerHTML = '';
                };

                const addRow = (idx, item) => {
                    const qty = Math.max(1, parseInt(item.qty || 1, 10));
                    const unitPrice = Math.max(0, parseFloat(item.unit_price || 0));
                    const tr = document.createElement('tr');
                    tr.className = 'booking-item-row';
                    tr.innerHTML = `
                        <td class="px-3 py-2">
                            <input type="hidden" name="items[${idx}][quotation_item_id]" value="${item.id || ''}">
                            <input type="hidden" name="items[${idx}][serviceable_type]" value="${item.serviceable_type || ''}">
                            <input type="hidden" name="items[${idx}][serviceable_id]" value="${item.serviceable_id || ''}">
                            <input type="hidden" name="items[${idx}][day_number]" value="${item.day_number || ''}">
                            <input type="hidden" name="items[${idx}][serviceable_meta]" value='${JSON.stringify(item.serviceable_meta || null)}'>
                            <input type="text" name="items[${idx}][description]" value="${(item.description || '').replace(/"/g, '&quot;')}" class="app-input" required>
                        </td>
                        <td class="px-3 py-2"><input type="number" min="1" name="items[${idx}][qty]" value="${qty}" class="app-input booking-item-qty" required></td>
                        <td class="px-3 py-2"><input type="text" inputmode="numeric" name="items[${idx}][unit_price]" value="${formatThousands(Math.round(unitPrice))}" class="app-input booking-item-rate text-right" required></td>
                        <td class="px-3 py-2">
                            <div class="input-with-left-affix">
                                <input type="text" value="${formatThousands(Math.round(qty * unitPrice))}" class="app-input booking-item-rate booking-item-total booking-item-total-value pl-14 text-right bg-gray-50 dark:bg-gray-900/30" readonly>
                                <span class="input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">${activeCurrencyLabel}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input type="text" name="items[${idx}][notes]" value="" class="app-input"></td>
                    `;
                    tbody.appendChild(tr);
                };

                const generateFromQuotation = () => {
                    const quotationId = String(select.value || '');
                    if (!quotationId) return;
                    const items = quotationItemsMap[quotationId] || [];
                    clearRows();
                    if (!Array.isArray(items) || items.length === 0) {
                        tbody.innerHTML = `<tr id="booking-items-empty"><td colspan="5" class="px-3 py-3 text-sm text-gray-500">{{ ui_phrase('No items in selected quotation.') }}</td></tr>`;
                        if (summary) summary.textContent = '';
                        return;
                    }
                    items.forEach((item, idx) => addRow(idx, item));
                    bindRowEvents();
                    recalcTotals();
                };

                const applyTravelDateFromQuotation = () => {
                    if (!travelDateInput) return;
                    const quotationId = String(select.value || '');
                    if (!quotationId) return;
                    const serviceDate = String(quotationTravelDateMap[quotationId] || '').trim();
                    if (serviceDate !== '') {
                        travelDateInput.value = serviceDate;
                    }
                };

                const applyOrderNumberFromQuotation = () => {
                    if (!orderNumberInput) return;
                    const quotationId = String(select.value || '');
                    if (!quotationId) {
                        orderNumberInput.value = '';
                        return;
                    }
                    orderNumberInput.value = String(quotationOrderNumberMap[quotationId] || '');
                };

                const applyBookingMetaFromQuotation = () => {
                    const quotationId = String(select.value || '');
                    if (customerNameInput) {
                        customerNameInput.value = quotationId ? String(quotationCustomerNameMap[quotationId] || '') : '';
                    }
                    if (paxAdultInput) {
                        paxAdultInput.value = quotationId ? String(quotationPaxAdultMap[quotationId] ?? 0) : '0';
                    }
                    if (paxChildInput) {
                        paxChildInput.value = quotationId ? String(quotationPaxChildMap[quotationId] ?? 0) : '0';
                    }
                };

                function parseIdrNumber(value) {
                    const cleaned = String(value || '').replace(/[^\d]/g, '');
                    return Math.max(0, parseInt(cleaned || '0', 10) || 0);
                }

                function formatThousands(value) {
                    const number = Math.max(0, parseInt(value || 0, 10) || 0);
                    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                }

                function formatMoney(value) {
                    const normalized = formatThousands(value);
                    if (String(activeCurrencyCode || '').toUpperCase() === 'USD') {
                        return `${activeCurrencyLabel}${normalized}`;
                    }
                    return `${activeCurrencyLabel} ${normalized}`;
                }

                generateBtn.addEventListener('click', generateFromQuotation);
                select.addEventListener('change', applyTravelDateFromQuotation);
                select.addEventListener('change', applyOrderNumberFromQuotation);
                select.addEventListener('change', applyBookingMetaFromQuotation);
                bindRowEvents();
                applyOrderNumberFromQuotation();
                applyBookingMetaFromQuotation();
                recalcTotals();

                tbody.addEventListener('blur', (event) => {
                    if (!(event.target instanceof HTMLInputElement)) return;
                    if (!event.target.classList.contains('booking-item-rate')) return;
                    event.target.value = formatThousands(parseIdrNumber(event.target.value));
                    recalcTotals();
                }, true);
            })();
        </script>
    @endpush
@endonce
