<?php

namespace App\Services\Quotation;

use App\Models\Quotation;
use App\Models\QuotationFollowUp;
use App\Models\QuotationFollowUpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuotationFollowUpService
{
    public const ALLOWED_STATUSES = [
        'sent',
        'pending',
        'need_revalidation',
        'pending_revalidation',
        'revision_requested',
        'under_revision',
    ];

    public function __construct(
        private readonly QuotationFollowUpNotificationService $notificationService
    ) {
    }

    public function canRecord(Quotation $quotation): bool
    {
        return in_array(Quotation::normalizeStatus((string) ($quotation->status ?? '')), self::ALLOWED_STATUSES, true);
    }

    public function record(Quotation $quotation, array $data, ?int $actorId = null): QuotationFollowUp
    {
        return DB::transaction(function () use ($quotation, $data, $actorId): QuotationFollowUp {
            $quotation->refresh();
            if (! $this->canRecord($quotation)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'Follow-up can only be recorded for sent, pending, pending revalidation, or under revision quotation.',
                ]);
            }

            $quotation->loadMissing('inquiry');
            $customerId = (int) ($quotation->inquiry?->customer_id ?? 0) ?: null;
            $handledBy = $this->notificationService->resolveNotificationUserId($quotation, null);
            $followUpAt = isset($data['follow_up_at']) ? now()->parse((string) $data['follow_up_at']) : now();
            $nextFollowUpAt = $followUpAt->copy()->addDay();

            $followUp = QuotationFollowUp::query()->create([
                'quotation_id' => (int) $quotation->id,
                'customer_id' => $customerId,
                'handled_by' => $handledBy,
                'channel' => $data['channel'] ?? null,
                'follow_up_type' => 'customer_follow_up',
                'follow_up_note' => $data['follow_up_note'] ?? null,
                'follow_up_at' => $followUpAt,
                'next_follow_up_at' => $nextFollowUpAt,
                'created_by' => $actorId,
            ]);

            $patch = [
                'last_followed_up_at' => $followUpAt,
                'follow_up_count' => DB::raw('COALESCE(follow_up_count, 0) + 1'),
                'next_follow_up_at' => $nextFollowUpAt,
                'follow_up_status' => 'followed_up',
                'approval_status' => 'waiting_customer_response',
                'current_stage' => 'customer_follow_up',
                'next_action' => 'follow_up_customer',
            ];

            $safePatch = [];
            foreach ($patch as $column => $value) {
                if (Schema::hasColumn('quotations', $column)) {
                    $safePatch[$column] = $value;
                }
            }
            if ($safePatch !== []) {
                DB::table('quotations')->where('id', (int) $quotation->id)->update($safePatch);
            }

            $this->notificationService->markUnreadByTypesAsReadForQuotation(
                $quotation,
                [
                    QuotationFollowUpNotification::TYPE_FOLLOW_UP_DUE,
                    QuotationFollowUpNotification::TYPE_FOLLOW_UP_OVERDUE,
                ],
                $handledBy
            );

            return $followUp;
        });
    }
}
