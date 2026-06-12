@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'app-card p-4']) }}>
    <div class="mb-3">
        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
            {{ $title ?: ui_phrase('Quick Actions') }}
        </h3>
        @if (filled($description))
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        {{ $slot }}
    </div>
</section>
