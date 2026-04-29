@php
    $buttonLabel = $buttonLabel ?? ui_phrase('Save');
    $selectedType = old('customer_type', $customer->customer_type ?? '');
    $currentCustomerId = $customer->id ?? 0;
@endphp

<div
    x-data="{
        selectedType: @js($selectedType),
        codeInput: @js(old('code', $customer->code ?? '')),
        codeStatus: null,
        codeMessage: '',
        debounceTimer: null,
        async checkCode() {
            const code = (this.codeInput || '').trim().toUpperCase();
            if (!code) {
                this.codeStatus = null;
                this.codeMessage = '';
                return;
            }

            this.codeStatus = 'checking';
            this.codeMessage = @js(ui_phrase('Checking code...'));

            const params = new URLSearchParams({
                code: code,
                ignore_id: @js((string) $currentCustomerId)
            });

            try {
                const response = await fetch(@js(route('customers.check-code')) + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                this.codeStatus = data.available ? 'available' : 'used';
                this.codeMessage = data.message || '';
            } catch (e) {
                this.codeStatus = 'used';
                this.codeMessage = @js(ui_phrase('failed check code'));
            }
        },
        scheduleCheck() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.checkCode(), 350);
        }
    }"
    x-init="if ((codeInput || '').trim()) { checkCode(); }"
    class="space-y-5"
>
    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ ui_phrase('Customer Type') }} <span class="text-rose-600">*</span>
        </label>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('choose type first') }}</p>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="cursor-pointer rounded-lg border p-3 transition hover:border-indigo-400"
                   :class="selectedType === 'individual' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'">
                <input type="radio" name="customer_type" value="individual" x-model="selectedType" class="sr-only" required>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ ui_phrase('Individual') }}</div>
            </label>
            <label class="cursor-pointer rounded-lg border p-3 transition hover:border-indigo-400"
                   :class="selectedType === 'company' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'">
                   <div class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ ui_phrase('Company') }}</div>
                   <input type="radio" name="customer_type" value="company" x-model="selectedType" class="sr-only" required>
            </label>
        </div>
        @error('customer_type')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div x-show="selectedType" x-cloak class="space-y-5">
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Code') }} <span class="text-rose-600">*</span></label>
        <input
            name="code"
            type="text"
            x-model="codeInput"
            @input="scheduleCheck()"
            value="{{ old('code', $customer->code ?? '') }}"
            class="mt-1 uppercase dark:border-gray-600 app-input"
            required
        >
        <p x-show="codeStatus === 'checking'" x-cloak class="mt-1 text-xs text-slate-500">{{ ui_phrase('Checking code...') }}</p>
        <p x-show="codeStatus === 'available'" x-cloak class="mt-1 text-xs text-emerald-600" x-text="codeMessage"></p>
        <p x-show="codeStatus === 'used'" x-cloak class="mt-1 text-xs text-rose-600" x-text="codeMessage"></p>
        @error('code')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Name') }} <span class="text-rose-600">*</span></label>
        <input
            name="name"
            type="text"
            value="{{ old('name', $customer->name ?? '') }}"
            class="mt-1 dark:border-gray-600 app-input"
            required
        >
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Email') }}</label>
            <input
                name="email"
                type="email"
                value="{{ old('email', $customer->email ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
            >
            @error('email')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Phone') }}</label>
            <input
                name="phone"
                type="text"
                value="{{ old('phone', $customer->phone ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
            >
            @error('phone')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Country') }} <span class="text-rose-600">*</span></label>
        <x-forms.searchable-select
            name="country"
            :options="$countries"
            :value="$customer->country ?? 'Indonesia'"
            list-id="customer-country-options"
            placeholder="{{ ui_phrase('select search country') }}"
            :required="true"
            class="mt-1"
        />
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Address') }}</label>
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
        <div x-show="selectedType === 'company'" x-cloak class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ ui_phrase('Company Name') }} <span class="text-rose-600">*</span>
            </label>
            <input
                name="company_name"
                type="text"
                value="{{ old('company_name', $customer->company_name ?? '') }}"
                class="mt-1 dark:border-gray-600 app-input"
                x-bind:required="selectedType === 'company'"
            >
            @error('company_name')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button type="submit"  class="btn-primary">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('customers.index') }}"  class="btn-secondary">
            {{ ui_phrase('Cancel') }}
        </a>
    </div>
    </div>
</div>




