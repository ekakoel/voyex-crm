<?php

namespace Tests\Unit\Workflow;

use App\Models\Quotation;
use App\Support\Workflow\QuotationWorkflow;
use PHPUnit\Framework\TestCase;

class QuotationLifecycleStep3Test extends TestCase
{
    public function test_draft_can_move_to_pending_validation(): void
    {
        $this->assertTrue(QuotationWorkflow::canTransition(Quotation::STATUS_DRAFT, Quotation::STATUS_PENDING_VALIDATION));
    }

    public function test_ready_to_send_can_be_marked_as_sent(): void
    {
        $this->assertTrue(QuotationWorkflow::canTransition(Quotation::STATUS_VALIDATED, Quotation::STATUS_SENT));
        $this->assertTrue(QuotationWorkflow::canTransition(Quotation::STATUS_VALIDATED, Quotation::STATUS_READY_TO_SEND));
        $this->assertTrue(QuotationWorkflow::canTransition(Quotation::STATUS_READY_TO_SEND, Quotation::STATUS_SENT));
    }

    public function test_sent_can_be_marked_as_customer_approved(): void
    {
        $this->assertTrue(QuotationWorkflow::canTransition(Quotation::STATUS_SENT, Quotation::STATUS_CUSTOMER_APPROVED));
    }

    public function test_draft_cannot_jump_directly_to_customer_approved(): void
    {
        $this->assertFalse(QuotationWorkflow::canTransition(Quotation::STATUS_DRAFT, Quotation::STATUS_CUSTOMER_APPROVED));
    }

    public function test_customer_approved_is_locked_for_direct_edit(): void
    {
        $quotation = new Quotation();
        $quotation->status = Quotation::STATUS_CUSTOMER_APPROVED;

        $this->assertTrue($quotation->isLockedForDirectEdit());
        $this->assertFalse(in_array(Quotation::STATUS_CUSTOMER_APPROVED, QuotationWorkflow::editableStatuses(), true));
    }
}
