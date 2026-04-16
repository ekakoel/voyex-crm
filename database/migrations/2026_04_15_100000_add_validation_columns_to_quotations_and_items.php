<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table): void {
                if (! Schema::hasColumn('quotations', 'validation_status')) {
                    $table->string('validation_status', 20)
                        ->default('pending')
                        ->after('status');
                }
                if (! Schema::hasColumn('quotations', 'validated_at')) {
                    $table->timestamp('validated_at')->nullable()->after('approval_note_at');
                }
                if (! Schema::hasColumn('quotations', 'validated_by')) {
                    $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('quotation_items', 'is_validation_required')) {
                    $table->boolean('is_validation_required')->default(false)->after('itinerary_item_type');
                }
                if (! Schema::hasColumn('quotation_items', 'is_validated')) {
                    $table->boolean('is_validated')->default(false)->after('is_validation_required');
                }
                if (! Schema::hasColumn('quotation_items', 'validated_at')) {
                    $table->timestamp('validated_at')->nullable()->after('is_validated');
                }
                if (! Schema::hasColumn('quotation_items', 'validated_by')) {
                    $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('quotation_items', 'validation_notes')) {
                    $table->text('validation_notes')->nullable()->after('validated_by');
                }
                if (! Schema::hasColumn('quotation_items', 'last_validated_contract_rate')) {
                    $table->decimal('last_validated_contract_rate', 15, 2)->nullable()->after('validation_notes');
                }
                if (! Schema::hasColumn('quotation_items', 'last_validated_markup_type')) {
                    $table->string('last_validated_markup_type', 20)->nullable()->after('last_validated_contract_rate');
                }
                if (! Schema::hasColumn('quotation_items', 'last_validated_markup')) {
                    $table->decimal('last_validated_markup', 15, 2)->nullable()->after('last_validated_markup_type');
                }
            });

            try {
                Schema::table('quotation_items', function (Blueprint $table): void {
                    $table->index(['quotation_id', 'is_validation_required', 'is_validated'], 'quotation_items_validation_progress_idx');
                });
            } catch (\Throwable $e) {
                // no-op
            }
        }

        try {
            Schema::table('quotations', function (Blueprint $table): void {
                $table->index('validation_status', 'quotations_validation_status_idx');
            });
        } catch (\Throwable $e) {
            // no-op
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quotation_items')) {
            Schema::table('quotation_items', function (Blueprint $table): void {
                try {
                    $table->dropIndex('quotation_items_validation_progress_idx');
                } catch (\Throwable $e) {
                    // no-op
                }

                if (Schema::hasColumn('quotation_items', 'last_validated_markup')) {
                    $table->dropColumn('last_validated_markup');
                }
                if (Schema::hasColumn('quotation_items', 'last_validated_markup_type')) {
                    $table->dropColumn('last_validated_markup_type');
                }
                if (Schema::hasColumn('quotation_items', 'last_validated_contract_rate')) {
                    $table->dropColumn('last_validated_contract_rate');
                }
                if (Schema::hasColumn('quotation_items', 'validation_notes')) {
                    $table->dropColumn('validation_notes');
                }
                if (Schema::hasColumn('quotation_items', 'validated_by')) {
                    $table->dropConstrainedForeignId('validated_by');
                }
                if (Schema::hasColumn('quotation_items', 'validated_at')) {
                    $table->dropColumn('validated_at');
                }
                if (Schema::hasColumn('quotation_items', 'is_validated')) {
                    $table->dropColumn('is_validated');
                }
                if (Schema::hasColumn('quotation_items', 'is_validation_required')) {
                    $table->dropColumn('is_validation_required');
                }
            });
        }

        if (Schema::hasTable('quotations')) {
            Schema::table('quotations', function (Blueprint $table): void {
                try {
                    $table->dropIndex('quotations_validation_status_idx');
                } catch (\Throwable $e) {
                    // no-op
                }

                if (Schema::hasColumn('quotations', 'validated_by')) {
                    $table->dropConstrainedForeignId('validated_by');
                }
                if (Schema::hasColumn('quotations', 'validated_at')) {
                    $table->dropColumn('validated_at');
                }
                if (Schema::hasColumn('quotations', 'validation_status')) {
                    $table->dropColumn('validation_status');
                }
            });
        }
    }
};
