@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

@php
    $items = old('items', isset($quotation) ? $quotation->items->map(function ($item) {
        return [
            'description' => $item->description,
            'qty' => $item->qty,
            'unit_price' => $item->unit_price,
            'discount' => $item->discount,
        ];
    })->toArray() : []);
    $items = array_values($items);
    $minRows = 3;
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Template</label>
            <select name="template_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected(old('template_id', $quotation->template_id ?? null) == $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
            @error('template_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Inquiry</label>
        <select name="inquiry_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            <option value="">Select inquiry</option>
            @foreach ($inquiries as $inquiry)
                <option value="{{ $inquiry->id }}" @selected(old('inquiry_id', $quotation->inquiry_id ?? null) == $inquiry->id)>
                    {{ $inquiry->inquiry_number }} - {{ $inquiry->customer->name ?? '-' }}
                </option>
            @endforeach
        </select>
        @error('inquiry_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
            <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                @foreach (['draft','sent','approved','rejected'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $quotation->status ?? 'draft') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Validity Date</label>
            <input
                name="validity_date"
                type="date"
                value="{{ old('validity_date', isset($quotation->validity_date) ? $quotation->validity_date->format('Y-m-d') : '') }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                required
            >
            @error('validity_date')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Final Amount</label>
            <input
                name="final_amount"
                type="number"
                step="0.01"
                value="{{ old('final_amount', $quotation->final_amount ?? 0) }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                required
            >
            @error('final_amount')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Discount Type</label>
            <select name="discount_type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-</option>
                <option value="percent" @selected(old('discount_type', $quotation->discount_type ?? '') === 'percent')>Percent</option>
                <option value="fixed" @selected(old('discount_type', $quotation->discount_type ?? '') === 'fixed')>Fixed</option>
            </select>
            @error('discount_type')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Discount Value</label>
            <input
                name="discount_value"
                type="number"
                step="0.01"
                value="{{ old('discount_value', $quotation->discount_value ?? 0) }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
            @error('discount_value')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Items</p>
        <div class="mt-3 space-y-3">
            @for ($i = 0; $i < max($minRows, count($items)); $i++)
                @php
                    $row = $items[$i] ?? ['description' => '', 'qty' => 1, 'unit_price' => 0, 'discount' => 0];
                @endphp
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-dashed border-gray-200 p-3 dark:border-gray-700 sm:grid-cols-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">Description</label>
                        <input name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Qty</label>
                        <input name="items[{{ $i }}][qty]" type="number" min="1" value="{{ $row['qty'] ?? 1 }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Unit Price</label>
                        <input name="items[{{ $i }}][unit_price]" type="number" step="0.01" value="{{ $row['unit_price'] ?? 0 }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500">Discount</label>
                        <input name="items[{{ $i }}][discount]" type="number" step="0.01" value="{{ $row['discount'] ?? 0 }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>
            @endfor
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('quotations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </a>
    </div>
</div>


