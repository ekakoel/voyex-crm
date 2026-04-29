@php
    $buttonLabel = $buttonLabel ?? ui_phrase('Save');
    $sourceLabels = $sourceLabels ?? [];
    $customerLabels = collect($customers ?? [])->mapWithKeys(function ($customer) {
        $label = '(' . ($customer->code ?? '-') . ') ' . $customer->name;
        return [(string) $customer->id => $label];
    })->toArray();

    $selectedCustomerId = (string) old('customer_id', $inquiry->customer_id ?? '');
    $selectedCustomerLabel = $selectedCustomerId !== '' && isset($customerLabels[$selectedCustomerId])
        ? $customerLabels[$selectedCustomerId]
        : (string) old('customer_label', '');
@endphp

<div class="module-form">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Customer:') }}</label>
        <input
            id="customer_label"
            type="text"
            list="customer-options"
            class="mt-1 app-input"
            placeholder="{{ ui_phrase('select customer') }}"
            value="{{ $selectedCustomerLabel }}"
            data-datalist-input="1"
            data-hidden-target="customer_id"
            data-map='@json(array_flip($customerLabels))'
            required
        >
        <input type="hidden" name="customer_id" id="customer_id" value="{{ $selectedCustomerId }}" class="app-input">
        <datalist id="customer-options">
            @foreach ($customerLabels as $label)
                <option value="{{ $label }}"></option>
            @endforeach
        </datalist>
        @error('customer_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        <p id="customer-invalid-message" class="mt-1 text-xs text-rose-600 hidden">
            {{ ui_phrase('invalid customer message') }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Source') }}</label>
            <select name="source" class="mt-1 app-input">
                <option value="">-</option>
                @foreach (($sourceLabels ?? []) as $value => $label)
                    <option value="{{ $value }}" @selected(old('source', $inquiry->source ?? '') === $value)>{{ ui_phrase((string) $value) }}</option>
                @endforeach
            </select>
            @error('source')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Deadline') }}</label>
            <input
                name="deadline"
                type="date"
                value="{{ old('deadline', isset($inquiry->deadline) ? $inquiry->deadline->format('Y-m-d') : '') }}"
                class="mt-1 app-input"
            >
            @error('deadline')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-1">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Priority') }}</label>
            <select name="priority" class="mt-1 app-input" required>
                @foreach (['low','normal','high'] as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $inquiry->priority ?? 'normal') === $priority)>{{ ui_phrase($priority) }}</option>
                @endforeach
            </select>
            @error('priority')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Notes') }}</label>
        <textarea
            name="notes"
            rows="4"
            class="mt-1 w-full app-input"
            placeholder="{{ ui_phrase('write here') }}"
        >{{ old('notes', $inquiry->notes ?? '') }}</textarea>
        @error('notes')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-2 mt-4">
        <button type="submit"  class="btn-primary">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('inquiries.index') }}"  class="btn-secondary">
            {{ ui_phrase('Cancel') }}
        </a>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function() {
                const inputs = document.querySelectorAll('#customer_label[data-datalist-input="1"]');
                inputs.forEach((input) => {
                    if (input.dataset.datalistBound === '1') return;
                    input.dataset.datalistBound = '1';

                    const map = input.dataset.map ? JSON.parse(input.dataset.map) : {};
                    const hiddenId = input.dataset.hiddenTarget || '';
                    const hidden = hiddenId ? document.getElementById(hiddenId) : null;
                    const invalidMessage = document.getElementById('customer-invalid-message');

                    const setInvalidState = (isInvalid) => {
                        if (invalidMessage) {
                            invalidMessage.classList.toggle('hidden', !isInvalid);
                        }
                        input.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
                    };

                    const syncHidden = () => {
                        if (!hidden) return;
                        const raw = (input.value || '').trim();
                        if (raw === '') {
                            hidden.value = '';
                            setInvalidState(false);
                            return;
                        }
                        if (map && Object.prototype.hasOwnProperty.call(map, raw)) {
                            hidden.value = map[raw];
                            setInvalidState(false);
                            return;
                        }
                        hidden.value = '';
                        setInvalidState(true);
                    };

                    input.addEventListener('input', syncHidden);
                    input.addEventListener('blur', syncHidden);
                    syncHidden();

                    const form = input.closest('form');
                    if (form) {
                        form.addEventListener('submit', (event) => {
                            syncHidden();
                            const raw = (input.value || '').trim();
                            if (raw !== '' && hidden && hidden.value === '') {
                                event.preventDefault();
                                input.focus();
                                input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    }
                });
            })();
        </script>
    @endpush
@endonce
