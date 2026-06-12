@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'app-card p-4']) }}>
    @if (filled($title) || filled($description))
        <div class="mb-3">
            @if (filled($title))
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h3>
            @endif
            @if (filled($description))
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    @endif
    <div class="space-y-3">
        {{ $slot }}
    </div>
</section>
