@php
    $hotel = $hotel ?? null;
    $roomOptions = $roomOptions ?? collect();
    $buttonLabel = $buttonLabel ?? 'Save';
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $toDisplayMoney = static function ($amount) use ($activeCurrencyCode): float {
        return round(\App\Support\Currency::convert((float) ($amount ?? 0), 'IDR', $activeCurrencyCode), 0);
    };

    $priceRows = old('hotel_prices');
    if ($priceRows === null) {
        $priceRows = $hotel?->prices?->map(function ($price) use ($toDisplayMoney) {
            $markupType = $price->markup_type ?? 'fixed';
            $rawMarkup = $price->markup ?? max(0, (float) (($price->publish_rate ?? 0) - ($price->contract_rate ?? 0)));

            return [
            'rooms_id' => $price->rooms_id,
            'start_date' => $price->start_date,
            'end_date' => $price->end_date,
            'contract_rate' => $toDisplayMoney($price->contract_rate ?? 0),
            'markup_type' => $markupType,
            'markup' => $markupType === 'percent' ? $rawMarkup : $toDisplayMoney($rawMarkup),
            'publish_rate' => $toDisplayMoney($price->publish_rate ?? 0),
            'kick_back' => $toDisplayMoney($price->kick_back ?? 0),
        ];
        })->toArray() ?? [];
    }
    if (empty($priceRows)) {
        $priceRows = [['rooms_id' => '']];
    }
@endphp

<div class="space-y-6 hotel-form">
    <div class="app-card p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Hotel Prices</h3>
            <button type="button" class="btn-ghost-sm" data-add-row="price">Add Price</button>
        </div>
        <div id="price-rows" class="space-y-3">
            @foreach ($priceRows as $index => $row)
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12" data-row>
                    <div class="md:col-span-4">
                        <label class="block text-xs text-gray-500">Room</label>
                        <select name="hotel_prices[{{ $index }}][rooms_id]" class="mt-1 app-input">
                            <option value="">Select room</option>
                            @foreach ($roomOptions as $roomOption)
                                <option value="{{ $roomOption->id }}" @selected((string) ($row['rooms_id'] ?? '') === (string) $roomOption->id)>{{ $roomOption->rooms }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4">                        <label class="block text-xs text-gray-500">Start Date</label>
                        <input type="date" name="hotel_prices[{{ $index }}][start_date]" value="{{ $row['start_date'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-4">                        <label class="block text-xs text-gray-500">End Date</label>
                        <input type="date" name="hotel_prices[{{ $index }}][end_date]" value="{{ $row['end_date'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-3">                        <label class="block text-xs text-gray-500">Contract Rate (IDR)</label>
                        <input type="number" min="0" step="1" name="hotel_prices[{{ $index }}][contract_rate]" data-hotel-rate="contract" value="{{ $row['contract_rate'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-3">                        <label class="block text-xs text-gray-500">Markup Type</label>
                        <select name="hotel_prices[{{ $index }}][markup_type]" data-hotel-rate="markup_type" class="mt-1 app-input">
                            <option value="fixed" @selected(($row['markup_type'] ?? 'fixed') === 'fixed')>Fixed</option>
                            <option value="percent" @selected(($row['markup_type'] ?? 'fixed') === 'percent')>Percent</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">                        <label class="block text-xs text-gray-500">Markup</label>
                        <input type="number" min="0" step="1" name="hotel_prices[{{ $index }}][markup]" data-hotel-rate="markup" value="{{ $row['markup'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-3">                        <label class="block text-xs text-gray-500">Publish Rate (Auto)</label>
                        <input type="number" min="0" step="1" name="hotel_prices[{{ $index }}][publish_rate]" data-hotel-rate="publish" value="{{ $row['publish_rate'] ?? '' }}" class="mt-1 app-input" readonly>
                    </div>

                    <div class="md:col-span-12 flex justify-end">
                        <button type="button" class="mt-1 btn-ghost-sm h-[38px] w-full md:w-auto" data-remove-row>Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>



