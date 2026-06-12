@props([
    'amount' => null,
    'currency' => 'IDR',
])

<x-money :amount="$amount" :currency="$currency" {{ $attributes }} />
