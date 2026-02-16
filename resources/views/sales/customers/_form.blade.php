@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama</label>
        <input
            name="name"
            type="text"
            value="{{ old('name', $customer->name ?? '') }}"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            required
        >
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
            <input
                name="email"
                type="email"
                value="{{ old('email', $customer->email ?? '') }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
            @error('email')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Phone</label>
            <input
                name="phone"
                type="text"
                value="{{ old('phone', $customer->phone ?? '') }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
            @error('phone')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
        <textarea
            name="address"
            rows="3"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
        >{{ old('address', $customer->address ?? '') }}</textarea>
        @error('address')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Customer Type</label>
            <select name="customer_type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                @foreach (['individual' => 'Individual', 'company' => 'Company'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('customer_type', $customer->customer_type ?? 'individual') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('customer_type')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Company Name</label>
            <input
                name="company_name"
                type="text"
                value="{{ old('company_name', $customer->company_name ?? '') }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
            @error('company_name')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('sales.customers.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </a>
    </div>
</div>
