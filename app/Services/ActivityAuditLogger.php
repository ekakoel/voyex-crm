<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityAuditLogger
{
    public function __construct(
        private readonly AuditDiffService $auditDiffService
    ) {
    }

    public function logCreated(Model $subject, array $after, ?string $module = null): void
    {
        $changes = $this->auditDiffService->created($after);
        $this->write('created', $subject, $changes, $module);
    }

    public function logUpdated(Model $subject, array $before, array $after, ?string $module = null): void
    {
        $changes = $this->auditDiffService->diff($before, $after);
        if ($changes === []) {
            return;
        }

        $this->write('updated', $subject, $changes, $module);
    }

    /**
     * @param array<int, array{field:string,label:string,from:mixed,to:mixed}> $changes
     */
    private function write(string $action, Model $subject, array $changes, ?string $module = null): void
    {
        $userId = auth()->id();
        $subjectType = $subject->getMorphClass();
        $subjectId = $subject->getKey();
        $moduleName = $module ?: class_basename($subject);

        $latest = ActivityLog::query()
            ->where('user_id', $userId)
            ->where('module', $moduleName)
            ->where('action', $action)
            ->where('subject_id', $subjectId)
            ->where('subject_type', $subjectType)
            ->latest('id')
            ->first();

        if ($latest) {
            $latestChanges = collect($latest->properties['changes'] ?? [])->values()->all();
            $isSameChanges = $latestChanges === array_values($changes);
            $isNearInTime = $latest->created_at && $latest->created_at->diffInSeconds(now()) <= 2;

            // Defensive dedupe: avoid writing identical log twice in rapid succession.
            if ($isSameChanges && $isNearInTime) {
                return;
            }
        }

        ActivityLog::query()->create([
            'user_id' => $userId,
            'module' => $moduleName,
            'action' => $action,
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'properties' => [
                'changes' => $changes,
                'performed_at' => now()->toIso8601String(),
                'changed_count' => count($changes),
            ],
        ]);
    }
}
