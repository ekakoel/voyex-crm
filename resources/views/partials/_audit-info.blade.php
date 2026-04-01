@php
    $record = $record ?? null;
    $title = $title ?? 'Audit Info';
@endphp

@if ($record)
    <div class="module-card p-6 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ $title }}</p>
        <dl class="space-y-2 text-xs text-gray-700 dark:text-gray-200">
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500 dark:text-gray-400">Created By</dt>
                <dd class="font-medium text-right">{{ $record->creator?->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500 dark:text-gray-400">Created At</dt>
                <dd class="font-medium text-right"><x-local-time :value="$record->created_at" /></dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500 dark:text-gray-400">Updated By</dt>
                <dd class="font-medium text-right">{{ $record->updater?->name ?? '-' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt class="text-gray-500 dark:text-gray-400">Updated At</dt>
                <dd class="font-medium text-right"><x-local-time :value="$record->updated_at" /></dd>
            </div>
        </dl>
    </div>
@endif

