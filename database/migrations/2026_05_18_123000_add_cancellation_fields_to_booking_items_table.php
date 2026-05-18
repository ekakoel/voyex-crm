<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_items', 'status')) {
                $table->string('status', 30)->default('active')->after('total');
            }
            if (! Schema::hasColumn('booking_items', 'cancellation_fee')) {
                $table->decimal('cancellation_fee', 18, 2)->default(0)->after('status');
            }
            if (! Schema::hasColumn('booking_items', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_fee');
            }
            if (! Schema::hasColumn('booking_items', 'cancellation_notes')) {
                $table->text('cancellation_notes')->nullable()->after('cancelled_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_items', 'cancellation_notes')) {
                $table->dropColumn('cancellation_notes');
            }
            if (Schema::hasColumn('booking_items', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            if (Schema::hasColumn('booking_items', 'cancellation_fee')) {
                $table->dropColumn('cancellation_fee');
            }
            if (Schema::hasColumn('booking_items', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

