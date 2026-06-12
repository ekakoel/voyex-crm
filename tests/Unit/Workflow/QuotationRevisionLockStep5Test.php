<?php

namespace Tests\Unit\Workflow;

use App\Models\Quotation;
use App\Support\Workflow\QuotationWorkflow;
use PHPUnit\Framework\TestCase;

class QuotationRevisionLockStep5Test extends TestCase
{
    public function test_editable_statuses_match_step5_scope(): void
    {
        $this->assertSame(
            [
                Quotation::STATUS_DRAFT,
                Quotation::STATUS_NEED_VALIDATION,
                Quotation::STATUS_PENDING_VALIDATION,
                Quotation::STATUS_VALIDATED,
                Quotation::STATUS_READY_TO_SEND,
                Quotation::STATUS_REVISION_REQUESTED,
                Quotation::STATUS_UNDER_REVISION,
                Quotation::STATUS_NEED_REVALIDATION,
                Quotation::STATUS_PENDING_REVALIDATION,
                'booking_issue',
                'operation_adjustment',
                'finalized',
            ],
            QuotationWorkflow::editableStatuses()
        );
    }

    public function test_locked_statuses_require_revision(): void
    {
        $lockedStatuses = [
            Quotation::STATUS_SENT,
            Quotation::STATUS_CUSTOMER_APPROVED,
            Quotation::STATUS_APPROVED,
            Quotation::STATUS_BOOKING_CREATED,
            Quotation::STATUS_CONVERTED_TO_BOOKING,
            Quotation::STATUS_IN_OPERATION,
            Quotation::STATUS_COMPLETED,
        ];

        foreach ($lockedStatuses as $status) {
            $quotation = new Quotation();
            $quotation->status = $status;

            $this->assertTrue($quotation->isLockedForDirectEdit(), 'Failed asserting locked status: ' . $status);
        }
    }
}
