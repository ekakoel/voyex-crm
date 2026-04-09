<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static bool $suppressAutomaticActivityLogging = false;

    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            if (static::$suppressAutomaticActivityLogging) {
                return;
            }
            $attributes = $model->getAttributes();
            $ignored = ['created_at', 'updated_at', 'deleted_at'];
            $changes = [];
            foreach ($attributes as $field => $value) {
                if (in_array((string) $field, $ignored, true)) {
                    continue;
                }
                $changes[] = [
                    'field' => (string) $field,
                    'label' => ucwords(str_replace('_', ' ', (string) $field)),
                    'from' => null,
                    'to' => $value,
                ];
            }

            $model->logActivity('created', $model, [
                'changes' => $changes,
            ]);
        });

        static::updated(function (Model $model): void {
            if (static::$suppressAutomaticActivityLogging) {
                return;
            }
            $changes = $model->getChanges();
            foreach (['updated_at', 'created_at', 'deleted_at', 'updated_by'] as $ignoredField) {
                unset($changes[$ignoredField]);
            }
            if ($changes === []) {
                return;
            }

            $before = array_intersect_key($model->getOriginal(), $changes);
            $changeRows = [];
            foreach ($changes as $field => $afterValue) {
                $changeRows[] = [
                    'field' => (string) $field,
                    'label' => ucwords(str_replace('_', ' ', (string) $field)),
                    'from' => $before[$field] ?? null,
                    'to' => $afterValue,
                ];
            }

            $model->logActivity('updated', $model, [
                'changed' => array_keys($changes),
                'before' => $before,
                'after' => $changes,
                'changes' => $changeRows,
            ]);
        });

        static::deleted(function (Model $model): void {
            if (static::$suppressAutomaticActivityLogging) {
                return;
            }
            $model->logActivity('deleted', $model);
        });
    }

    public static function withoutActivityLogging(callable $callback): mixed
    {
        $previous = static::$suppressAutomaticActivityLogging;
        static::$suppressAutomaticActivityLogging = true;

        try {
            return $callback();
        } finally {
            static::$suppressAutomaticActivityLogging = $previous;
        }
    }

    public function logActivity(string $action, Model $model, array $properties = []): void
    {
        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'module' => class_basename($model),
            'action' => $action,
            'subject_id' => $model->getKey(),
            'subject_type' => $model->getMorphClass(),
            'properties' => $properties !== [] ? $properties : null,
        ]);
    }
}
