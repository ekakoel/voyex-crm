<?php

namespace Tests\Unit\Workflow;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Support\Workflow\QuotationWorkflow;
use PHPUnit\Framework\TestCase;

class OperationalCommercialGuardrailsTest extends TestCase
{
    public function test_draft_quotation_cannot_be_directly_customer_approved(): void
    {
        $this->assertFalse(
            QuotationWorkflow::canTransition(Quotation::STATUS_DRAFT, Quotation::STATUS_CUSTOMER_APPROVED)
        );
    }

    public function test_only_customer_approved_is_eligible_for_booking_transition(): void
    {
        $this->assertTrue(
            QuotationWorkflow::canTransition(Quotation::STATUS_SENT, Quotation::STATUS_CUSTOMER_APPROVED)
        );
        $this->assertFalse(
            QuotationWorkflow::canTransition(Quotation::STATUS_DRAFT, Quotation::STATUS_BOOKING_CREATED)
        );
        $this->assertFalse(
            QuotationWorkflow::canTransition(Quotation::STATUS_VALIDATED, Quotation::STATUS_BOOKING_CREATED)
        );
        $this->assertTrue(
            QuotationWorkflow::canTransition(Quotation::STATUS_APPROVED, Quotation::STATUS_CONVERTED_TO_BOOKING)
        );
    }

    public function test_paid_invoice_is_not_editable(): void
    {
        $invoice = new Invoice();
        $invoice->status = 'paid';
        $this->assertFalse($invoice->isEditable());
    }

    public function test_locked_quotation_requires_revision_not_silent_edit(): void
    {
        $quotation = new Quotation();
        $quotation->status = Quotation::STATUS_SENT;
        $this->assertTrue($quotation->isLockedForDirectEdit());
    }
}
