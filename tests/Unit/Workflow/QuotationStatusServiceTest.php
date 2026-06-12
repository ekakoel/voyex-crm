<?php

namespace Tests\Unit\Workflow;

use App\Models\Quotation;
use App\Services\Quotation\QuotationStatusService;
use App\Support\Workflow\QuotationStatusNormalizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationStatusServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        foreach (['quotation_customer_responses', 'quotation_items', 'quotations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 80)->nullable();
            $table->string('validation_status', 80)->nullable();
            $table->unsignedInteger('validation_progress')->nullable();
            $table->unsignedInteger('revision_number')->nullable();
            $table->unsignedBigInteger('revision_of_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->boolean('is_validation_required')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->string('validation_status', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_customer_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->boolean('requires_revision')->default(false);
            $table->boolean('is_used_for_revision')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['quotation_customer_responses', 'quotation_items', 'quotations'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_pending_required_item_keeps_new_quotation_in_need_validation_status(): void
    {
        $quotation = $this->quotation('draft');
        $quotation->items()->create([
            'description' => 'Pending service',
            'is_validation_required' => true,
            'is_validated' => false,
        ]);

        app(QuotationStatusService::class)->syncStatus($quotation);

        $quotation->refresh();
        $this->assertSame('need_validation', $quotation->status);
        $this->assertSame('need_validation', QuotationStatusNormalizer::normalize($quotation->status));
        $this->assertSame('pending', $quotation->validation_status);
        $this->assertSame(0, (int) $quotation->validation_progress);
    }

    public function test_partial_validation_keeps_logical_need_validation(): void
    {
        $quotation = $this->quotation('draft');
        $quotation->items()->create(['description' => 'Validated', 'is_validation_required' => true, 'is_validated' => true]);
        $quotation->items()->create(['description' => 'Pending', 'is_validation_required' => true, 'is_validated' => false]);

        app(QuotationStatusService::class)->syncStatus($quotation);

        $quotation->refresh();
        $this->assertSame('need_validation', $quotation->status);
        $this->assertSame('partial', $quotation->validation_status);
        $this->assertSame(50, (int) $quotation->validation_progress);
    }

    public function test_all_required_items_validated_becomes_ready_to_send(): void
    {
        $quotation = $this->quotation('pending_validation');
        $quotation->items()->create(['description' => 'Validated', 'is_validation_required' => true, 'is_validated' => true]);

        app(QuotationStatusService::class)->syncStatus($quotation);

        $quotation->refresh();
        $this->assertSame('ready_to_send', $quotation->status);
        $this->assertSame('validated', $quotation->validation_status);
        $this->assertSame(100, (int) $quotation->validation_progress);
    }

    public function test_unhandled_revision_response_keeps_validated_quotation_in_revision_requested(): void
    {
        $quotation = $this->quotation('under_revision', ['revision_number' => 2]);
        $quotation->items()->create(['description' => 'Validated', 'is_validation_required' => true, 'is_validated' => true]);
        DB::table('quotation_customer_responses')->insert([
            'quotation_id' => $quotation->id,
            'requires_revision' => true,
            'is_used_for_revision' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(QuotationStatusService::class)->syncStatus($quotation);

        $quotation->refresh();
        $this->assertSame('revision_requested', $quotation->status);
        $this->assertSame('validated', $quotation->validation_status);
        $this->assertSame(100, (int) $quotation->validation_progress);
    }

    public function test_revision_with_pending_required_item_becomes_need_revalidation_status(): void
    {
        $quotation = $this->quotation('under_revision', ['revision_number' => 2]);
        $quotation->items()->create([
            'description' => 'New pending service',
            'is_validation_required' => true,
            'is_validated' => false,
        ]);

        app(QuotationStatusService::class)->syncStatus($quotation);

        $quotation->refresh();
        $this->assertSame('need_revalidation', $quotation->status);
        $this->assertSame('need_revalidation', QuotationStatusNormalizer::normalize($quotation->status));
    }

    public function test_legacy_statuses_are_normalized_to_logical_statuses(): void
    {
        $this->assertSame('need_validation', QuotationStatusNormalizer::normalize('pending_validation'));
        $this->assertSame('need_revalidation', QuotationStatusNormalizer::normalize('pending_revalidation'));
        $this->assertSame('approved', QuotationStatusNormalizer::normalize('customer_approved'));
        $this->assertSame('converted_to_booking', QuotationStatusNormalizer::normalize('booking_created'));
        $this->assertSame('approved', QuotationStatusNormalizer::normalize('approved'));
        $this->assertSame('converted_to_booking', QuotationStatusNormalizer::normalize('converted_to_booking'));
    }

    private function quotation(string $status, array $overrides = []): Quotation
    {
        return Quotation::withoutActivityLogging(fn () => Quotation::query()->create(array_merge([
            'status' => $status,
            'validation_status' => 'pending',
            'revision_number' => 1,
        ], $overrides)));
    }
}
