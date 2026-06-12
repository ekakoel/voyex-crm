@props([
    'title' => null,
    'description' => null,
    'helpText' => null,
    'warningText' => null,
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

    @if (filled($warningText))
        <div class="mb-3 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            {{ $warningText }}
        </div>
    @endif

    <div class="flex flex-wrap gap-2">
        {{ $slot }}
    </div>

    @if (filled($helpText))
        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $helpText }}</p>
    @endif
</section>
