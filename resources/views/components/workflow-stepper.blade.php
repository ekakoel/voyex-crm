@props([
    'steps' => [],
    'current' => '',
    'title' => null,
])

@php
    $currentKey = strtolower(trim((string) $current));
    $stepList = collect($steps)->values()->map(function ($step) {
        if (is_array($step)) {
            return [
                'key' => strtolower(trim((string) ($step['key'] ?? ''))),
                'label' => (string) ($step['label'] ?? ($step['key'] ?? '')),
            ];
        }

        return [
            'key' => strtolower(trim((string) $step)),
            'label' => (string) $step,
        ];
    })->filter(fn ($step) => $step['key'] !== '')->values();
    $currentIndex = $stepList->search(fn ($step) => $step['key'] === $currentKey);
@endphp

<div {{ $attributes->merge(['class' => 'app-card p-4']) }}>
    @if ($title)
        <h3 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h3>
    @endif
    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stepList as $index => $step)
            @php
                $isCurrent = $step['key'] === $currentKey;
                $isPassed = is_int($currentIndex) && $index < $currentIndex;
                $badgeClass = $isCurrent
                    ? 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300'
                    : ($isPassed
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                        : 'border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-900/30 dark:text-gray-300');
            @endphp
            <div class="rounded-lg border px-3 py-2 {{ $badgeClass }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold uppercase tracking-wide">{{ $step['label'] }}</span>
                    @if ($isCurrent)
                        <span class="text-[10px] font-semibold">{{ ui_phrase('Current') }}</span>
                    @elseif ($isPassed)
                        <span class="text-[10px] font-semibold">{{ ui_phrase('Done') }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>

