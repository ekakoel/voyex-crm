<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addQuotationFollowUpColumns();
        $this->createOrUpdateQuotationFollowUpsTable();
        $this->createOrUpdateQuotationCustomerResponsesTable();
        $this->createOrUpdateQuotationFollowUpNotificationsTable();
    }

    public function down(): void
    {
        // Intentionally non-destructive. These tables/columns can contain production follow-up history.
    }

    private function addQuotationFollowUpColumns(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotations', 'follow_up_status')) {
                $table->string('follow_up_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'last_followed_up_at')) {
                $table->timestamp('last_followed_up_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'next_follow_up_at')) {
                $table->timestamp('next_follow_up_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'follow_up_count')) {
                $table->unsignedInteger('follow_up_count')->default(0);
            }
            if (! Schema::hasColumn('quotations', 'follow_up_until')) {
                $table->timestamp('follow_up_until')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'no_response_warning_at')) {
                $table->timestamp('no_response_warning_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'service_date_warning_at')) {
                $table->timestamp('service_date_warning_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'auto_status_reason')) {
                $table->string('auto_status_reason', 120)->nullable();
            }
            if (! Schema::hasColumn('quotations', 'auto_status_updated_at')) {
                $table->timestamp('auto_status_updated_at')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'auto_status_locked')) {
                $table->boolean('auto_status_locked')->default(false);
            }
            if (! Schema::hasColumn('quotations', 'sent_count')) {
                $table->unsignedInteger('sent_count')->default(0);
            }
        });

        $this->tryAddIndex('quotations', ['follow_up_status'], 'quotations_follow_up_status_idx');
        $this->tryAddIndex('quotations', ['next_follow_up_at'], 'quotations_next_follow_up_at_idx');
        $this->tryAddIndex('quotations', ['last_sent_at'], 'quotations_last_sent_at_idx');
        $this->tryAddIndex('quotations', ['service_date'], 'quotations_service_date_idx');
    }

    private function createOrUpdateQuotationFollowUpsTable(): void
    {
        if (! Schema::hasTable('quotation_follow_ups')) {
            Schema::create('quotation_follow_ups', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('channel', 80)->nullable();
                $table->string('follow_up_type', 80)->nullable();
                $table->text('follow_up_note')->nullable();
                $table->timestamp('follow_up_at')->nullable();
                $table->timestamp('next_follow_up_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['quotation_id', 'follow_up_at'], 'quotation_follow_ups_quotation_at_idx');
                $table->index(['handled_by', 'next_follow_up_at'], 'quotation_follow_ups_handler_next_idx');
            });

            return;
        }

        Schema::table('quotation_follow_ups', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotation_follow_ups', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'handled_by')) {
                $table->unsignedBigInteger('handled_by')->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'channel')) {
                $table->string('channel', 80)->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'follow_up_type')) {
                $table->string('follow_up_type', 80)->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'follow_up_note')) {
                $table->text('follow_up_note')->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'follow_up_at')) {
                $table->timestamp('follow_up_at')->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'next_follow_up_at')) {
                $table->timestamp('next_follow_up_at')->nullable();
            }
            if (! Schema::hasColumn('quotation_follow_ups', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
        });
    }

    private function createOrUpdateQuotationCustomerResponsesTable(): void
    {
        if (! Schema::hasTable('quotation_customer_responses')) {
            Schema::create('quotation_customer_responses', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('response_channel', 80)->nullable();
                $table->string('response_status', 80)->nullable();
                $table->text('response_note')->nullable();
                $table->boolean('requires_revision')->default(false);
                $table->string('revision_type', 80)->nullable();
                $table->string('revision_priority', 40)->nullable();
                $table->json('requested_changes')->nullable();
                $table->boolean('is_used_for_revision')->default(false);
                $table->timestamp('used_for_revision_at')->nullable();
                $table->foreignId('used_for_revision_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('quotation_revision_id')->nullable();
                $table->timestamp('response_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['quotation_id', 'requires_revision', 'is_used_for_revision'], 'qcr_revision_pending_idx');
                $table->index(['response_status', 'response_at'], 'qcr_status_response_at_idx');
            });

            return;
        }

        Schema::table('quotation_customer_responses', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotation_customer_responses', 'quotation_id')) {
                $table->unsignedBigInteger('quotation_id')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'handled_by')) {
                $table->unsignedBigInteger('handled_by')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'response_channel')) {
                $table->string('response_channel', 80)->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'response_status')) {
                $table->string('response_status', 80)->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'response_note')) {
                $table->text('response_note')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'requires_revision')) {
                $table->boolean('requires_revision')->default(false);
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'revision_type')) {
                $table->string('revision_type', 80)->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'revision_priority')) {
                $table->string('revision_priority', 40)->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'requested_changes')) {
                $table->json('requested_changes')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'is_used_for_revision')) {
                $table->boolean('is_used_for_revision')->default(false);
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'used_for_revision_at')) {
                $table->timestamp('used_for_revision_at')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'used_for_revision_by')) {
                $table->unsignedBigInteger('used_for_revision_by')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'quotation_revision_id')) {
                $table->unsignedBigInteger('quotation_revision_id')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'response_at')) {
                $table->timestamp('response_at')->nullable();
            }
            if (! Schema::hasColumn('quotation_customer_responses', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }
        });
    }

    private function createOrUpdateQuotationFollowUpNotificationsTable(): void
    {
        if (Schema::hasTable('quotation_follow_up_notifications')) {
            return;
        }

        Schema::create('quotation_follow_up_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
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

            $table->index(['user_id', 'is_read', 'due_at'], 'qfun_user_read_due_idx');
            $table->index(['quotation_id', 'notification_type', 'is_read'], 'qfun_quotation_type_read_idx');
        });
    }

    private function tryAddIndex(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        try {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
                $table->index($columns, $indexName);
            });
        } catch (Throwable $e) {
            // Existing production databases may already have this index.
        }
    }
};
