@props([
    'align' => 'right',
    'width' => 'w-44',
    'label' => null,
])

@php
    $alignmentClass = $align === 'left' ? 'left-0' : 'right-0';
    $ariaLabel = filled($label) ? (string) $label : ui_phrase('Actions');
@endphp

<div {{ $attributes->merge(['class' => 'relative inline-block text-left']) }} x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button
        x-ref="trigger"
        type="button"
        class="btn-ghost-sm"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="menu"
        aria-label="{{ $ariaLabel }}"
        title="{{ $ariaLabel }}"
        @click="
            open = !open;
            if (open) {
                $nextTick(() => {
                    const trigger = $refs.trigger;
                    const panel = $refs.panel;
                    if (!trigger || !panel) return;
                    const rect = trigger.getBoundingClientRect();
                    const panelRect = panel.getBoundingClientRect();
                    const gap = 8;
                    let left = rect.right - panelRect.width;
                    let top = rect.bottom + gap;
                    if (left < gap) left = gap;
                    if (left + panelRect.width > window.innerWidth - gap) {
                        left = window.innerWidth - panelRect.width - gap;
                    }
                    if (top + panelRect.height > window.innerHeight - gap) {
                        top = rect.top - panelRect.height - gap;
                    }
                    if (top < gap) top = gap;
                    panel.style.left = left + 'px';
                    panel.style.top = top + 'px';
                });
            }
        "
    >
        {{ $trigger ?? '' }}
        @if (!isset($trigger))
            <i class="fa-solid fa-ellipsis"></i>
        @endif
    </button>

    <template x-teleport="body">
        <div
            x-ref="panel"
            x-cloak
            x-show="open"
            x-transition.opacity.scale.80
            class="fixed z-[110] {{ $width }} rounded-lg border border-gray-200 bg-white p-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"
            role="menu"
            @click.outside="open = false"
            @keydown.escape.window="open = false"
        >
            <div class="space-y-0.5">
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
