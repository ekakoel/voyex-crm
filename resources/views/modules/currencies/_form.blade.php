@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $currency = $currency ?? null;
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Code</label>
            <input name="code" value="{{ old('code', $currency->code ?? '') }}" maxlength="10" class="mt-1 uppercase dark:border-gray-600 app-input" required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Name</label>
            <input name="name" value="{{ old('name', $currency->name ?? '') }}" maxlength="100" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Symbol</label>
            <input name="symbol" value="{{ old('symbol', $currency->symbol ?? '') }}" maxlength="10" placeholder="Rp / $" class="mt-1 dark:border-gray-600 app-input">
            @error('symbol') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Rate to IDR</label>
            <x-money-input
                name="rate_to_idr"
                :value="old('rate_to_idr', $currency->rate_to_idr ?? 1)"
                min="0"
                step="0.000001"
                badge="IDR"
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">1 unit currency = ? IDR</p>
            @error('rate_to_idr') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Decimal Places</label>
            <input name="decimal_places" type="number" min="0" max="6" value="{{ old('decimal_places', $currency->decimal_places ?? 0) }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('decimal_places') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-4">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
                @checked(old('is_active', $currency->is_active ?? true))>
            Active
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-emerald-600"
                @checked(old('is_default', $currency->is_default ?? false))>
            Set as default currency
        </label>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('currencies.index') }}"  class="btn-secondary">Cancel</a>
    </div>
</div>



