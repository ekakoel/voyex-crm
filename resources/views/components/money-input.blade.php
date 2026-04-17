@props([
    'label' => null,
    'name' => null,
    'value' => null,
    'id' => null,
    'type' => 'number',
    'step' => '0.01',
    'min' => 0,
    'required' => false,
    'readonly' => false,
    'placeholder' => null,
    'currency' => null,
    'badge' => null,
    'wrapperClass' => null,
    'inputClass' => null,
    'labelClass' => null,
    'badgeClass' => null,
    'compact' => false,
])

@php
    $activeCurrencyCode = strtoupper((string) (\App\Support\Currency::current() ?: 'IDR'));
    $activeCurrencyMeta = \App\Support\Currency::meta($activeCurrencyCode);
    $activeCurrencySymbol = is_array($activeCurrencyMeta) && !empty($activeCurrencyMeta['symbol'])
        ? (string) $activeCurrencyMeta['symbol']
        : ($activeCurrencyCode === 'USD' ? '$' : 'Rp');
    $badgeText = $badge ?? $activeCurrencySymbol;
    $wrapperClass = $wrapperClass ?? ($compact ? '' : 'space-y-1');
    $baseInputClass = $compact
        ? 'app-input pl-14 text-right'
        : 'mt-1 app-input pl-14 text-right';
    $inputClass = trim($baseInputClass . ' ' . ($inputClass ?? ''));
    $baseBadgeClass = 'input-left-affix rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200';
    $badgeClass = trim($baseBadgeClass . ' ' . ($badgeClass ?? ''));
@endphp

<div class="{{ $wrapperClass }}">
    @if ($label)
        <label
            @if ($id) for="{{ $id }}" @endif
            class="{{ $labelClass ?? 'block text-sm font-medium text-gray-700 dark:text-gray-200' }}"
        >{{ $label }}</label>
    @endif
    <div class="input-with-left-affix">
        <input
            @if ($name) name="{{ $name }}" @endif
            @if ($id) id="{{ $id }}" @endif
            type="{{ $type }}"
            @if ($value !== null) value="{{ $value }}" @endif
            @if ($step !== null) step="{{ $step }}" @endif
            @if ($min !== null) min="{{ $min }}" @endif
            @if ($required) required @endif
            @if ($readonly) readonly @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            data-money-input="1"
            data-money-currency="{{ $activeCurrencyCode }}"
            {{ $attributes->merge(['class' => $inputClass]) }}
        >
        <span
            class="{{ $badgeClass }}"
            data-money-badge="1"
            data-money-badge-default="{{ $badgeText }}"
        >
            {{ $badgeText }}
        </span>
    </div>
</div>
