<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->widenQuotationStatusColumn();
        $this->addQuotationWorkflowDimensions();
        $this->backfillQuotationWorkflowDimensions();
        $this->addQuotationHandledByForeignKey();
        $this->createQuotationStatusLogsTable();
        $this->createQuotationSendLogsTable();
        $this->createQuotationApprovalLogsTable();
        $this->createQuotationRevisionsTable();
        $this->createWorkflowTasksTable();
        $this->addWorkflowIndexes();
    }

    public function down(): void
    {
        // Intentionally non-destructive. These structures may contain production workflow history.
    }

    private function widenQuotationStatusColumn(): void
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE quotations MODIFY status VARCHAR(80) NOT NULL DEFAULT 'draft'");
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE quotations ALTER COLUMN status TYPE VARCHAR(80)');
            }
        } catch (Throwable $e) {
            // Existing installations may already have a compatible type or a driver that cannot alter inline.
        }
    }

    private function addQuotationWorkflowDimensions(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotations', 'send_status')) {
                $table->string('send_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'approval_status')) {
                $table->string('approval_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'booking_status')) {
                $table->string('booking_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'invoice_status')) {
                $table->string('invoice_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'payment_status')) {
                $table->string('payment_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'operation_status')) {
                $table->string('operation_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'current_stage')) {
                $table->string('current_stage', 120)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'next_action')) {
                $table->string('next_action', 255)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'handled_by')) {
                $table->unsignedBigInteger('handled_by')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'last_sent_at')) {
                $table->timestamp('last_sent_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });
    }

    private function createQuotationStatusLogsTable(): void
    {
        if (Schema::hasTable('quotation_status_logs')) {
            return;
        }

        Schema::create('quotation_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->string('old_status', 80)->nullable();
            $table->string('new_status', 80)->nullable();
            $table->string('old_stage', 120)->nullable();
            $table->string('new_stage', 120)->nullable();
            $table->string('action', 120)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'changed_at'], 'quotation_status_logs_quotation_changed_idx');
            $table->index(['new_status', 'changed_at'], 'quotation_status_logs_status_changed_idx');
        });
    }

    private function createQuotationSendLogsTable(): void
    {
        if (Schema::hasTable('quotation_send_logs')) {
            return;
        }

        Schema::create('quotation_send_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->string('send_status', 80)->nullable();
            $table->string('channel', 80)->nullable();
            $table->string('recipient', 255)->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'sent_at'], 'quotation_send_logs_quotation_sent_idx');
            $table->index(['send_status', 'sent_at'], 'quotation_send_logs_status_sent_idx');
        });
    }

    private function createQuotationApprovalLogsTable(): void
    {
        if (Schema::hasTable('quotation_approval_logs')) {
            return;
        }

        Schema::create('quotation_approval_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_role', 80)->nullable();
            $table->string('old_approval_status', 80)->nullable();
            $table->string('new_approval_status', 80)->nullable();
            $table->string('action', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'acted_at'], 'quotation_approval_logs_quotation_acted_idx');
            $table->index(['new_approval_status', 'acted_at'], 'quotation_approval_logs_status_acted_idx');
        });
    }

    private function createQuotationRevisionsTable(): void
    {
        if (Schema::hasTable('quotation_revisions')) {
            return;
        }

        Schema::create('quotation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('parent_quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('created_from_revision_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->string('quotation_number')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->text('revision_reason')->nullable();
            $table->foreignId('revision_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revision_requested_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['quotation_id', 'version'], 'quotation_revisions_quotation_version_idx');
            $table->index(['parent_quotation_id', 'version'], 'quotation_revisions_parent_version_idx');
        });
    }

    private function createWorkflowTasksTable(): void
    {
        if (Schema::hasTable('workflow_tasks')) {
            return;
        }

        Schema::create('workflow_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('module_type', 120);
            $table->unsignedBigInteger('module_id');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('task_type', 120)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 80)->default('open');
            $table->string('priority', 40)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['module_type', 'module_id'], 'workflow_tasks_module_idx');
            $table->index(['assigned_to', 'status'], 'workflow_tasks_assignee_status_idx');
            $table->index(['status', 'due_date'], 'workflow_tasks_status_due_idx');
        });
    }

    private function addWorkflowIndexes(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table): void {
            $this->addIndexIfPossible($table, 'quotations', ['status'], 'quotations_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['validation_status'], 'quotations_validation_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['send_status'], 'quotations_send_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['approval_status'], 'quotations_approval_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['booking_status'], 'quotations_booking_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['invoice_status'], 'quotations_invoice_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['payment_status'], 'quotations_payment_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['operation_status'], 'quotations_operation_status_idx');
            $this->addIndexIfPossible($table, 'quotations', ['current_stage'], 'quotations_current_stage_idx');
            $this->addIndexIfPossible($table, 'quotations', ['handled_by'], 'quotations_handled_by_idx');
            $this->addIndexIfPossible($table, 'quotations', ['validity_date'], 'quotations_validity_date_idx');
        });

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table): void {
                $this->addIndexIfPossible($table, 'quotation_items', ['quotation_id'], 'quotation_items_quotation_id_idx');
                $this->addIndexIfPossible($table, 'quotation_items', ['serviceable_type', 'serviceable_id'], 'quotation_items_serviceable_idx');
                $this->addIndexIfPossible($table, 'quotation_items', ['status'], 'quotation_items_status_idx');
            });
        }
    }

    private function addIndexIfPossible(Blueprint $table, string $tableName, array $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        $table->index($columns, $indexName);
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                return DB::table('information_schema.statistics')
                    ->where('table_schema', DB::raw('DATABASE()'))
                    ->where('table_name', $tableName)
                    ->where('index_name', $indexName)
                    ->exists();
            }

            if ($driver === 'pgsql') {
                return DB::table('pg_indexes')
                    ->where('schemaname', DB::raw('current_schema()'))
                    ->where('tablename', $tableName)
                    ->where('indexname', $indexName)
                    ->exists();
            }

            if ($driver === 'sqlite') {
                foreach (DB::select("PRAGMA index_list('{$tableName}')") as $index) {
                    if ((string) ($index->name ?? '') === $indexName) {
                        return true;
                    }
                }
            }
        } catch (Throwable $e) {
            return false;
        }

        return false;
    }

    private function addQuotationHandledByForeignKey(): void
    {
        if (
            ! Schema::hasTable('quotations')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('quotations', 'handled_by')
        ) {
            return;
        }

        $this->nullInvalidQuotationHandledByValues();

        if ($this->foreignKeyExists('quotations', 'quotations_handled_by_foreign')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table): void {
            $table
                ->foreign('handled_by', 'quotations_handled_by_foreign')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                return DB::table('information_schema.referential_constraints')
                    ->where('constraint_schema', DB::raw('DATABASE()'))
                    ->where('table_name', $tableName)
                    ->where('constraint_name', $foreignKeyName)
                    ->exists();
            }
        } catch (Throwable $e) {
            return false;
        }

        return false;
    }

    private function nullInvalidQuotationHandledByValues(): void
    {
        if (
            ! Schema::hasTable('quotations')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('quotations', 'handled_by')
        ) {
            return;
        }

        DB::table('quotations')
            ->whereNotNull('handled_by')
            ->whereNotExists(function ($query): void {
                $query
                    ->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', 'quotations.handled_by');
            })
            ->update(['handled_by' => null]);
    }

    private function backfillQuotationWorkflowDimensions(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        $select = ['id', 'status'];
        foreach (['validation_status', 'inquiry_id', 'handled_by', 'created_by'] as $column) {
            if (Schema::hasColumn('quotations', $column)) {
                $select[] = $column;
            }
        }

        DB::table('quotations')
            ->select($select)
            ->orderBy('id')
            ->chunkById(200, function ($quotations): void {
                foreach ($quotations as $quotation) {
                    $status = $this->normalizeQuotationStatus((string) ($quotation->status ?? ''));
                    $booking = $this->latestBookingForQuotation((int) $quotation->id);
                    $invoice = $booking ? $this->latestInvoiceForBooking((int) $booking->id) : null;
                    $paymentStatus = $invoice ? $this->derivePaymentStatus((int) $invoice->id, (string) ($invoice->status ?? '')) : 'not_invoiced';

                    $payload = [
                        'send_status' => $this->deriveSendStatus($status),
                        'approval_status' => $this->deriveApprovalStatus($status),
                        'booking_status' => $booking ? (string) ($booking->status ?? 'created') : 'not_created',
                        'invoice_status' => $invoice ? (string) ($invoice->status ?? 'issued') : 'not_created',
                        'payment_status' => $paymentStatus,
                        'operation_status' => $this->deriveOperationStatus($booking ? (string) ($booking->status ?? '') : ''),
                        'current_stage' => $this->deriveCurrentStage($status, $booking, $invoice, $paymentStatus),
                        'next_action' => $this->deriveNextAction($status, $booking, $invoice, $paymentStatus),
                    ];

                    $update = [];
                    foreach ($payload as $column => $value) {
                        if (! Schema::hasColumn('quotations', $column)) {
                            continue;
                        }
                        if ($value === null) {
                            continue;
                        }

                        $update[$column] = DB::raw("COALESCE({$column}, " . DB::getPdo()->quote((string) $value) . ')');
                    }

                    if (Schema::hasColumn('quotations', 'handled_by')) {
                        $update['handled_by'] = $this->resolveValidQuotationHandler($quotation);
                    }

                    if ($update !== []) {
                        DB::table('quotations')->where('id', (int) $quotation->id)->update($update);
                    }
                }
            });

        $this->nullInvalidQuotationHandledByValues();
    }

    private function latestBookingForQuotation(int $quotationId): ?object
    {
        if (! Schema::hasTable('bookings')) {
            return null;
        }

        return DB::table('bookings')
            ->where('quotation_id', $quotationId)
            ->orderByDesc('id')
            ->first();
    }

    private function latestInvoiceForBooking(int $bookingId): ?object
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        return DB::table('invoices')
            ->where('booking_id', $bookingId)
            ->orderByDesc('id')
            ->first();
    }

    private function derivePaymentStatus(int $invoiceId, string $invoiceStatus): string
    {
        if (in_array($invoiceStatus, ['paid', 'overpaid'], true)) {
            return $invoiceStatus;
        }

        if (! Schema::hasTable('payments')) {
            return 'unpaid';
        }

        $confirmedAmount = (float) DB::table('payments')
            ->where('invoice_id', $invoiceId)
            ->where('status', 'confirmed')
            ->sum('amount');

        if ($confirmedAmount > 0) {
            return 'partially_paid';
        }

        $hasPendingPayment = DB::table('payments')
            ->where('invoice_id', $invoiceId)
            ->whereIn('status', ['pending', 'waiting_confirmation'])
            ->exists();

        return $hasPendingPayment ? 'waiting_confirmation' : 'unpaid';
    }

    private function resolveValidQuotationHandler(object $quotation): ?int
    {
        $quotationHandler = $this->validUserId((int) ($quotation->handled_by ?? 0));
        if ($quotationHandler !== null) {
            return $quotationHandler;
        }

        $inquiryHandler = $this->resolveValidInquiryHandler((int) ($quotation->inquiry_id ?? 0));
        if ($inquiryHandler !== null) {
            return $inquiryHandler;
        }

        return $this->validUserId((int) ($quotation->created_by ?? 0));
    }

    private function resolveValidInquiryHandler(int $inquiryId): ?int
    {
        if ($inquiryId <= 0 || ! Schema::hasTable('inquiries')) {
            return null;
        }

        $query = DB::table('inquiries')->where('id', $inquiryId);
        $select = ['id'];
        foreach (['handled_by', 'assigned_to'] as $column) {
            if (Schema::hasColumn('inquiries', $column)) {
                $select[] = $column;
            }
        }

        $inquiry = $query->first($select);
        if (! $inquiry) {
            return null;
        }

        foreach (['handled_by', 'assigned_to'] as $column) {
            $value = $this->validUserId((int) ($inquiry->{$column} ?? 0));
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function validUserId(int $userId): ?int
    {
        if ($userId <= 0 || ! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->exists() ? $userId : null;
    }

    private function normalizeQuotationStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            '' => 'draft',
            'accepted' => 'customer_approved',
            'converted' => 'booking_created',
            'valid' => 'validated',
            'rejected' => 'lost',
            'final' => 'completed',
            default => $status,
        };
    }

    private function deriveSendStatus(string $status): string
    {
        return match ($status) {
            'sent', 'customer_approved', 'approved', 'booking_created', 'booking_in_progress', 'invoiced', 'waiting_payment', 'in_operation', 'operation_adjustment', 'finalized', 'completed' => 'sent',
            'validated', 'ready_to_send' => 'ready_to_send',
            default => 'not_sent',
        };
    }

    private function deriveApprovalStatus(string $status): string
    {
        return match ($status) {
            'customer_approved', 'approved', 'booking_created', 'booking_in_progress', 'invoiced', 'waiting_payment', 'in_operation', 'operation_adjustment', 'finalized', 'completed' => 'approved',
            'sent' => 'waiting_customer',
            'lost' => 'lost',
            'cancelled' => 'cancelled',
            default => 'not_ready',
        };
    }

    private function deriveOperationStatus(string $bookingStatus): string
    {
        return match ($bookingStatus) {
            'ready_to_operate' => 'ready_to_operate',
            'in_operation' => 'in_operation',
            'service_completed' => 'service_completed',
            'reconciliation' => 'reconciliation',
            'completed_settled', 'closed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'not_started',
        };
    }

    private function deriveCurrentStage(string $status, ?object $booking, ?object $invoice, string $paymentStatus): string
    {
        if (in_array($status, ['cancelled', 'lost', 'completed'], true)) {
            return $status;
        }
        if ($booking && in_array((string) ($booking->status ?? ''), ['in_operation', 'service_completed', 'reconciliation', 'completed_settled', 'closed'], true)) {
            return 'operation';
        }
        if (! in_array($paymentStatus, ['not_invoiced', 'unpaid'], true)) {
            return 'payment';
        }
        if ($invoice) {
            return 'invoice';
        }
        if ($booking) {
            return 'booking';
        }

        return match ($status) {
            'pending_validation' => 'validation',
            'validated', 'ready_to_send' => 'ready_to_send',
            'sent' => 'sent',
            'customer_approved', 'approved' => 'approval',
            default => 'quotation_draft',
        };
    }

    private function deriveNextAction(string $status, ?object $booking, ?object $invoice, string $paymentStatus): string
    {
        if (in_array($status, ['cancelled', 'lost', 'completed'], true)) {
            return 'view_summary';
        }
        if ($booking && in_array((string) ($booking->status ?? ''), ['in_operation', 'service_completed', 'reconciliation'], true)) {
            return 'continue_operation';
        }
        if ($invoice && ! in_array($paymentStatus, ['paid', 'overpaid'], true)) {
            return 'follow_up_payment';
        }
        if ($booking) {
            return 'continue_booking';
        }

        return match ($status) {
            'draft' => 'submit_for_validation',
            'pending_validation' => 'validate_items',
            'validated', 'ready_to_send' => 'send_quotation',
            'sent' => 'await_customer_decision',
            'customer_approved', 'approved' => 'create_booking',
            default => 'review_quotation',
        };
    }
};
