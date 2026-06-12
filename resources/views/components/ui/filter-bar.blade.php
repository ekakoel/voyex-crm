@props([
    'title' => null,
    'description' => null,
    'formClass' => 'grid grid-cols-1 gap-3 sm:grid-cols-2',
])

<section {{ $attributes->merge(['class' => 'app-card p-5']) }}>
    @if (filled($title) || filled($description))
        <div class="mb-3">
            @if (filled($title))
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h2>
            @endif
            @if (filled($description))
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    @endif

    <div class="{{ $formClass }}">
        {{ $slot }}
    </div>
</section>
