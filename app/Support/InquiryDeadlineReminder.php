<?php

namespace App\Support;

use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class InquiryDeadlineReminder
{
    public static function queryForUser($user): Builder
    {
        $today = Carbon::today();
        $hasHandledBy = SchemaInspector::hasColumn('inquiries', 'handled_by');
        $hasAssignedTo = SchemaInspector::hasColumn('inquiries', 'assigned_to');

        return Inquiry::query()
            ->whereNull('deleted_at')
            ->whereNotNull('deadline')
            ->where('reminder_enabled', true)
            ->whereDate('deadline', '>=', $today->toDateString())
            ->whereDate('deadline', '<=', $today->copy()->addDays(7)->toDateString())
            ->where(function (Builder $query) use ($today): void {
                $query
                    ->where(function (Builder $priorityQuery) use ($today): void {
                        $priorityQuery
                            ->where('priority', 'low')
                            ->whereDate('deadline', '<=', $today->copy()->addDay()->toDateString());
                    })
                    ->orWhere(function (Builder $priorityQuery) use ($today): void {
                        $priorityQuery
                            ->whereIn('priority', ['normal', 'medium'])
                            ->whereDate('deadline', '<=', $today->copy()->addDays(2)->toDateString());
                    })
                    ->orWhere(function (Builder $priorityQuery) use ($today): void {
                        $priorityQuery
                            ->whereIn('priority', ['high', 'hight'])
                            ->whereDate('deadline', '<=', $today->copy()->addDays(7)->toDateString());
                    });
            })
            ->where(function (Builder $query) use ($user, $hasHandledBy, $hasAssignedTo): void {
                if (! $hasHandledBy && ! $hasAssignedTo) {
                    $query->whereRaw('1 = 0');
                    return;
                }
                if ($hasHandledBy) {
                    $query->where('handled_by', (int) $user->id);
                }
                if ($hasAssignedTo) {
                    $hasHandledBy
                        ? $query->orWhere('assigned_to', (int) $user->id)
                        : $query->where('assigned_to', (int) $user->id);
                }
            })
            ->whereDoesntHave('quotation')
            ->whereDoesntHave('itineraries.quotations');
    }

    public static function daysUntilDeadline(?Carbon $deadline): ?int
    {
        if (! $deadline) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($deadline->copy()->startOfDay(), false);
    }

    public static function reminderLabel(?Carbon $deadline): string
    {
        $days = self::daysUntilDeadline($deadline);
        if ($days === null) {
            return 'H-?';
        }

        return $days <= 0 ? 'H-0' : 'H-' . $days;
    }
}
