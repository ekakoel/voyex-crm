<?php

namespace App\Support\Workflow;

use App\Enums\QuotationStatus;

class QuotationStatusNormalizer
{
    private const LOGICAL_STATUS_MAP = [
        'pending_validation' => 'need_validation',
        'need_validation' => 'need_validation',
        'validated' => 'ready_to_send',
        'pending_revalidation' => 'need_revalidation',
        'need_revalidation' => 'need_revalidation',
        'customer_approved' => 'approved',
        'approved' => 'approved',
        'booking_created' => 'converted_to_booking',
        'converted_to_booking' => 'converted_to_booking',
        'booking_in_progress' => 'booking_in_progress',
        'booking_issue' => 'booking_issue',
        'invoiced' => 'invoiced',
        'waiting_payment' => 'waiting_payment',
        'in_operation' => 'in_operation',
        'operation_adjustment' => 'operation_adjustment',
        'finalized' => 'finalized',
        'converted' => 'converted_to_booking',
        'accepted' => 'approved',
        'rejected' => 'rejected',
        'lost' => 'lost',
        'cancelled' => 'cancelled',
        'ready_to_send' => 'ready_to_send',
        'sent' => 'sent',
        'revision_requested' => 'revision_requested',
        'under_revision' => 'under_revision',
        'draft' => 'draft',
        'completed' => 'completed',
    ];

    public static function normalize(?string $status): string
    {
        $primary = self::primary($status);

        return self::LOGICAL_STATUS_MAP[$primary] ?? self::LOGICAL_STATUS_MAP[strtolower(trim((string) $status))] ?? $primary;
    }

    public static function isFinal(?string $status): bool
    {
        return in_array(self::normalize($status), [
            'approved',
            'booking_in_progress',
            'converted_to_booking',
            'completed',
            'lost',
            'cancelled',
            'rejected',
        ], true);
    }

    public static function isApproved(?string $status): bool
    {
        return self::normalize($status) === 'approved';
    }

    public static function isReadyToSend(?string $status): bool
    {
        return self::normalize($status) === 'ready_to_send';
    }

    public static function primary(?string $status): string
    {
        return QuotationStatus::normalize($status);
    }

    public static function approval(?string $status): string
    {
        return match (self::primary($status)) {
            QuotationStatus::CustomerApproved->value,
            QuotationStatus::Approved->value,
            QuotationStatus::ConvertedToBooking->value,
            QuotationStatus::BookingCreated->value,
            QuotationStatus::BookingInProgress->value,
            QuotationStatus::Invoiced->value,
            QuotationStatus::WaitingPayment->value,
            QuotationStatus::InOperation->value,
            QuotationStatus::OperationAdjustment->value,
            QuotationStatus::Finalized->value,
            QuotationStatus::Completed->value => QuotationStatus::Approved->value,
            QuotationStatus::Lost->value => QuotationStatus::Lost->value,
            QuotationStatus::Rejected->value => QuotationStatus::Rejected->value,
            QuotationStatus::Cancelled->value => QuotationStatus::Cancelled->value,
            QuotationStatus::RevisionRequested->value => 'revision_requested',
            QuotationStatus::UnderRevision->value => 'revision_requested',
            QuotationStatus::Sent->value,
            QuotationStatus::Pending->value => 'waiting_customer_response',
            default => 'not_ready',
        };
    }

    public static function send(?string $status): string
    {
        return match (self::primary($status)) {
            QuotationStatus::Sent->value,
            QuotationStatus::CustomerApproved->value,
            QuotationStatus::Approved->value,
            QuotationStatus::ConvertedToBooking->value,
            QuotationStatus::BookingCreated->value,
            QuotationStatus::BookingInProgress->value,
            QuotationStatus::Invoiced->value,
            QuotationStatus::WaitingPayment->value,
            QuotationStatus::InOperation->value,
            QuotationStatus::OperationAdjustment->value,
            QuotationStatus::Finalized->value,
            QuotationStatus::Completed->value => 'sent',
            QuotationStatus::Validated->value,
            QuotationStatus::ReadyToSend->value => 'ready_to_send',
            default => 'not_sent',
        };
    }

    public static function currentStage(?string $status): string
    {
        return match (self::primary($status)) {
            QuotationStatus::Draft->value => 'quotation_draft',
            QuotationStatus::PendingValidation->value,
            QuotationStatus::NeedValidation->value,
            QuotationStatus::PendingRevalidation->value => 'validation',
            QuotationStatus::NeedRevalidation->value => 'validation',
            QuotationStatus::Validated->value,
            QuotationStatus::ReadyToSend->value => 'ready_to_send',
            QuotationStatus::Sent->value,
            QuotationStatus::Pending->value => 'customer_follow_up',
            QuotationStatus::CustomerApproved->value,
            QuotationStatus::Approved->value => 'booking_preparation',
            QuotationStatus::RevisionRequested->value,
            QuotationStatus::UnderRevision->value => 'quotation_revision',
            QuotationStatus::ConvertedToBooking->value,
            QuotationStatus::BookingCreated->value,
            QuotationStatus::BookingInProgress->value,
            QuotationStatus::BookingIssue->value => 'booking',
            QuotationStatus::Invoiced->value => 'invoice',
            QuotationStatus::WaitingPayment->value => 'payment',
            QuotationStatus::InOperation->value,
            QuotationStatus::OperationAdjustment->value => 'operation',
            QuotationStatus::Finalized->value => 'final_invoice',
            QuotationStatus::Completed->value => 'completed',
            QuotationStatus::Cancelled->value => 'cancelled',
            QuotationStatus::Lost->value => 'lost',
            default => 'quotation',
        };
    }
}
