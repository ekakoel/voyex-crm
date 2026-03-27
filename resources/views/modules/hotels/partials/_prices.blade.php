@php
    $hotel = $hotel ?? null;
    $roomOptions = $roomOptions ?? collect();
    $buttonLabel = $buttonLabel ?? 'Save';

    $priceRows = old('hotel_prices');
    if ($priceRows === null) {
        $priceRows = $hotel?->prices?->map(fn ($price) => [
            'rooms_id' => $price->rooms_id,
            'start_date' => $price->start_date,
            'end_date' => $price->end_date,
            'contract_rate' => $price->contract_rate,
        ])->toArray() ?? [];
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
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">Start Date</label>
                        <input type="date" name="hotel_prices[{{ $index }}][start_date]" value="{{ $row['start_date'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">End Date</label>
                        <input type="date" name="hotel_prices[{{ $index }}][end_date]" value="{{ $row['end_date'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-500">Contract Rate (IDR)</label>
                        <input type="number" min="0" name="hotel_prices[{{ $index }}][contract_rate]" data-no-money-hint="1" value="{{ $row['contract_rate'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    
                    
                    <div class="md:col-span-1">
                        <label class="block text-xs text-transparent select-none">Action</label>
                        <button type="button" class="mt-1 btn-ghost-sm h-[38px] w-full" data-remove-row>Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
