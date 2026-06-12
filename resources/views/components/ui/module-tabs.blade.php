@props([
    'tabs' => [],
    'active' => null,
])

<x-module-status-tabs :tabs="$tabs" :active="$active" {{ $attributes }} />
