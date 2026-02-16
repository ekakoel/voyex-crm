@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $vendor = $vendor ?? null;
@endphp

<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Vendor Name</label>
        <input name="name" value="{{ old('name', $vendor->name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Name</label>
            <input name="contact_name" value="{{ old('contact_name', $vendor->contact_name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
            <input name="contact_email" type="email" value="{{ old('contact_email', $vendor->contact_email ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $vendor->contact_phone ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div class="flex items-center gap-2 mt-6">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
                @checked(old('is_active', $vendor->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
        <textarea name="address" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('address', $vendor->address ?? '') }}</textarea>
    </div>
    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('admin.vendors.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>
