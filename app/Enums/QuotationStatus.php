<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'draft';
    case NeedValidation = 'need_validation';
    case PendingValidation = 'pending_validation';
    case Validated = 'validated';
    case ReadyToSend = 'ready_to_send';
    case Sent = 'sent';
    case RevisionRequested = 'revision_requested';
    case UnderRevision = 'under_revision';
    case NeedRevalidation = 'need_revalidation';
    case PendingRevalidation = 'pending_revalidation';
    case CustomerApproved = 'customer_approved';
    case Approved = 'approved';
    case ConvertedToBooking = 'converted_to_booking';
    case BookingCreated = 'booking_created';
    case BookingInProgress = 'booking_in_progress';
    case BookingIssue = 'booking_issue';
    case Invoiced = 'invoiced';
    case WaitingPayment = 'waiting_payment';
    case Pending = 'pending';
    case InOperation = 'in_operation';
    case OperationAdjustment = 'operation_adjustment';
    case Finalized = 'finalized';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Lost = 'lost';

    public static function normalize(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        if ($normalized === '') {
            return self::Draft->value;
        }

        return match ($normalized) {
            'accepted' => self::CustomerApproved->value,
            'converted' => self::BookingCreated->value,
            'valid' => self::Validated->value,
            'final' => self::Completed->value,
            default => $normalized,
        };
    }

    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }

    public static function isKnown(?string $status): bool
    {
        return in_array(self::normalize($status), self::values(), true);
    }

    public static function mainFlow(): array
    {
        return [
            self::Draft->value,
            self::NeedValidation->value,
            self::ReadyToSend->value,
            self::Sent->value,
            self::RevisionRequested->value,
            self::UnderRevision->value,
            self::NeedRevalidation->value,
            self::Approved->value,
            self::BookingInProgress->value,
            self::ConvertedToBooking->value,
            self::Invoiced->value,
            self::WaitingPayment->value,
            self::InOperation->value,
            self::Finalized->value,
            self::Completed->value,
            self::Rejected->value,
            self::Lost->value,
            self::Cancelled->value,
        ];
    }

    public static function terminalStatuses(): array
    {
        return [
            self::Completed->value,
            self::Cancelled->value,
            self::Rejected->value,
            self::Lost->value,
        ];
    }
}
