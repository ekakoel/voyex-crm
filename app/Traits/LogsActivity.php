<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            $model->logActivity('created', $model);
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            if ($changes === []) {
                return;
            }

            $model->logActivity('updated', $model, [
                'changed' => array_keys($changes),
                'before' => array_intersect_key($model->getOriginal(), $changes),
                'after' => $changes,
            ]);
        });

        static::deleted(function (Model $model): void {
            $model->logActivity('deleted', $model);
        });
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
