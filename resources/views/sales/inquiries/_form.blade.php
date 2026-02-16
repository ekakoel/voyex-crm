@php
    $buttonLabel = $buttonLabel ?? 'Save';
@endphp

<div class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Customer</label>
        <select name="customer_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            <option value="">Select customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $inquiry->customer_id ?? null) == $customer->id)>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
        @error('customer_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Source</label>
            <select name="source" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach (['phone' => 'Phone', 'email' => 'Email', 'website' => 'Website', 'walk-in' => 'Walk-in', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('source', $inquiry->source ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('source')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Deadline</label>
            <input
                name="deadline"
                type="date"
                value="{{ old('deadline', isset($inquiry->deadline) ? $inquiry->deadline->format('Y-m-d') : '') }}"
                class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
            @error('deadline')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
            <select name="status" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                @foreach (['new','follow_up','quoted','converted','closed'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $inquiry->status ?? 'new') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Priority</label>
            <select name="priority" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                @foreach (['low','normal','high'] as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $inquiry->priority ?? 'normal') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
            @error('priority')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Assigned To</label>
            <select name="assigned_to" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">-</option>
                @foreach ($assignees as $user)
                    <option value="{{ $user->id }}" @selected(old('assigned_to', $inquiry->assigned_to ?? null) == $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('assigned_to')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea
            name="notes"
            rows="4"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
        >{{ old('notes', $inquiry->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2">
        <input
            id="reminder_enabled"
            name="reminder_enabled"
            type="checkbox"
            value="1"
            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            @checked(old('reminder_enabled', $inquiry->reminder_enabled ?? true))
        >
        <label for="reminder_enabled" class="text-sm text-gray-700 dark:text-gray-200">Enable email reminder</label>
    </div>

    <div class="flex items-center gap-2">
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('sales.inquiries.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </a>
    </div>
</div>
