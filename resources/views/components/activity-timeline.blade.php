@props([
    'activities',
])

@php
    $actionLabels = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'duplicated_from' => 'Duplicated From',
        'reminder_added' => 'Reminder added',
        'reminder_done' => 'Reminder done',
        'communication_added' => 'Communication added',
    ];
@endphp

<div class="space-y-2">
    @forelse ($activities as $activity)
        @php
            $properties = is_array($activity->properties ?? null) ? $activity->properties : [];
            $changes = collect($properties['changes'] ?? [])->filter(function ($change) {
                return is_array($change)
                    && array_key_exists('field', $change)
                    && (string) ($change['field'] ?? '') !== 'is_active';
            })->values();

            if ($changes->isEmpty() && is_array($properties['before'] ?? null) && is_array($properties['after'] ?? null)) {
                $before = $properties['before'];
                $after = $properties['after'];
                foreach ($after as $field => $value) {
                    if ((string) $field === 'is_active') {
                        continue;
                    }
                    $changes->push([
                        'field' => (string) $field,
                        'label' => ucwords(str_replace('_', ' ', (string) $field)),
                        'from' => $before[$field] ?? null,
                        'to' => $value,
                    ]);
                }
            }

            $formatValue = static function ($value): string {
                if ($value === null || $value === '') {
                    return '-';
                }
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }
                if (is_array($value)) {
                    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return \Illuminate\Support\Str::limit((string) $encoded, 140);
                }

                return \Illuminate\Support\Str::limit((string) $value, 140);
            };
        @endphp
        <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
            @php
                $label = $actionLabels[$activity->action] ?? \Illuminate\Support\Str::headline((string) $activity->action);
                $userName = $activity->user?->name ?? '-';
                $modalName = 'activity-log-' . (int) $activity->id;
            @endphp
            <div class="flex items-center justify-between gap-3">
                <p class="min-w-0 flex-1 truncate text-xs text-gray-700 dark:text-gray-200">
                    <span class="font-semibold">{{ $label }}</span>
                    <span> <x-local-time :value="$activity->created_at" /></span>
                    <span> - {{ $userName }}</span>
                </p>
                <button
                    type="button"
                    class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    x-data
                    x-on:click.prevent="$dispatch('open-modal', '{{ $modalName }}')"
                    title="View log detail"
                    aria-label="View log detail"
                >
                    <i class="fa-solid fa-eye text-[11px]"></i>
                </button>
            </div>
        </div>

        <x-modal name="{{ $modalName }}" focusable maxWidth="2xl">
            <div class="p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $label }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $label }} <x-local-time :value="$activity->created_at" /> - {{ $userName }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-ghost px-2 py-1 text-xs"
                        x-on:click.prevent="$dispatch('close-modal', '{{ $modalName }}')"
                    >
                        Close
                    </button>
                </div>

                @if ($changes->isNotEmpty())
                    <div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                        @foreach ($changes as $change)
                            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-200">
                                <p class="font-semibold">{{ $change['label'] ?? $change['field'] ?? 'Field' }}</p>
                                <p class="mt-1">
                                    <span class="text-gray-500 dark:text-gray-400">From:</span>
                                    <span>{{ $formatValue($change['from'] ?? null) }}</span>
                                </p>
                                <p>
                                    <span class="text-gray-500 dark:text-gray-400">To:</span>
                                    <span>{{ $formatValue($change['to'] ?? null) }}</span>
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No detailed field changes available for this log.</p>
                @endif
            </div>
        </x-modal>
    @empty
        <div class="text-sm text-gray-500 dark:text-gray-400">No activity yet.</div>
    @endforelse
</div>
