@props([
    'title' => null,
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'app-card p-6 text-center']) }}>
    <div class="mx-auto max-w-md space-y-1">
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            {{ $title ?: ui_phrase('No data found') }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $message ?: ui_phrase('Try changing your filter or create a new record.') }}
        </p>
    </div>
</div>
