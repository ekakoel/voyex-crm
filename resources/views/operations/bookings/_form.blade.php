@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Quotation</label>
        <select name="quotation_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            <option value="">Select quotation</option>
            @foreach ($quotations as $quotation)
                <option value="{{ $quotation->id }}" @selected(old('quotation_id', $booking->quotation_id ?? null) == $quotation->id)>
                    {{ $quotation->quotation_number }} - {{ $quotation->inquiry->customer->name ?? '-' }}
                </option>
            @endforeach
        </select>
        @error('quotation_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Travel Date</label>
            <input
                name="travel_date"
                type="date"
                value="{{ old('travel_date', isset($booking->travel_date) ? $booking->travel_date->format('Y-m-d') : '') }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                required
            >
            @error('travel_date')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
            <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                @foreach (['confirmed','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $booking->status ?? 'confirmed') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes / Reason</label>
        <textarea
            name="notes"
            rows="3"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
        >{{ old('notes', $booking->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Required when status is cancelled.</p>
    </div>

    <div class="flex items-center gap-2">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('operations.bookings.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </a>
    </div>
</div>
