@props([
    'action',
    'method' => 'POST',
    'modalName' => null,
    'title' => null,
    'message' => null,
    'confirmLabel' => null,
    'cancelLabel' => null,
    'triggerLabel' => null,
    'triggerIcon' => null,
    'triggerClass' => 'btn-secondary',
    'confirmClass' => 'btn-primary-sm',
    'impactTitle' => null,
    'impactItems' => [],
    'noticeMessage' => null,
    'noticeTone' => 'info',
])

@php
    $normalizedMethod = strtoupper((string) $method);
    $resolvedModalName = (string) ($modalName ?: ('confirm-action-' . substr(sha1((string) $action . '|' . (string) $triggerLabel . '|' . (string) $triggerClass), 0, 12)));
    $resolvedTitle = (string) ($title ?: __('confirm.default_title'));
    $resolvedMessage = (string) ($message ?: __('confirm.default_message'));
    $resolvedConfirmLabel = (string) ($confirmLabel ?: ui_phrase('Confirm'));
    $resolvedCancelLabel = (string) ($cancelLabel ?: ui_phrase('Cancel'));
    $resolvedTriggerLabel = (string) ($triggerLabel ?: ui_phrase('Submit'));
    $resolvedImpactTitle = (string) ($impactTitle ?: __('confirm.action_information'));
    $resolvedImpactItems = collect(is_array($impactItems) ? $impactItems : [])->filter(fn ($item) => filled($item))->values();
    $toneClassMap = [
        'danger' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-700/40 dark:bg-rose-900/20 dark:text-rose-200',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-700/40 dark:bg-amber-900/20 dark:text-amber-200',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-200',
        'info' => 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-700/40 dark:bg-indigo-900/20 dark:text-indigo-200',
    ];
    $resolvedNoticeMessage = (string) ($noticeMessage ?: __('confirm.notification_after_action'));
    $resolvedNoticeTone = strtolower(trim((string) $noticeTone));
    $resolvedNoticeClass = $toneClassMap[$resolvedNoticeTone] ?? $toneClassMap['info'];
@endphp

<button
    type="button"
    class="{{ $triggerClass }}"
    x-data
    x-on:click.prevent="$dispatch('open-modal', '{{ $resolvedModalName }}')"
>
    @if (!empty($triggerIcon))
        <i class="{{ $triggerIcon }}" aria-hidden="true"></i>
    @endif
    <span>{{ $resolvedTriggerLabel }}</span>
</button>

<x-modal name="{{ $resolvedModalName }}" focusable maxWidth="lg">
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xl dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $resolvedTitle }}</h3>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $resolvedMessage }}</p>
            </div>
        </div>

        @if ($resolvedImpactItems->isNotEmpty())
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/60">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-200">{{ $resolvedImpactTitle }}</p>
                <ul class="mt-2 space-y-1 text-xs text-gray-700 dark:text-gray-300">
                    @foreach ($resolvedImpactItems as $impactItem)
                        <li class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-info mt-0.5 text-[11px] text-indigo-500 dark:text-indigo-300" aria-hidden="true"></i>
                            <span>{{ $impactItem }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-3 rounded-lg border px-3 py-2 text-xs {{ $resolvedNoticeClass }}">
            <p class="flex items-start gap-2">
                <i class="fa-solid fa-bell mt-0.5 text-[11px]" aria-hidden="true"></i>
                <span>{{ $resolvedNoticeMessage }}</span>
            </p>
        </div>

        <form action="{{ $action }}" method="POST" class="mt-5 flex items-center justify-end gap-2">
            @csrf
            @if (!in_array($normalizedMethod, ['GET', 'POST'], true))
                @method($normalizedMethod)
            @endif
            <button type="button" class="btn-secondary-sm" x-data x-on:click.prevent="$dispatch('close-modal', '{{ $resolvedModalName }}')">
                {{ $resolvedCancelLabel }}
            </button>
            <button type="submit" class="{{ $confirmClass }}">
                {{ $resolvedConfirmLabel }}
            </button>
        </form>
    </div>
</x-modal>
