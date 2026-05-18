<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_items', 'cancellation_policy_snapshot')) {
                $table->json('cancellation_policy_snapshot')->nullable()->after('cancellation_notes');
            }
            if (! Schema::hasColumn('booking_items', 'cancellation_fee_calculated')) {
                $table->decimal('cancellation_fee_calculated', 18, 2)->default(0)->after('cancellation_policy_snapshot');
            }
            if (! Schema::hasColumn('booking_items', 'cancellation_fee_overridden')) {
                $table->boolean('cancellation_fee_overridden')->default(false)->after('cancellation_fee_calculated');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_items', 'cancellation_fee_overridden')) {
                $table->dropColumn('cancellation_fee_overridden');
            }
            if (Schema::hasColumn('booking_items', 'cancellation_fee_calculated')) {
                $table->dropColumn('cancellation_fee_calculated');
            }
            if (Schema::hasColumn('booking_items', 'cancellation_policy_snapshot')) {
                $table->dropColumn('cancellation_policy_snapshot');
            }
        });
    }
};

