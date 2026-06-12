@props([
    'empty' => false,
])

@php
    $hasEmptySlot = isset($emptyState) && trim((string) $emptyState) !== '';
@endphp

<div {{ $attributes->merge(['class' => 'app-card overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
            @if (isset($head))
                <thead>
                    {{ $head }}
                </thead>
            @endif
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @if ($empty && $hasEmptySlot)
                    {{ $emptyState }}
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
</div>
