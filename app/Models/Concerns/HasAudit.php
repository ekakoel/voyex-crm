<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Schema;

trait HasAudit
{
    protected static array $auditColumnCache = [];

    protected static function hasAuditColumn(string $table, string $column): bool
    {
        $key = $table . ':' . $column;
        if (! array_key_exists($key, static::$auditColumnCache)) {
            static::$auditColumnCache[$key] = Schema::hasColumn($table, $column);
        }

        return static::$auditColumnCache[$key];
    }

    protected static function bootHasAudit(): void
    {
        static::creating(function ($model): void {
            $userId = auth()->id();
            if (! $userId) {
                return;
            }

            $table = $model->getTable();
            if (static::hasAuditColumn($table, 'created_by') && empty($model->created_by)) {
                $model->created_by = $userId;
            }
            if (static::hasAuditColumn($table, 'updated_by')) {
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model): void {
            $userId = auth()->id();
            if (! $userId) {
                return;
            }

            $table = $model->getTable();
            if (static::hasAuditColumn($table, 'updated_by')) {
                $model->updated_by = $userId;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
