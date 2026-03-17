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
                $timestamp = $activity->created_at?->format('Y-m-d H:i') ?? '-';
                $userName = $activity->user?->name ?? '-';
            @endphp
            - {{ $label }} ({{ $timestamp }}) by {{ $userName }}
        </div>
    @empty
        <div class="text-sm text-gray-500 dark:text-gray-400">No activity yet.</div>
    @endforelse
</div>
