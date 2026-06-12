@props([
    'tabs' => [],
    'active' => null,
])

@php
    $tabItems = collect($tabs)->values()->map(function ($tab) {
        return [
            'key' => (string) ($tab['key'] ?? ''),
            'label' => (string) ($tab['label'] ?? ($tab['key'] ?? '')),
            'url' => (string) ($tab['url'] ?? '#'),
            'count' => $tab['count'] ?? null,
        ];
    })->filter(fn ($tab) => $tab['key'] !== '' && $tab['url'] !== '')->values();
    $activeKey = (string) ($active ?? request('status', 'all'));
@endphp

@if ($tabItems->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'app-card p-3']) }}>
        <div class="app-tabs">
            @foreach ($tabItems as $tab)
                @php
                    $isActive = $activeKey === (string) $tab['key'];
                @endphp
                <a href="{{ $tab['url'] }}"
                    class="app-tab {{ $isActive ? 'is-active' : '' }}"
                    @if ($isActive) aria-current="page" @endif>
                    <span>{{ $tab['label'] }}</span>
                    @if (is_numeric($tab['count']))
                        <span class="rounded-full bg-black/10 px-1.5 py-0.5 text-[10px] dark:bg-white/20">{{ (int) $tab['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif
