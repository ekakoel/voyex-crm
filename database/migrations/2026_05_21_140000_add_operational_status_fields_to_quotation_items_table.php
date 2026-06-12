<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotation_items', 'status')) {
                $table->string('status', 50)->default('active')->after('itinerary_item_type');
                $table->index('status', 'quotation_items_status_index');
            }

            if (! Schema::hasColumn('quotation_items', 'cancellation_fee_type')) {
                $table->string('cancellation_fee_type', 20)->nullable()->after('status');
            }

            if (! Schema::hasColumn('quotation_items', 'cancellation_fee_value')) {
                $table->decimal('cancellation_fee_value', 15, 2)->nullable()->after('cancellation_fee_type');
            }

            if (! Schema::hasColumn('quotation_items', 'cancellation_fee_amount')) {
                $table->decimal('cancellation_fee_amount', 15, 2)->nullable()->after('cancellation_fee_value');
            }

            if (! Schema::hasColumn('quotation_items', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancellation_fee_amount');
            }

            if (! Schema::hasColumn('quotation_items', 'actual_used_at')) {
                $table->timestamp('actual_used_at')->nullable()->after('cancellation_reason');
            }

            if (! Schema::hasColumn('quotation_items', 'replaced_by_item_id')) {
                $table->foreignId('replaced_by_item_id')
                    ->nullable()
                    ->after('actual_used_at')
                    ->constrained('quotation_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table): void {
            if (Schema::hasColumn('quotation_items', 'replaced_by_item_id')) {
                $table->dropConstrainedForeignId('replaced_by_item_id');
            }

            if (Schema::hasColumn('quotation_items', 'actual_used_at')) {
                $table->dropColumn('actual_used_at');
            }

            if (Schema::hasColumn('quotation_items', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }

            if (Schema::hasColumn('quotation_items', 'cancellation_fee_amount')) {
                $table->dropColumn('cancellation_fee_amount');
            }

            if (Schema::hasColumn('quotation_items', 'cancellation_fee_value')) {
                $table->dropColumn('cancellation_fee_value');
            }

            if (Schema::hasColumn('quotation_items', 'cancellation_fee_type')) {
                $table->dropColumn('cancellation_fee_type');
            }

            if (Schema::hasColumn('quotation_items', 'status')) {
                $table->dropIndex('quotation_items_status_index');
                $table->dropColumn('status');
            }
        });
    }
};
