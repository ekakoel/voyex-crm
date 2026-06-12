<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_cancellation_policies')) {
            return;
        }

        Schema::table('service_cancellation_policies', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_cancellation_policies', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('serviceable_id')->constrained('vendors')->nullOnDelete();
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'season_type')) {
                $table->string('season_type', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'start_date')) {
                $table->date('start_date')->nullable()->after('season_type');
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'cancel_before_hours')) {
                $table->unsignedInteger('cancel_before_hours')->nullable()->after('end_date');
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'fee_type')) {
                $table->string('fee_type', 30)->nullable()->after('cancel_before_hours');
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'fee_value')) {
                $table->decimal('fee_value', 18, 2)->nullable()->after('fee_type');
            }
            if (! Schema::hasColumn('service_cancellation_policies', 'description')) {
                $table->text('description')->nullable()->after('fee_value');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_cancellation_policies')) {
            return;
        }

        Schema::table('service_cancellation_policies', function (Blueprint $table): void {
            foreach (['description', 'fee_value', 'fee_type', 'cancel_before_hours', 'end_date', 'start_date', 'season_type'] as $column) {
                if (Schema::hasColumn('service_cancellation_policies', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('service_cancellation_policies', 'vendor_id')) {
                try {
                    $table->dropConstrainedForeignId('vendor_id');
                } catch (\Throwable) {
                    $table->dropColumn('vendor_id');
                }
            }
        });
    }
};

