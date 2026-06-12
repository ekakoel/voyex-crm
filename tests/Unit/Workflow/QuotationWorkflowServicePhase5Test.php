<?php

namespace Tests\Unit\Workflow;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\Quotation\QuotationValidationService;
use App\Services\Quotation\QuotationWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuotationWorkflowServicePhase5Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        foreach (['quotation_customer_responses', 'quotation_status_logs', 'quotation_items', 'quotations', 'users'] as $table) {
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

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->nullable();
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
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->unsignedBigInteger('approval_note_by')->nullable();
            $table->timestamp('approval_note_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->boolean('is_validation_required')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->text('validation_notes')->nullable();
            $table->timestamps();
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

        Schema::create('quotation_customer_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->boolean('requires_revision')->default(false);
            $table->boolean('is_used_for_revision')->default(false);
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Workflow Tester',
            'email' => 'workflow@example.test',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['quotation_customer_responses', 'quotation_status_logs', 'quotation_items', 'quotations', 'users'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_mark_sent_updates_send_status_and_log(): void
    {
        $quotation = $this->quotation('ready_to_send', 'validated');

        app(QuotationWorkflowService::class)->transition($quotation, 'sent', 1, 'mark_sent');

        $quotation->refresh();
        $this->assertSame('sent', $quotation->status);
        $this->assertSame('sent', $quotation->send_status);
        $this->assertSame('waiting_customer_response', $quotation->approval_status);
        $this->assertSame('customer_follow_up', $quotation->current_stage);
        $this->assertSame('follow_up_customer', $quotation->next_action);
        $this->assertNotNull($quotation->last_sent_at);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'old_status' => 'ready_to_send',
            'new_status' => 'sent',
            'action' => 'mark_sent',
        ]);
    }

    public function test_sent_self_transition_does_not_refresh_last_sent_at(): void
    {
        $quotation = $this->quotation('sent', 'valid');
        $originalSentAt = now()->subDays(2);
        DB::table('quotations')->where('id', $quotation->id)->update(['last_sent_at' => $originalSentAt]);

        app(QuotationWorkflowService::class)->transition($quotation, 'sent', 1, 'customer_response_recorded');

        $quotation->refresh();
        $this->assertSame($originalSentAt->toDateTimeString(), $quotation->last_sent_at->toDateTimeString());
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'old_status' => 'sent',
            'new_status' => 'sent',
            'action' => 'customer_response_recorded',
        ]);
    }

    public function test_customer_approved_updates_approval_status_and_log(): void
    {
        $quotation = $this->quotation('sent', 'valid');

        app(QuotationWorkflowService::class)->transition($quotation, 'customer_approved', 1, 'customer_approved');

        $quotation->refresh();
        $this->assertSame('customer_approved', $quotation->status);
        $this->assertSame('approved', $quotation->approval_status);
        $this->assertSame('booking_preparation', $quotation->current_stage);
        $this->assertSame('create_booking', $quotation->next_action);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'old_status' => 'sent',
            'new_status' => 'customer_approved',
        ]);
    }

    public function test_reject_sets_lost_and_writes_log(): void
    {
        $quotation = $this->quotation('sent', 'valid');

        app(QuotationWorkflowService::class)->transition($quotation, 'lost', 1, 'reject');

        $quotation->refresh();
        $this->assertSame('lost', $quotation->status);
        $this->assertSame('lost', $quotation->approval_status);
        $this->assertSame('lost', $quotation->current_stage);
        $this->assertSame('none', $quotation->next_action);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'new_status' => 'lost',
            'action' => 'reject',
        ]);
    }

    public function test_sent_quotation_can_request_revision(): void
    {
        $quotation = $this->quotation('sent', 'valid');

        app(QuotationWorkflowService::class)->transition($quotation, 'under_revision', 1, 'request_revision');

        $quotation->refresh();
        $this->assertSame('under_revision', $quotation->status);
        $this->assertSame('revision_requested', $quotation->approval_status);
        $this->assertSame('quotation_revision', $quotation->current_stage);
        $this->assertSame('revise_quotation', $quotation->next_action);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'old_status' => 'sent',
            'new_status' => 'under_revision',
            'action' => 'request_revision',
        ]);
    }

    public function test_under_revision_can_return_to_ready_to_send(): void
    {
        $quotation = $this->quotation('under_revision', 'valid');

        app(QuotationWorkflowService::class)->transition($quotation, 'ready_to_send', 1, 'save_quotation_revision');

        $quotation->refresh();
        $this->assertSame('ready_to_send', $quotation->status);
        $this->assertSame('ready_to_send', $quotation->send_status);
        $this->assertSame('ready_to_send', $quotation->current_stage);
        $this->assertSame('send_quotation', $quotation->next_action);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'old_status' => 'under_revision',
            'new_status' => 'ready_to_send',
            'action' => 'save_quotation_revision',
        ]);
    }

    public function test_ready_to_send_requires_completed_validation(): void
    {
        $quotation = $this->quotation('pending_validation', 'partial');
        $quotation->items()->create([
            'description' => 'Pending transfer validation',
            'is_validation_required' => true,
            'is_validated' => false,
        ]);

        $service = app(QuotationWorkflowService::class);

        $this->assertFalse($service->canTransition($quotation, 'ready_to_send'));
        $this->assertFalse($service->canTransition($quotation, 'sent'));
    }

    public function test_ready_to_send_is_allowed_when_all_required_items_are_validated(): void
    {
        $quotation = $this->quotation('pending_validation', 'partial');
        $quotation->items()->create([
            'description' => 'Validated transfer',
            'is_validation_required' => true,
            'is_validated' => true,
        ]);

        $service = app(QuotationWorkflowService::class);

        $this->assertTrue($service->canTransition($quotation, 'ready_to_send'));
    }

    public function test_ready_to_send_and_sent_are_blocked_by_unhandled_revision_response(): void
    {
        $quotation = $this->quotation('pending_validation', 'partial');
        $quotation->items()->create([
            'description' => 'Validated transfer',
            'is_validation_required' => true,
            'is_validated' => true,
        ]);
        DB::table('quotation_customer_responses')->insert([
            'quotation_id' => $quotation->id,
            'requires_revision' => true,
            'is_used_for_revision' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(QuotationWorkflowService::class);

        $this->assertFalse($service->canTransition($quotation, 'ready_to_send'));
        $this->assertFalse($service->canTransition($quotation, 'sent'));
    }

    public function test_sent_requires_ready_to_send_status(): void
    {
        $quotation = $this->quotation('validated', 'validated');

        $this->assertFalse(app(QuotationWorkflowService::class)->canTransition($quotation, 'sent'));
    }

    public function test_set_pending_updates_next_action(): void
    {
        $quotation = $this->quotation('sent', 'valid');

        app(QuotationWorkflowService::class)->transition($quotation, 'pending', 1, 'set_pending');

        $quotation->refresh();
        $this->assertSame('pending', $quotation->status);
        $this->assertSame('waiting_customer_response', $quotation->approval_status);
        $this->assertSame('customer_follow_up', $quotation->current_stage);
        $this->assertSame('follow_up_customer', $quotation->next_action);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'new_status' => 'pending',
            'action' => 'set_pending',
        ]);
    }

    public function test_sent_quotation_can_be_cancelled(): void
    {
        $quotation = $this->quotation('sent', 'valid');

        app(QuotationWorkflowService::class)->transition($quotation, 'cancelled', 1, 'cancel');

        $quotation->refresh();
        $this->assertSame('cancelled', $quotation->status);
        $this->assertSame('cancelled', $quotation->approval_status);
        $this->assertSame('cancelled', $quotation->current_stage);
        $this->assertSame('none', $quotation->next_action);
        $this->assertNotNull($quotation->cancelled_at);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'old_status' => 'sent',
            'new_status' => 'cancelled',
            'action' => 'cancel',
        ]);
    }

    public function test_validation_finalize_updates_validation_status_and_current_stage(): void
    {
        $quotation = $this->quotation('pending_validation', 'pending');
        QuotationItem::query()->create([
            'quotation_id' => $quotation->id,
            'description' => 'Validated item',
            'is_validation_required' => true,
            'is_validated' => true,
        ]);

        app(QuotationValidationService::class)->syncValidationStatus($quotation, 1);
        $quotation->refresh();
        app(QuotationWorkflowService::class)->transition($quotation, 'ready_to_send', 1, 'validation_finalize');

        $quotation->refresh();
        $this->assertSame('validated', $quotation->validation_status);
        $this->assertSame('ready_to_send', $quotation->status);
        $this->assertSame('ready_to_send', $quotation->current_stage);
        $this->assertDatabaseHas('quotation_status_logs', [
            'quotation_id' => $quotation->id,
            'new_status' => 'ready_to_send',
            'action' => 'validation_finalize',
        ]);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $quotation = $this->quotation('draft', 'pending');

        $this->expectException(ValidationException::class);

        app(QuotationWorkflowService::class)->transition($quotation, 'customer_approved', 1, 'invalid_jump');
    }

    public function test_non_sent_quotation_cannot_use_post_sent_actions(): void
    {
        $service = app(QuotationWorkflowService::class);
        $quotation = $this->quotation('validated', 'valid');

        $this->assertFalse($service->canUsePostSentAction($quotation, 'customer_approved'));
        $this->assertFalse($service->canUsePostSentAction($quotation, 'under_revision'));
        $this->assertFalse($service->canUsePostSentAction($quotation, 'pending'));
        $this->assertFalse($service->canUsePostSentAction($quotation, 'lost'));
        $this->assertFalse($service->canUsePostSentAction($quotation, 'cancelled'));
    }

    private function quotation(string $status, string $validationStatus): Quotation
    {
        DB::table('quotations')->insert([
            'quotation_number' => 'QT-' . uniqid(),
            'status' => $status,
            'validation_status' => $validationStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Quotation::query()->latest('id')->firstOrFail();
    }
}
