<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ManualItemValidationQueueService
{
    public function resolvePendingForSubject(Model $subject, ?int $validatorId = null, string $note = 'updated'): int
    {
        $subjectId = (int) ($subject->getKey() ?? 0);
        if ($subjectId <= 0) {
            return 0;
        }

        $subjectTypes = array_values(array_unique(array_filter([
            get_class($subject),
            is_string($subject->getMorphClass()) ? $subject->getMorphClass() : null,
        ])));

        $logs = ActivityLog::query()
            ->where('module', 'itinerary_day_planner')
            ->where('action', 'manual_item_created')
            ->where('subject_id', $subjectId)
            ->whereIn('subject_type', $subjectTypes)
            ->where(function ($query): void {
                $query->whereNull('properties')
                    ->orWhereRaw("JSON_EXTRACT(properties, '$.validated_at') IS NULL");
            })
            ->get();

        if ($logs->isEmpty()) {
            return 0;
        }

        $validator = $validatorId ? auth()->user() : null;
        $validatorName = trim((string) ($validator?->name ?? 'System'));

        foreach ($logs as $log) {
            $properties = is_array($log->properties) ? $log->properties : [];
            $properties['validated_at'] = now()->toIso8601String();
            $properties['validated_by'] = (int) ($validatorId ?? 0);
            $properties['validated_by_name'] = $validatorName;
            $properties['requires_validation'] = false;
            $properties['resolved_by_update'] = true;
            $properties['resolution_note'] = trim($note) !== '' ? trim($note) : 'updated';

            $log->properties = $properties;
            $log->save();
        }

        return (int) $logs->count();
    }
}
