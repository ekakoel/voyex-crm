@props([
    'amount' => null,
    'currency' => 'IDR',
])

<span {{ $attributes }}>
    {{ \App\Support\Currency::format($amount !== null ? (float) $amount : null, (string) $currency) }}
</span>
