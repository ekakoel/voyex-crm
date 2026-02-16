@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $promotion = $promotion ?? null;
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Code</label>
        <input name="code" value="{{ old('code', $promotion->code ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Type</label>
            <select name="type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                <option value="percent" @selected(old('type', $promotion->type ?? '') === 'percent')>Percent</option>
                <option value="fixed" @selected(old('type', $promotion->type ?? '') === 'fixed')>Fixed</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Value</label>
            <input name="value" type="number" step="0.01" value="{{ old('value', $promotion->value ?? 0) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
        </div>
        <div class="flex items-center gap-2 mt-6">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
                @checked(old('is_active', $promotion->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
        </div>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</label>
            <input name="start_date" type="date" value="{{ old('start_date', isset($promotion->start_date) ? $promotion->start_date->format('Y-m-d') : '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">End Date</label>
            <input name="end_date" type="date" value="{{ old('end_date', isset($promotion->end_date) ? $promotion->end_date->format('Y-m-d') : '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('admin.promotions.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>
