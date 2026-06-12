<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_adjustments', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_adjustments', 'quotation_id')) {
                $table->foreignId('quotation_id')->nullable()->after('booking_item_id')->constrained('quotations')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_adjustments', 'type')) {
                $table->string('type', 50)->nullable()->after('adjustment_type');
            }
            if (! Schema::hasColumn('booking_adjustments', 'amount_type')) {
                $table->string('amount_type', 20)->nullable()->after('type');
            }
            if (! Schema::hasColumn('booking_adjustments', 'percentage')) {
                $table->decimal('percentage', 8, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('booking_adjustments', 'calculated_amount')) {
                $table->decimal('calculated_amount', 15, 2)->nullable()->after('percentage');
            }
            if (! Schema::hasColumn('booking_adjustments', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('impact_type')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('booking_adjustments')
            ->whereNull('type')
            ->update(['type' => DB::raw('adjustment_type')]);

        DB::table('booking_adjustments')
            ->whereNull('calculated_amount')
            ->update(['calculated_amount' => DB::raw('amount')]);

        DB::table('booking_adjustments')
            ->whereNull('created_by')
            ->update(['created_by' => DB::raw('requested_by')]);

        DB::table('booking_adjustments')
            ->whereNull('quotation_id')
            ->update([
                'quotation_id' => DB::raw('(SELECT b.quotation_id FROM bookings b WHERE b.id = booking_adjustments.booking_id)')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('booking_adjustments', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('booking_adjustments', 'quotation_id')) {
                $table->dropConstrainedForeignId('quotation_id');
            }
            if (Schema::hasColumn('booking_adjustments', 'calculated_amount')) {
                $table->dropColumn('calculated_amount');
            }
            if (Schema::hasColumn('booking_adjustments', 'percentage')) {
                $table->dropColumn('percentage');
            }
            if (Schema::hasColumn('booking_adjustments', 'amount_type')) {
                $table->dropColumn('amount_type');
            }
            if (Schema::hasColumn('booking_adjustments', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
