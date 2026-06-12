<?php

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait ResolvesInquiryHandler
{
    protected function resolveInquiryHandlerId($inquiry): int
    {
        if (! $inquiry) {
            return 0;
        }

        $table = method_exists($inquiry, 'getTable') ? (string) $inquiry->getTable() : 'inquiries';
        $handlerId = 0;

        if (Schema::hasColumn($table, 'handled_by')) {
            $handlerId = (int) ($inquiry->handled_by ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn($table, 'assigned_to')) {
            $handlerId = (int) ($inquiry->assigned_to ?? 0);
        }
        if ($handlerId <= 0 && Schema::hasColumn($table, 'created_by')) {
            $handlerId = (int) ($inquiry->created_by ?? 0);
        }

        return max(0, $handlerId);
    }

    protected function inquiryHandlerMatchesUser($inquiry, ?int $userId): bool
    {
        $resolvedUserId = max(0, (int) $userId);
        if ($resolvedUserId <= 0) {
            return false;
        }

        $handlerId = $this->resolveInquiryHandlerId($inquiry);

        return $handlerId <= 0 || $handlerId === $resolvedUserId;
    }

    protected function applyInquiryHandlerScope(Builder $query, int $userId, string $table = 'inquiries'): void
    {
        $resolvedUserId = max(0, $userId);
        if ($resolvedUserId <= 0) {
            $query->whereRaw('1 = 0');

            return;
        }

        $hasHandledBy = Schema::hasColumn($table, 'handled_by');
        $hasAssignedTo = Schema::hasColumn($table, 'assigned_to');
        $hasCreatedBy = Schema::hasColumn($table, 'created_by');

        if (! $hasHandledBy && ! $hasAssignedTo && ! $hasCreatedBy) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $handlerQuery) use ($resolvedUserId, $hasHandledBy, $hasAssignedTo, $hasCreatedBy): void {
            if ($hasHandledBy) {
                $handlerQuery->where('handled_by', $resolvedUserId);
            }

            if ($hasAssignedTo) {
                $handlerQuery->orWhere(function (Builder $assignedQuery) use ($resolvedUserId, $hasHandledBy): void {
                    if ($hasHandledBy) {
                        $assignedQuery->where(function (Builder $emptyHandledQuery): void {
                            $emptyHandledQuery->whereNull('handled_by')
                                ->orWhere('handled_by', 0);
                        });
                    }

                    $assignedQuery->where('assigned_to', $resolvedUserId);
                });
            }

            if ($hasCreatedBy) {
                $handlerQuery->orWhere(function (Builder $createdQuery) use ($resolvedUserId, $hasHandledBy, $hasAssignedTo): void {
                    if ($hasHandledBy) {
                        $createdQuery->where(function (Builder $emptyHandledQuery): void {
                            $emptyHandledQuery->whereNull('handled_by')
                                ->orWhere('handled_by', 0);
                        });
                    }

                    if ($hasAssignedTo) {
                        $createdQuery->where(function (Builder $emptyAssignedQuery): void {
                            $emptyAssignedQuery->whereNull('assigned_to')
                                ->orWhere('assigned_to', 0);
                        });
                    }

                    $createdQuery->where('created_by', $resolvedUserId);
                });
            }
        });
    }
}
