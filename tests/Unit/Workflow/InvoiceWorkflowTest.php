<?php

namespace Tests\Unit\Workflow;

use App\Support\Workflow\InvoiceWorkflow;
use PHPUnit\Framework\TestCase;

class InvoiceWorkflowTest extends TestCase
{
    public function test_statuses_include_expected_values(): void
    {
        $this->assertContains('draft', InvoiceWorkflow::statuses());
        $this->assertContains('issued', InvoiceWorkflow::statuses());
        $this->assertContains('paid', InvoiceWorkflow::statuses());
    }

    public function test_main_transition_path_is_allowed(): void
    {
        $this->assertTrue(InvoiceWorkflow::canTransition('draft', 'issued'));
        $this->assertTrue(InvoiceWorkflow::canTransition('issued', 'partially_paid'));
        $this->assertTrue(InvoiceWorkflow::canTransition('partially_paid', 'paid'));
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $this->assertFalse(InvoiceWorkflow::canTransition('draft', 'paid'));
        $this->assertFalse(InvoiceWorkflow::canTransition('paid', 'revised'));
    }

    public function test_locked_status_is_not_editable(): void
    {
        $this->assertContains('paid', InvoiceWorkflow::lockedStatuses());
        $this->assertNotContains('paid', InvoiceWorkflow::editableStatuses());
    }

    public function test_editable_status_is_editable(): void
    {
        $this->assertContains('draft', InvoiceWorkflow::editableStatuses());
        $this->assertNotContains('draft', InvoiceWorkflow::lockedStatuses());
    }
}

