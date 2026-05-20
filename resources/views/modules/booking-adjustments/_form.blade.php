@php
    $selectedType = old('adjustment_type', data_get($adjustment, 'adjustment_type', 'manual_adjustment'));
    $selectedImpact = old('impact_type', data_get($adjustment, 'impact_type', 'non_financial'));
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="adjustment_type" :value="ui_phrase('Adjustment Type')" />
        <select id="adjustment_type" name="adjustment_type" class="app-input" required>
            @foreach (\App\Models\BookingAdjustment::TYPE_OPTIONS as $option)
                <option value="{{ $option }}" @selected($selectedType === $option)>{{ ui_phrase($option) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="impact_type" :value="ui_phrase('Impact Type')" />
        <select id="impact_type" name="impact_type" class="app-input" required>
            @foreach (\App\Models\BookingAdjustment::IMPACT_OPTIONS as $option)
                <option value="{{ $option }}" @selected($selectedImpact === $option)>{{ ui_phrase($option) }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="title" :value="ui_phrase('Title')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', data_get($adjustment, 'title', ''))" required />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="description" :value="ui_phrase('Description')" />
        <textarea id="description" name="description" class="app-input" rows="3">{{ old('description', data_get($adjustment, 'description', '')) }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="reason" :value="ui_phrase('Reason')" />
        <textarea id="reason" name="reason" class="app-input" rows="2">{{ old('reason', data_get($adjustment, 'reason', '')) }}</textarea>
    </div>
    <div>
        <x-input-label for="amount" :value="ui_phrase('Amount')" />
        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('amount', data_get($adjustment, 'amount', 0))" required />
    </div>
    <div>
        <x-input-label for="currency_code" :value="ui_phrase('Currency')" />
        <x-text-input id="currency_code" name="currency_code" type="text" class="mt-1 block w-full" :value="old('currency_code', data_get($adjustment, 'currency_code', 'IDR'))" />
    </div>
    <div>
        <x-input-label for="booking_item_id" :value="ui_phrase('Booking Item')" />
        <select id="booking_item_id" name="booking_item_id" class="app-input">
            <option value="">{{ ui_phrase('Not linked') }}</option>
            @foreach ($booking->items as $item)
                <option value="{{ $item->id }}" @selected((int) old('booking_item_id', data_get($adjustment, 'booking_item_id', 0)) === (int) $item->id)>
                    #{{ $item->id }} - {{ $item->description }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="invoice_id" :value="ui_phrase('Invoice')" />
        <select id="invoice_id" name="invoice_id" class="app-input">
            <option value="">{{ ui_phrase('Not linked') }}</option>
            @foreach ($booking->invoices as $invoice)
                <option value="{{ $invoice->id }}" @selected((int) old('invoice_id', data_get($adjustment, 'invoice_id', 0)) === (int) $invoice->id)>
                    {{ $invoice->invoice_number }} ({{ ui_phrase((string) $invoice->status) }})
                </option>
            @endforeach
        </select>
    </div>
</div>
