<?php

namespace App\Services\Quotation;

use App\Models\Quotation;
use App\Models\QuotationFollowUpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class QuotationFollowUpNotificationService
{
    public function createForQuotation(
        Quotation $quotation,
        string $type,
        string $title,
        ?string $message = null,
        string $severity = 'info',
        $dueAt = null,
        ?int $userId = null
    ): ?QuotationFollowUpNotification {
        if (! Schema::hasTable('quotation_follow_up_notifications')) {
            return null;
        }

        $assigneeId = $this->resolveNotificationUserId($quotation, $userId);
        if (! $assigneeId) {
            return null;
        }

        return QuotationFollowUpNotification::query()->firstOrCreate(
            [
                'quotation_id' => (int) $quotation->id,
                'user_id' => $assigneeId,
                'notification_type' => $type,
                'is_read' => false,
            ],
            [
                'title' => $title,
                'message' => $message,
                'icon' => 'fa-headset',
                'severity' => $severity,
                'action_url' => route('quotations.show', $quotation),
                'due_at' => $dueAt ?: now(),
            ]
        );
    }

    public function unreadPayloadForUser($user): array
    {
        if (! $user || ! Schema::hasTable('quotation_follow_up_notifications')) {
            return [
                'enabled' => false,
                'count' => 0,
                'latest' => null,
                'items' => [],
            ];
        }

        $query = QuotationFollowUpNotification::query()
            ->with(['quotation.inquiry.customer'])
            ->where('user_id', (int) $user->id)
            ->where('is_read', false)
            ->latest('due_at')
            ->latest('id');

        $count = (clone $query)->count();
        $items = (clone $query)->limit(8)->get()->map(function (QuotationFollowUpNotification $notification): array {
            $quotation = $notification->quotation;

            return [
                'id' => (int) $notification->id,
                'title' => (string) $notification->title,
                'message' => (string) ($notification->message ?? ''),
                'severity' => (string) ($notification->severity ?? 'info'),
                'type' => (string) $notification->notification_type,
                'icon' => (string) ($notification->icon ?? 'fa-headset'),
                'action_url' => (string) ($notification->action_url ?: ($quotation ? route('quotations.show', $quotation) : '#')),
                'due_at' => optional($notification->due_at)->toIso8601String(),
                'quotation_number' => (string) ($quotation?->quotation_number ?? '-'),
                'customer_name' => (string) ($quotation?->inquiry?->customer?->name ?? $quotation?->inquiry?->customer?->company_name ?? '-'),
            ];
        })->values()->all();

        return [
            'enabled' => true,
            'count' => max(0, (int) $count),
            'latest' => $items[0] ?? null,
            'items' => $items,
        ];
    }

    public function markRead(QuotationFollowUpNotification $notification, int $userId): void
    {
        if ((int) ($notification->user_id ?? 0) !== $userId) {
            return;
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function markUnreadByTypesAsReadForQuotation(Quotation $quotation, array $types, ?int $userId = null): int
    {
        if (! Schema::hasTable('quotation_follow_up_notifications') || $types === []) {
            return 0;
        }

        $resolvedUserId = $this->resolveNotificationUserId($quotation, $userId);
        if (! $resolvedUserId) {
            return 0;
        }

        return (int) DB::table('quotation_follow_up_notifications')
            ->where('quotation_id', (int) $quotation->id)
            ->where('user_id', (int) $resolvedUserId)
            ->whereIn('notification_type', $types)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function resolveNotificationUserId(Quotation $quotation, ?int $preferredUserId = null): ?int
    {
        if ($preferredUserId && $this->userExists($preferredUserId)) {
            return $preferredUserId;
        }

        $quotation->loadMissing('inquiry');
        $candidates = [
            Schema::hasColumn('quotations', 'handled_by') ? (int) ($quotation->handled_by ?? 0) : 0,
            Schema::hasColumn('quotations', 'created_by') ? (int) ($quotation->created_by ?? 0) : 0,
            Schema::hasColumn('inquiries', 'handled_by') ? (int) ($quotation->inquiry?->handled_by ?? 0) : 0,
            Schema::hasColumn('inquiries', 'assigned_to') ? (int) ($quotation->inquiry?->assigned_to ?? 0) : 0,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate > 0 && $this->userExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function unreadCountForUser($user): int
    {
        if (! $user || ! Schema::hasTable('quotation_follow_up_notifications')) {
            return 0;
        }

        return (int) QuotationFollowUpNotification::query()
            ->where('user_id', (int) $user->id)
            ->where('is_read', false)
            ->count();
    }

    private function userExists(int $userId): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        return \App\Models\User::query()->whereKey($userId)->exists();
    }
}
