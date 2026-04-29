@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

<div class="space-y-5 module-form">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Quotation') }}</label>
        <select name="quotation_id" class="mt-1 app-input" required>
            <option value="">{{ ui_phrase('Select quotation') }}</option>
            @foreach ($quotations as $quotation)
                <option value="{{ $quotation->id }}" @selected(old('quotation_id', $booking->quotation_id ?? null) == $quotation->id)>
                    {{ $quotation->quotation_number }} - {{ $quotation->inquiry?->customer?->name ?? '-' }}
                </option>
            @endforeach
        </select>
        @error('quotation_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Travel Date') }}</label>
            <input
                name="travel_date"
                type="date"
                value="{{ old('travel_date', isset($booking->travel_date) ? $booking->travel_date->format('Y-m-d') : '') }}"
                class="mt-1 app-input"
                required
            >
            @error('travel_date')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Status') }}</label>
            <select name="status" class="mt-1 app-input" required>
                @foreach (\App\Models\Booking::STATUS_OPTIONS as $status)
                    <option value="{{ $status }}" @selected(old('status', $booking->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes / Reason') }}</label>
        <textarea
            name="notes"
            rows="3"
            class="mt-1 w-full app-input"
        >{{ old('notes', $booking->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Required when status is rejected.') }}</p>
    </div>

    <div class="flex items-center gap-2">
        <button type="submit"  class="btn-primary">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('bookings.index') }}"  class="btn-secondary">
            Cancel
        </a>
    </div>
</div>






