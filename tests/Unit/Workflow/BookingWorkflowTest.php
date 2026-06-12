<?php

namespace Tests\Unit\Workflow;

use App\Support\Workflow\BookingWorkflow;
use PHPUnit\Framework\TestCase;

class BookingWorkflowTest extends TestCase
{
    public function test_statuses_include_expected_values(): void
    {
        $this->assertContains('created', BookingWorkflow::statuses());
        $this->assertContains('in_operation', BookingWorkflow::statuses());
        $this->assertContains('closed', BookingWorkflow::statuses());
    }

    public function test_main_transition_path_is_allowed(): void
    {
        $this->assertTrue(BookingWorkflow::canTransition('created', 'vendor_confirmation'));
        $this->assertTrue(BookingWorkflow::canTransition('vendor_confirmation', 'voucher_preparation'));
        $this->assertTrue(BookingWorkflow::canTransition('voucher_preparation', 'ready_to_operate'));
        $this->assertTrue(BookingWorkflow::canTransition('in_operation', 'service_completed'));
    }

    public function test_invalid_transition_is_blocked(): void
    {
        $this->assertFalse(BookingWorkflow::canTransition('created', 'in_operation'));
        $this->assertFalse(BookingWorkflow::canTransition('closed', 'reconciliation'));
    }

    public function test_locked_status_is_not_editable(): void
    {
        $this->assertContains('closed', BookingWorkflow::lockedStatuses());
        $this->assertNotContains('closed', BookingWorkflow::editableStatuses());
    }

    public function test_editable_status_is_editable(): void
    {
        $this->assertContains('created', BookingWorkflow::editableStatuses());
        $this->assertNotContains('created', BookingWorkflow::lockedStatuses());
    }
}

