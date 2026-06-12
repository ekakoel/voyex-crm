<?php

namespace Tests\Feature\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizeQuotationStatusCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('quotations');
        Schema::enableForeignKeyConstraints();

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('status', 80)->default('draft');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('quotations');
        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    public function test_dry_run_does_not_update_legacy_statuses(): void
    {
        $this->insertQuotation('pending_validation');
        $this->insertQuotation('customer_approved');

        $this->artisan('quotations:normalize-status --dry-run')
            ->expectsOutputToContain('Dry-run mode')
            ->assertExitCode(0);

        $this->assertDatabaseHas('quotations', ['status' => 'pending_validation']);
        $this->assertDatabaseHas('quotations', ['status' => 'customer_approved']);
    }

    public function test_apply_updates_only_supported_legacy_statuses(): void
    {
        $this->insertQuotation('pending_validation');
        $this->insertQuotation('pending_revalidation');
        $this->insertQuotation('customer_approved');
        $this->insertQuotation('booking_created');
        $this->insertQuotation('sent');

        $this->artisan('quotations:normalize-status --apply')
            ->expectsOutputToContain('Updated quotation rows: 4')
            ->assertExitCode(0);

        $this->assertDatabaseHas('quotations', ['status' => 'need_validation']);
        $this->assertDatabaseHas('quotations', ['status' => 'need_revalidation']);
        $this->assertDatabaseHas('quotations', ['status' => 'approved']);
        $this->assertDatabaseHas('quotations', ['status' => 'converted_to_booking']);
        $this->assertDatabaseHas('quotations', ['status' => 'sent']);
        $this->assertDatabaseMissing('quotations', ['status' => 'pending_validation']);
        $this->assertDatabaseMissing('quotations', ['status' => 'pending_revalidation']);
        $this->assertDatabaseMissing('quotations', ['status' => 'customer_approved']);
        $this->assertDatabaseMissing('quotations', ['status' => 'booking_created']);
    }

    private function insertQuotation(string $status): void
    {
        DB::table('quotations')->insert([
            'quotation_number' => 'QT-' . uniqid(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
