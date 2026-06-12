<?php

namespace Tests\Unit\Support;

use App\Models\Quotation;
use App\Services\ModuleService;
use App\Support\QuotationActionResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotationActionResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();
        foreach (['quotation_customer_responses', 'invoices', 'bookings', 'quotation_items', 'quotations', 'modules', 'permissions', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->string('quotation_number')->nullable();
            $table->string('status', 80)->default('draft');
            $table->unsignedBigInteger('revision_of_id')->nullable();
            $table->string('validation_status', 80)->nullable();
            $table->string('approval_status', 80)->nullable();
            $table->string('booking_status', 80)->nullable();
            $table->string('invoice_status', 80)->nullable();
            $table->string('payment_status', 80)->nullable();
            $table->string('operation_status', 80)->nullable();
            $table->string('follow_up_status', 80)->nullable();
            $table->timestamp('last_followed_up_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->string('status', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('status', 80)->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_customer_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->boolean('requires_revision')->default(false);
            $table->boolean('is_used_for_revision')->default(false);
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        DB::table('modules')->insert([
            'key' => 'bookings',
            'name' => 'Bookings',
            'description' => 'Booking module',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ModuleService::flushCache();
    }

    protected function tearDown(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['quotation_customer_responses', 'invoices', 'bookings', 'quotation_items', 'quotations', 'modules', 'permissions', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        ModuleService::flushCache();

        parent::tearDown();
    }

    public function test_draft_shows_edit_and_submit_validation_only(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'draft', 'validation_status' => '']));

        $this->assertContains('edit_quotation', $keys);
        $this->assertContains('submit_validation', $keys);
        $this->assertNotContains('mark_as_sent', $keys);
        $this->assertNotContains('create_booking', $keys);
    }

    public function test_pending_validation_shows_validate_quotation(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'pending_validation', 'validation_status' => 'pending']));

        $this->assertContains('validate_quotation', $keys);
        $this->assertContains('edit_quotation', $keys);
    }

    public function test_validated_shows_download_pdf_and_mark_as_sent(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'validated', 'validation_status' => 'valid']));

        $this->assertContains('preview_download_pdf', $keys);
        $this->assertContains('mark_as_sent', $keys);
    }

    public function test_pending_revision_response_hides_mark_sent_and_shows_start_revision(): void
    {
        $quotation = $this->quotation(['status' => 'ready_to_send', 'validation_status' => 'valid']);
        DB::table('quotation_customer_responses')->insert([
            'quotation_id' => $quotation->id,
            'requires_revision' => true,
            'is_used_for_revision' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $keys = $this->keys($quotation);

        $this->assertContains('start_revision', $keys);
        $this->assertNotContains('mark_as_sent', $keys);
    }

    public function test_sent_shows_follow_up_and_customer_response(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'sent', 'validation_status' => 'valid']));

        $this->assertContains('add_follow_up', $keys);
        $this->assertContains('add_customer_response', $keys);
        $this->assertNotContains('start_revision', $keys);
        $this->assertContains('set_pending', $keys);
        $this->assertNotContains('mark_lost', $keys);
        $this->assertNotContains('mark_cancelled', $keys);
        $this->assertNotContains('create_booking', $keys);
    }

    public function test_sent_hides_follow_up_when_already_followed_up_today(): void
    {
        $keys = $this->keys($this->quotation([
            'status' => 'sent',
            'validation_status' => 'valid',
            'last_followed_up_at' => now(),
        ]));

        $this->assertNotContains('add_follow_up', $keys);
        $this->assertContains('add_customer_response', $keys);
    }

    public function test_revision_requested_shows_start_revision(): void
    {
        $quotation = $this->quotation(['status' => 'revision_requested', 'approval_status' => 'revision_requested']);
        $actions = app(QuotationActionResolver::class)->availableActions($quotation);
        $keys = collect($actions)->pluck('key')->all();

        $this->assertContains('start_revision', $keys);
        $this->assertSame('Start Revision', (string) collect($actions)->firstWhere('key', 'start_revision')['label']);
        $this->assertNotContains('create_booking', $keys);
        $startAction = collect($actions)->firstWhere('key', 'start_revision');
        $this->assertSame(route('quotations.request-revision', $quotation), (string) ($startAction['route'] ?? ''));
        $this->assertSame('POST', (string) ($startAction['method'] ?? 'GET'));
    }

    public function test_under_revision_prioritizes_revision_button_over_validation_status(): void
    {
        foreach (['pending', 'valid'] as $validationStatus) {
            $actions = app(QuotationActionResolver::class)->availableActions(
                $this->quotation([
                    'status' => 'under_revision',
                    'approval_status' => 'revision_requested',
                    'validation_status' => $validationStatus,
                ])
            );
            $keys = collect($actions)->pluck('key')->all();

            $this->assertContains('revise_quotation', $keys);
            $this->assertNotContains('mark_as_sent', $keys);
            $this->assertNotContains('validate_quotation', $keys);
        }
    }

    public function test_pending_revalidation_shows_revalidate_quotation(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'pending_revalidation', 'validation_status' => 'needs_revalidation']));

        $this->assertContains('revalidate_quotation', $keys);
        $this->assertContains('view_expired_items', $keys);
        $this->assertContains('edit_quotation', $keys);
    }

    public function test_approved_shows_create_booking(): void
    {
        $quotation = $this->quotation(['status' => 'customer_approved', 'approval_status' => 'approved', 'validation_status' => 'valid']);
        DB::table('quotation_items')->insert([
            'quotation_id' => $quotation->id,
            'description' => 'Service item',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $keys = $this->keys($quotation);

        $this->assertContains('create_booking', $keys);
        $this->assertNotContains('create_revision', $keys);
        $this->assertNotContains('start_revision', $keys);
        $this->assertNotContains('validate_quotation', $keys);
        $this->assertContains('preview_download_pdf', $keys);
    }

    public function test_booking_actions_are_hidden_when_bookings_module_is_disabled(): void
    {
        DB::table('modules')
            ->where('key', 'bookings')
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);
        ModuleService::flushCache();

        $approvedKeys = $this->keys($this->quotation([
            'status' => 'customer_approved',
            'approval_status' => 'approved',
            'validation_status' => 'valid',
        ]));

        $bookingKeys = $this->keys($this->quotation([
            'status' => 'converted_to_booking',
            'booking_status' => 'in_progress',
        ]));

        $bookingIssueActions = app(QuotationActionResolver::class)->availableActions(
            $this->quotation([
                'status' => 'booking_issue',
                'booking_status' => 'issue',
            ])
        );

        $this->assertNotContains('create_booking', $approvedKeys);
        $this->assertNotContains('view_booking', $bookingKeys);
        $this->assertNotContains('vendor_confirmation', $bookingKeys);
        $this->assertSame('create_revision', (string) collect($bookingIssueActions)->first()['key']);
        $this->assertSame('Create Revision', (string) collect($bookingIssueActions)->first()['label']);
    }

    public function test_booking_issue_shows_create_revision_from_booking_issue(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'booking_issue', 'booking_status' => 'issue']));

        $this->assertContains('create_revision_from_booking_issue', $keys);
        $this->assertContains('revalidate_replacement_items', $keys);
    }

    public function test_waiting_payment_shows_invoice_payment_actions(): void
    {
        $quotation = $this->quotation([
            'status' => 'invoiced',
            'invoice_status' => 'issued',
            'payment_status' => 'waiting_payment',
        ]);
        DB::table('bookings')->insert(['id' => 10, 'quotation_id' => $quotation->id, 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('invoices')->insert(['id' => 20, 'booking_id' => 10, 'status' => 'issued', 'created_at' => now(), 'updated_at' => now()]);

        $keys = $this->keys($quotation);

        $this->assertContains('view_invoice', $keys);
        $this->assertContains('record_payment', $keys);
    }

    public function test_completed_shows_no_mutation_actions(): void
    {
        $keys = $this->keys($this->quotation(['status' => 'completed']));

        $this->assertSame(['view_summary'], $keys);
    }

    public function test_cancelled_and_lost_show_no_primary_mutation_actions(): void
    {
        $cancelled = $this->keys($this->quotation(['status' => 'cancelled']));
        $lost = $this->keys($this->quotation(['status' => 'lost']));

        $this->assertNotContains('mark_as_sent', $cancelled);
        $this->assertNotContains('create_booking', $cancelled);
        $this->assertNotContains('mark_as_sent', $lost);
        $this->assertNotContains('create_booking', $lost);
    }

    public function test_missing_route_does_not_throw_error(): void
    {
        $actions = app(QuotationActionResolver::class)->availableActions(
            $this->quotation(['status' => 'operation_adjustment', 'operation_status' => 'adjustment_required'])
        );

        $this->assertIsArray($actions);
    }

    public function test_standardized_action_metadata_is_consistent(): void
    {
        $validatedActions = app(QuotationActionResolver::class)->availableActions(
            $this->quotation(['status' => 'validated', 'validation_status' => 'valid'])
        );
        $markAsSent = collect($validatedActions)->firstWhere('key', 'mark_as_sent');
        $this->assertNotNull($markAsSent);
        $this->assertSame('Mark as Sent', (string) ($markAsSent['label'] ?? ''));
        $this->assertSame('fa-paper-plane', (string) ($markAsSent['icon'] ?? ''));
        $this->assertSame('primary', (string) ($markAsSent['style'] ?? ''));

        $draftActions = app(QuotationActionResolver::class)->availableActions(
            $this->quotation(['status' => 'draft'])
        );
        $revise = collect($draftActions)->firstWhere('key', 'edit_quotation');
        $this->assertNotNull($revise);
        $this->assertSame('Revise Quotation', (string) ($revise['label'] ?? ''));
        $this->assertSame('fa-pen-to-square', (string) ($revise['icon'] ?? ''));
        $this->assertSame('primary', (string) ($revise['style'] ?? ''));
    }

    private function keys(Quotation $quotation): array
    {
        return collect(app(QuotationActionResolver::class)->availableActions($quotation))
            ->pluck('key')
            ->all();
    }

    private function quotation(array $attributes): Quotation
    {
        DB::table('quotations')->insert(array_merge([
            'quotation_number' => 'QT-' . uniqid(),
            'status' => 'draft',
            'validation_status' => null,
            'approval_status' => null,
            'booking_status' => null,
            'invoice_status' => null,
            'payment_status' => null,
            'operation_status' => null,
            'follow_up_status' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Quotation::query()->latest('id')->firstOrFail();
    }
}
