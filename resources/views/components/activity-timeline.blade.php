@props(['activities'])

@php
    $actionLabels = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'reminder_added' => 'Reminder added',
        'reminder_done' => 'Reminder done',
        'communication_added' => 'Communication added',
    ];
@endphp

<div class="space-y-2">
    @forelse ($activities as $activity)
        <div class="text-sm text-gray-600 dark:text-gray-300">
            @php
                $label = $actionLabels[$activity->action] ?? ucfirst(str_replace('_', ' ', (string) $activity->action));
                $userName = $activity->user?->name ?? '-';
            @endphp
            - {{ $label }} (<x-local-time :value="$activity->created_at" />) by {{ $userName }}
        </div>
    @empty
        <div class="text-sm text-gray-500 dark:text-gray-400">No activity yet.</div>
    @endforelse
</div>
