@php
    $buttonLabel = $buttonLabel ?? 'Save Service';
    $service = $service ?? null;
    $routePrefix = match($serviceType) {
        'accommodations' => 'admin.services.items.accommodations',
        'transports' => 'admin.services.items.transports',
        'guides' => 'admin.services.items.guides',
        'attractions' => 'admin.services.items.attractions',
        'travel_activities' => 'admin.services.items.travel-activities',
        default => 'admin.services.items.accommodations',
    };
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Vendor</label>
        <select name="vendor_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <option value="">-</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $service->vendor_id ?? null) == $vendor->id)>{{ $vendor->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $serviceTypeLabel }} Name</label>
        <input name="name" value="{{ old('name', $service->name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Price</label>
            <input name="unit_price" type="number" step="0.01" value="{{ old('unit_price', $service->unit_price ?? 0) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
        </div>
        <div class="flex items-center gap-2 mt-6">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
                @checked(old('is_active', $service->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
        <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $service->description ?? '') }}</textarea>
    </div>

    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route($routePrefix.'.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>
