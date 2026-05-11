@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidth = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div
    x-data="{
        show: @js($show),
        lockBodyScroll() {
            window.__appModalOpenCount = (window.__appModalOpenCount || 0) + 1;
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');
        },
        unlockBodyScroll() {
            window.__appModalOpenCount = Math.max(0, (window.__appModalOpenCount || 0) - 1);
            if (window.__appModalOpenCount === 0) {
                document.documentElement.classList.remove('overflow-hidden');
                document.body.classList.remove('overflow-hidden');
            }
        },
        focusables() {
            // All focusable element types...
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...this.$root.querySelectorAll(selector)]
                // All non-disabled elements...
                .filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) -1 },
    }"
    x-init="
        $watch('show', value => {
            if (value) {
                lockBodyScroll();
                {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable()?.focus(), 100)' : '' }}
            } else {
                unlockBodyScroll();
            }
        });
        if (show) {
            lockBodyScroll();
        }
    "
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:beforeunload.window="unlockBodyScroll()"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="if (show) show = false"
    x-on:keydown.tab.prevent="if (show) { $event.shiftKey || nextFocusable().focus() }"
    x-on:keydown.shift.tab.prevent="if (show) { prevFocusable().focus() }"
>
    <template x-teleport="body">
        <div
            x-show="show"
            class="fixed inset-0 z-[1000] overflow-y-auto px-4 py-6 sm:px-0"
            style="display: none;"
        >
            <div
                x-show="show"
                class="fixed inset-0 transform transition-all"
                x-on:click="show = false"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <div class="absolute inset-0 bg-gray-900/55"></div>
            </div>

            <div class="relative flex min-h-full items-center justify-center py-2">
                <div
                    x-show="show"
                    class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full {{ $maxWidth }} sm:mx-auto max-h-[calc(100vh-2rem)] flex flex-col"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div class="max-h-[calc(100vh-2rem)] overflow-y-auto overscroll-contain">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
