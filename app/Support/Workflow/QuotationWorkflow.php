<?php

namespace App\Support\Workflow;

class QuotationWorkflow
{
    public static function statuses(): array
    {
        return [
            'draft',
            'need_validation',
            'pending_validation',
            'validated',
            'ready_to_send',
            'sent',
            'revision_requested',
            'customer_approved',
            'approved',
            'under_revision',
            'need_revalidation',
            'pending_revalidation',
            'converted_to_booking',
            'booking_created',
            'booking_in_progress',
            'booking_issue',
            'in_operation',
            'operation_adjustment',
            'finalized',
            'completed',
            'rejected',
            'cancelled',
            'lost',
        ];
    }

    public static function editableStatuses(): array
    {
        return array_values(array_diff(self::statuses(), self::lockedStatuses()));
    }

    public static function lockedStatuses(): array
    {
        return [
            'sent',
            'customer_approved',
            'approved',
            'booking_created',
            'converted_to_booking',
            'booking_in_progress',
            'in_operation',
            'completed',
            'rejected',
            'cancelled',
            'lost',
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        if (! in_array($from, self::statuses(), true) || ! in_array($to, self::statuses(), true)) {
            return false;
        }

        $from = QuotationStatusNormalizer::normalize($from);
        $to = QuotationStatusNormalizer::normalize($to);

        if ($from === $to) {
            return true;
        }

        $map = [
            'draft' => ['need_validation', 'cancelled', 'lost'],
            'need_validation' => ['ready_to_send', 'draft', 'cancelled', 'lost'],
            'ready_to_send' => ['sent', 'need_validation', 'cancelled', 'lost'],
            'sent' => ['approved', 'revision_requested', 'under_revision', 'need_revalidation', 'cancelled', 'lost'],
            'revision_requested' => ['under_revision', 'cancelled', 'lost'],
            'under_revision' => ['need_revalidation', 'ready_to_send', 'cancelled', 'lost'],
            'need_revalidation' => ['under_revision', 'ready_to_send', 'cancelled', 'lost'],
            'approved' => ['converted_to_booking', 'booking_in_progress', 'cancelled'],
            'converted_to_booking' => ['booking_in_progress', 'in_operation', 'cancelled'],
            'booking_in_progress' => ['in_operation', 'cancelled'],
            'in_operation' => ['completed', 'cancelled'],
        ];

        return in_array($to, $map[$from] ?? [], true);
    }

    public static function label(string $status): string
    {
        $status = QuotationStatusNormalizer::normalize($status);

        return ucwords(str_replace('_', ' ', $status));
    }

    public static function color(string $status): string
    {
        $status = QuotationStatusNormalizer::normalize($status);

        $palette = [
            'draft' => 'slate',
            'need_validation' => 'amber',
            'ready_to_send' => 'cyan',
            'sent' => 'indigo',
            'revision_requested' => 'amber',
            'under_revision' => 'amber',
            'need_revalidation' => 'amber',
            'approved' => 'emerald',
            'converted_to_booking' => 'violet',
            'booking_in_progress' => 'violet',
            'in_operation' => 'sky',
            'completed' => 'emerald',
            'rejected' => 'rose',
            'cancelled' => 'rose',
            'lost' => 'rose',
        ];

        return $palette[$status] ?? 'slate';
    }
}
