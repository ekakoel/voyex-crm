<?php

namespace Tests\Unit\Workflow;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\ActivityAuditLogger;
use App\Services\QuotationRevisionService;
use App\Services\QuotationValidationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationRevisionValidationCarryOverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        foreach (['quotation_items', 'quotations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('order_number')->nullable();
            $table->unsignedBigInteger('inquiry_id')->nullable();
            $table->unsignedBigInteger('itinerary_id')->nullable();
            $table->unsignedBigInteger('revision_of_id')->nullable();
            $table->unsignedInteger('revision_number')->nullable();
            $table->boolean('is_current_revision')->default(true);
            $table->text('revision_reason')->nullable();
            $table->string('status', 80)->nullable();
            $table->string('validation_status', 80)->nullable();
            $table->date('validity_date')->nullable();
            $table->date('service_date')->nullable();
            $table->unsignedInteger('pax_adult')->default(0);
            $table->unsignedInteger('pax_child')->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->unsignedBigInteger('approval_note_by')->nullable();
            $table->timestamp('approval_note_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('contract_rate', 15, 2)->default(0);
            $table->string('markup_type', 20)->nullable();
            $table->decimal('markup', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('serviceable_type')->nullable();
            $table->unsignedBigInteger('serviceable_id')->nullable();
            $table->unsignedInteger('day_number')->nullable();
            $table->json('serviceable_meta')->nullable();
            $table->string('itinerary_item_type')->nullable();
            $table->date('service_date')->nullable();
            $table->string('status')->nullable();
            $table->string('cancellation_fee_type')->nullable();
            $table->decimal('cancellation_fee_value', 15, 2)->nullable();
            $table->decimal('cancellation_fee_amount', 15, 2)->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('actual_used_at')->nullable();
            $table->unsignedBigInteger('replaced_by_item_id')->nullable();
            $table->boolean('is_validation_required')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->unsignedBigInteger('validated_by')->nullable();
            $table->text('validation_notes')->nullable();
            $table->decimal('last_validated_contract_rate', 15, 2)->nullable();
            $table->string('last_validated_markup_type')->nullable();
            $table->decimal('last_validated_markup', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['quotation_items', 'quotations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_revision_carries_validated_state_for_existing_items(): void
    {
        $quotation = Quotation::withoutActivityLogging(fn () => Quotation::query()->create([
            'quotation_number' => 'QTN-TEST',
            'status' => Quotation::STATUS_SENT,
            'validation_status' => QuotationValidationService::STATUS_VALID,
            'validity_date' => now()->addDays(7)->toDateString(),
            'service_date' => now()->addDays(14)->toDateString(),
            'pax_adult' => 2,
            'final_amount' => 125,
        ]));

        $validatedAt = now()->subDay();
        $quotation->items()->create([
            'description' => 'Validated transfer',
            'qty' => 1,
            'contract_rate' => 100,
            'markup_type' => 'fixed',
            'markup' => 25,
            'unit_price' => 125,
            'total' => 125,
            'status' => QuotationItem::STATUS_VALIDATED,
            'is_validation_required' => true,
            'is_validated' => true,
            'validated_at' => $validatedAt,
            'validated_by' => 9,
            'validation_notes' => 'Vendor confirmed once.',
            'last_validated_contract_rate' => 100,
            'last_validated_markup_type' => 'fixed',
            'last_validated_markup' => 25,
        ]);

        $auditLogger = $this->mock(ActivityAuditLogger::class);
        $auditLogger->shouldReceive('logCreated')->once();

        $validationService = $this->mock(QuotationValidationService::class);
        $validationService->shouldReceive('syncValidationRequirementsAndMasterRates')->once();

        $revision = app(QuotationRevisionService::class)->createRevisionFromQuotation($quotation, [
            'revision_reason' => 'Customer requested additional service.',
        ]);

        $item = $revision->items()->firstOrFail();
        $this->assertTrue((bool) $item->is_validation_required);
        $this->assertTrue((bool) $item->is_validated);
        $this->assertSame(9, (int) $item->validated_by);
        $this->assertSame('Vendor confirmed once.', $item->validation_notes);
        $this->assertSame(QuotationItem::STATUS_VALIDATED, $item->status);
        $this->assertSame(100.0, (float) $item->last_validated_contract_rate);
    }

    public function test_quotation_can_create_multiple_revisions_before_approval(): void
    {
        $quotation = Quotation::withoutActivityLogging(fn () => Quotation::query()->create([
            'quotation_number' => 'QTN-MULTI',
            'revision_number' => 1,
            'is_current_revision' => true,
            'status' => Quotation::STATUS_SENT,
            'validation_status' => QuotationValidationService::STATUS_VALID,
            'validity_date' => now()->addDays(7)->toDateString(),
            'service_date' => now()->addDays(14)->toDateString(),
            'pax_adult' => 2,
            'final_amount' => 125,
        ]));

        $quotation->items()->create([
            'description' => 'Validated transfer',
            'qty' => 1,
            'contract_rate' => 100,
            'markup_type' => 'fixed',
            'markup' => 25,
            'unit_price' => 125,
            'total' => 125,
            'status' => QuotationItem::STATUS_VALIDATED,
            'is_validation_required' => true,
            'is_validated' => true,
            'validated_at' => now()->subDay(),
            'validated_by' => 9,
            'last_validated_contract_rate' => 100,
            'last_validated_markup_type' => 'fixed',
            'last_validated_markup' => 25,
        ]);

        $auditLogger = $this->mock(ActivityAuditLogger::class);
        $auditLogger->shouldReceive('logCreated')->twice();

        $validationService = $this->mock(QuotationValidationService::class);
        $validationService->shouldReceive('syncValidationRequirementsAndMasterRates')->twice();

        $revisionOne = app(QuotationRevisionService::class)->createRevisionFromQuotation($quotation, [
            'status' => Quotation::STATUS_UNDER_REVISION,
            'revision_reason' => 'Customer requested transport change.',
        ]);
        $revisionTwo = app(QuotationRevisionService::class)->createRevisionFromQuotation($revisionOne, [
            'status' => Quotation::STATUS_UNDER_REVISION,
            'revision_reason' => 'Customer requested hotel change.',
        ]);

        $this->assertSame(2, (int) $revisionOne->revision_number);
        $this->assertSame(3, (int) $revisionTwo->revision_number);
        $this->assertSame((int) $quotation->id, (int) $revisionOne->revision_of_id);
        $this->assertSame((int) $quotation->id, (int) $revisionTwo->revision_of_id);
        $this->assertFalse((bool) $quotation->fresh()->is_current_revision);
        $this->assertFalse((bool) $revisionOne->fresh()->is_current_revision);
        $this->assertTrue((bool) $revisionTwo->fresh()->is_current_revision);
    }
}
