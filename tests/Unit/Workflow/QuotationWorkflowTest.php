<?php

namespace Tests\Unit\Workflow;

use App\Support\Workflow\QuotationWorkflow;
use PHPUnit\Framework\TestCase;

class QuotationWorkflowTest extends TestCase
{
    public function test_statuses_include_expected_values(): void
    {
        $this->assertContains('draft', QuotationWorkflow::statuses());
        $this->assertContains('need_validation', QuotationWorkflow::statuses());
        $this->assertContains('customer_approved', QuotationWorkflow::statuses());
        $this->assertContains('approved', QuotationWorkflow::statuses());
        $this->assertContains('converted_to_booking', QuotationWorkflow::statuses());
        $this->assertContains('completed', QuotationWorkflow::statuses());
    }

    public function test_main_transition_path_is_allowed(): void
    {
        $this->assertTrue(QuotationWorkflow::canTransition('draft', 'pending_validation'));
        $this->assertTrue(QuotationWorkflow::canTransition('pending_validation', 'validated'));
        $this->assertTrue(QuotationWorkflow::canTransition('validated', 'ready_to_send'));
        $this->assertTrue(QuotationWorkflow::canTransition('ready_to_send', 'sent'));
        $this->assertTrue(QuotationWorkflow::canTransition('sent', 'customer_approved'));
        $this->assertTrue(QuotationWorkflow::canTransition('sent', 'approved'));
        $this->assertTrue(QuotationWorkflow::canTransition('approved', 'converted_to_booking'));
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $this->assertFalse(QuotationWorkflow::canTransition('draft', 'customer_approved'));
        $this->assertFalse(QuotationWorkflow::canTransition('completed', 'draft'));
    }

    public function test_locked_status_is_not_editable(): void
    {
        $this->assertContains('completed', QuotationWorkflow::lockedStatuses());
        $this->assertContains('sent', QuotationWorkflow::lockedStatuses());
        $this->assertNotContains('completed', QuotationWorkflow::editableStatuses());
        $this->assertNotContains('sent', QuotationWorkflow::editableStatuses());
    }

    public function test_editable_status_is_editable(): void
    {
        $this->assertContains('draft', QuotationWorkflow::editableStatuses());
        $this->assertNotContains('draft', QuotationWorkflow::lockedStatuses());
    }
}
