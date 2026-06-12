<?php

namespace Tests\Unit\Workflow;

use App\Models\Quotation;
use App\Models\QuotationCustomerResponse;
use App\Services\Quotation\QuotationCustomerResponseService;
use App\Services\Quotation\QuotationFollowUpAutomationService;
use App\Services\Quotation\QuotationFollowUpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationFollowUpWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        foreach ([
            'quotation_follow_up_notifications',
            'quotation_customer_responses',
            'quotation_follow_ups',
            'quotation_status_logs',
            'quotations',
            'inquiries',
            'customers',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->string('status', 80)->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->foreignId('inquiry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->string('status', 80)->default('draft');
            $table->string('validation_status', 80)->nullable();
            $table->string('send_status', 80)->nullable();
            $table->string('approval_status', 80)->nullable();
            $table->string('booking_status', 80)->nullable();
            $table->string('invoice_status', 80)->nullable();
            $table->string('payment_status', 80)->nullable();
            $table->string('operation_status', 80)->nullable();
            $table->string('current_stage', 120)->nullable();
            $table->string('next_action')->nullable();
            $table->date('validity_date')->nullable();
            $table->date('service_date')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_followed_up_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->unsignedInteger('follow_up_count')->default(0);
            $table->string('follow_up_status', 80)->nullable();
            $table->timestamp('no_response_warning_at')->nullable();
            $table->timestamp('service_date_warning_at')->nullable();
            $table->string('auto_status_reason', 120)->nullable();
            $table->timestamp('auto_status_updated_at')->nullable();
            $table->boolean('auto_status_locked')->default(false);
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->string('old_status', 80)->nullable();
            $table->string('new_status', 80)->nullable();
            $table->string('old_stage', 120)->nullable();
            $table->string('new_stage', 120)->nullable();
            $table->string('action', 120)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->string('channel', 80)->nullable();
            $table->string('follow_up_type', 80)->nullable();
            $table->text('follow_up_note')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_customer_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->string('response_channel', 80)->nullable();
            $table->string('response_status', 80)->nullable();
            $table->text('response_note')->nullable();
            $table->boolean('requires_revision')->default(false);
            $table->string('revision_type', 80)->nullable();
            $table->string('revision_priority', 40)->nullable();
            $table->json('requested_changes')->nullable();
            $table->boolean('is_used_for_revision')->default(false);
            $table->timestamp('used_for_revision_at')->nullable();
            $table->unsignedBigInteger('used_for_revision_by')->nullable();
            $table->unsignedBigInteger('quotation_revision_id')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_follow_up_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('notification_type', 120);
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('severity', 40)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Reservation',
            'email' => 'reservation@example.test',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('customers')->insert([
            'id' => 1,
            'name' => 'Customer',
            'company_name' => 'Agent Co',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inquiries')->insert([
            'id' => 1,
            'customer_id' => 1,
            'status' => 'quotation_sent',
            'handled_by' => 1,
            'assigned_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'quotation_follow_up_notifications',
            'quotation_customer_responses',
            'quotation_follow_ups',
            'quotation_status_logs',
            'quotations',
            'inquiries',
            'customers',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_reservation_can_record_follow_up_without_changing_approval_status(): void
    {
        $quotation = $this->quotation();

        app(QuotationFollowUpService::class)->record($quotation, [
            'channel' => 'WhatsApp',
            'follow_up_note' => 'Asked for customer decision.',
            'follow_up_at' => now(),
        ], 1);

        $quotation->refresh();
        $this->assertSame('sent', $quotation->status);
        $this->assertSame('waiting_customer_response', $quotation->approval_status);
        $this->assertSame('customer_follow_up', $quotation->current_stage);
        $this->assertSame('follow_up_customer', $quotation->next_action);
        $this->assertSame(1, (int) $quotation->follow_up_count);
        $this->assertNotNull($quotation->next_follow_up_at);
        $this->assertTrue($quotation->next_follow_up_at->greaterThan($quotation->last_followed_up_at));
        $this->assertDatabaseHas('quotation_follow_ups', [
            'quotation_id' => $quotation->id,
            'channel' => 'WhatsApp',
            'follow_up_type' => 'customer_follow_up',
        ]);
    }

    public function test_record_follow_up_marks_due_notifications_as_read(): void
    {
        $quotation = $this->quotation();
        DB::table('quotation_follow_up_notifications')->insert([
            'quotation_id' => $quotation->id,
            'user_id' => 1,
            'notification_type' => 'quotation_follow_up_due',
            'title' => 'Follow-up quotation due today',
            'severity' => 'info',
            'is_read' => false,
            'due_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(QuotationFollowUpService::class)->record($quotation, [
            'channel' => 'WhatsApp',
            'follow_up_note' => 'Done today.',
            'follow_up_at' => now(),
        ], 1);

        $this->assertDatabaseHas('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'notification_type' => 'quotation_follow_up_due',
            'is_read' => true,
        ]);
    }

    public function test_reservation_can_record_customer_response_separately(): void
    {
        $quotation = $this->quotation();

        app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'Email',
            'response_status' => QuotationCustomerResponse::STATUS_APPROVED,
            'response_note' => 'Customer approved the quotation.',
        ], 1);

        $quotation->refresh();
        $this->assertSame('approved', $quotation->status);
        $this->assertDatabaseHas('quotation_customer_responses', [
            'quotation_id' => $quotation->id,
            'response_status' => QuotationCustomerResponse::STATUS_APPROVED,
        ]);
    }

    public function test_customer_response_is_kept_when_status_transition_is_not_available(): void
    {
        $quotation = $this->quotation([
            'status' => 'ready_to_send',
            'send_status' => 'ready_to_send',
            'approval_status' => 'not_ready',
            'current_stage' => 'ready_to_send',
            'next_action' => 'send_to_customer',
        ]);

        app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'Email',
            'response_status' => QuotationCustomerResponse::STATUS_APPROVED,
            'response_note' => 'Customer approved before sent status was marked.',
        ], 1);

        $quotation->refresh();
        $this->assertSame('ready_to_send', $quotation->status);
        $this->assertDatabaseHas('quotation_customer_responses', [
            'quotation_id' => $quotation->id,
            'response_status' => QuotationCustomerResponse::STATUS_APPROVED,
            'response_note' => 'Customer approved before sent status was marked.',
        ]);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'new_status' => 'ready_to_send',
            'action' => 'sync_workflow_dimensions',
        ]);
    }

    public function test_multiple_revision_responses_are_all_recorded_and_remain_pending(): void
    {
        $quotation = $this->quotation([
            'status' => 'ready_to_send',
            'send_status' => 'ready_to_send',
            'approval_status' => 'not_ready',
            'current_stage' => 'ready_to_send',
            'next_action' => 'send_to_customer',
        ]);

        app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'WhatsApp',
            'response_status' => QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
            'response_note' => 'Change room type.',
        ], 1);
        app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'Email',
            'response_status' => QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
            'response_note' => 'Add airport transfer.',
        ], 1);

        $quotation->refresh();
        $pendingIds = $quotation->pendingRevisionCustomerResponses()->pluck('id')->all();

        $this->assertCount(2, $pendingIds);
        $this->assertDatabaseHas('quotation_customer_responses', [
            'quotation_id' => $quotation->id,
            'response_note' => 'Change room type.',
            'requires_revision' => true,
            'is_used_for_revision' => false,
        ]);
        $this->assertDatabaseHas('quotation_customer_responses', [
            'quotation_id' => $quotation->id,
            'response_note' => 'Add airport transfer.',
            'requires_revision' => true,
            'is_used_for_revision' => false,
        ]);
    }

    public function test_revision_requested_response_creates_revision_request(): void
    {
        $quotation = $this->quotation();

        app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'WhatsApp',
            'response_status' => QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
            'response_note' => 'Please add airport transfer.',
        ], 1);

        $quotation->refresh();
        $this->assertSame('revision_requested', $quotation->status);
        $this->assertSame('revise_quotation', $quotation->next_action);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'new_status' => 'revision_requested',
            'action' => 'customer_response_revision_requested',
        ]);
        $this->assertDatabaseHas('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'notification_type' => 'quotation_response_needs_revision',
            'user_id' => 1,
        ]);
    }

    public function test_revision_request_appears_in_revision_reference_query(): void
    {
        $quotation = $this->quotation();
        $response = app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'Line',
            'response_status' => QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
            'response_note' => 'Change pax.',
        ], 1);

        $quotation->refresh();
        $pending = $quotation->pendingRevisionCustomerResponses()->pluck('id')->all();

        $this->assertContains($response->id, $pending);
    }

    public function test_revision_response_can_be_added_while_under_revision_without_resetting_status(): void
    {
        $quotation = $this->quotation([
            'status' => 'under_revision',
            'approval_status' => 'revision_requested',
            'current_stage' => 'quotation_revision',
        ]);

        $response = app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'WhatsApp',
            'response_status' => QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
            'response_note' => 'Please also update the hotel room.',
        ], 1);

        $quotation->refresh();
        $this->assertSame('under_revision', $quotation->status);
        $this->assertFalse((bool) $response->is_used_for_revision);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'new_status' => 'under_revision',
            'action' => 'sync_workflow_dimensions',
        ]);
    }

    public function test_mark_customer_response_as_used_for_revision(): void
    {
        $quotation = $this->quotation();
        $response = app(QuotationCustomerResponseService::class)->record($quotation, [
            'response_channel' => 'Phone',
            'response_status' => QuotationCustomerResponse::STATUS_REVISION_REQUESTED,
            'response_note' => 'Change service date.',
        ], 1);

        $handledCount = app(QuotationCustomerResponseService::class)->markManyUsedForRevision($quotation, [$response->id], 1);

        $response->refresh();
        $this->assertSame(1, $handledCount);
        $this->assertTrue((bool) $response->is_used_for_revision);
        $this->assertNotNull($response->used_for_revision_at);
        $this->assertSame(1, (int) $response->used_for_revision_by);
    }

    public function test_initial_follow_up_record_is_created_once_when_quotation_is_sent(): void
    {
        $quotation = $this->quotation([
            'next_follow_up_at' => null,
            'follow_up_status' => null,
        ]);

        app(QuotationFollowUpAutomationService::class)->ensureInitialFollowUpNotification($quotation);
        app(QuotationFollowUpAutomationService::class)->ensureInitialFollowUpNotification($quotation);

        $quotation->refresh();
        $this->assertSame(1, DB::table('quotation_follow_ups')->where('quotation_id', $quotation->id)->where('follow_up_type', 'quotation_sent')->count());
        $this->assertNotNull($quotation->next_follow_up_at);
        $this->assertTrue($quotation->next_follow_up_at->isFuture());
        $this->assertSame('follow_up_scheduled', (string) $quotation->follow_up_status);
        $this->assertDatabaseHas('quotation_follow_ups', [
            'quotation_id' => $quotation->id,
            'follow_up_type' => 'quotation_sent',
            'channel' => 'system',
        ]);
    }

    public function test_follow_up_due_creates_notification_for_handled_by(): void
    {
        $quotation = $this->quotation(['next_follow_up_at' => now()->subMinute()]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $this->assertDatabaseHas('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'user_id' => 1,
            'notification_type' => 'quotation_follow_up_due',
        ]);
    }

    public function test_follow_up_due_notification_not_created_when_already_followed_up_today(): void
    {
        $quotation = $this->quotation([
            'next_follow_up_at' => now()->subMinute(),
            'last_followed_up_at' => now(),
            'follow_up_status' => 'followed_up',
        ]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $this->assertDatabaseMissing('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'notification_type' => 'quotation_follow_up_due',
        ]);
    }

    public function test_missed_follow_up_is_marked_overdue_and_notified(): void
    {
        $quotation = $this->quotation([
            'next_follow_up_at' => now()->subDays(1)->startOfDay(),
            'last_followed_up_at' => now()->subDays(2),
        ]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $quotation->refresh();
        $this->assertSame('follow_up_overdue', (string) $quotation->follow_up_status);
        $this->assertDatabaseHas('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'notification_type' => 'quotation_follow_up_overdue',
            'user_id' => 1,
        ]);
    }

    public function test_no_response_three_days_creates_warning_not_direct_lost(): void
    {
        $quotation = $this->quotation(['last_sent_at' => now()->subDays(4)]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $quotation->refresh();
        $this->assertSame('sent', $quotation->status);
        $this->assertNotNull($quotation->no_response_warning_at);
        $this->assertDatabaseHas('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'notification_type' => 'quotation_no_response_warning',
        ]);
    }

    public function test_service_date_passed_creates_review_notification_not_direct_cancelled(): void
    {
        $quotation = $this->quotation(['service_date' => now()->subDay()->toDateString()]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $quotation->refresh();
        $this->assertSame('sent', $quotation->status);
        $this->assertNotNull($quotation->service_date_warning_at);
        $this->assertDatabaseHas('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
            'notification_type' => 'quotation_auto_status_review_required',
        ]);
    }

    public function test_after_warning_grace_period_automation_can_set_pending(): void
    {
        $quotation = $this->quotation([
            'last_sent_at' => now()->subDays(5),
            'no_response_warning_at' => now()->subDays(2),
        ]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);
        $this->assertSame('pending_no_response', $quotation->follow_up_status);
        $this->assertSame('no_response_after_warning', $quotation->auto_status_reason);
    }

    public function test_auto_status_locked_prevents_automation_status_change(): void
    {
        $quotation = $this->quotation([
            'service_date' => now()->subDays(3)->toDateString(),
            'service_date_warning_at' => now()->subDays(2),
            'auto_status_locked' => true,
        ]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $quotation->refresh();
        $this->assertSame('sent', $quotation->status);
    }

    public function test_closed_quotations_are_ignored_by_follow_up_automation(): void
    {
        $quotation = $this->quotation([
            'status' => 'customer_approved',
            'next_follow_up_at' => now()->subDay(),
            'last_sent_at' => now()->subDays(4),
            'service_date' => now()->subDay()->toDateString(),
        ]);

        app(QuotationFollowUpAutomationService::class)->processQuotation($quotation);

        $this->assertDatabaseMissing('quotation_follow_up_notifications', [
            'quotation_id' => $quotation->id,
        ]);
    }

    private function quotation(array $overrides = []): Quotation
    {
        $defaults = [
            'quotation_number' => 'QT-' . uniqid(),
            'inquiry_id' => 1,
            'created_by' => 1,
            'handled_by' => null,
            'status' => 'sent',
            'validation_status' => 'valid',
            'send_status' => 'sent',
            'approval_status' => 'waiting_customer_response',
            'current_stage' => 'customer_follow_up',
            'next_action' => 'follow_up_customer',
            'validity_date' => now()->addDays(5)->toDateString(),
            'service_date' => now()->addDays(7)->toDateString(),
            'last_sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('quotations')->insert(array_merge($defaults, $overrides));

        return Quotation::query()->latest('id')->firstOrFail();
    }
}
