<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        Schema::table('booking_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_items', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('serviceable_id')->constrained('vendors')->nullOnDelete();
            }
            if (! Schema::hasColumn('booking_items', 'service_date')) {
                $table->date('service_date')->nullable()->after('day_number');
            }
            if (! Schema::hasColumn('booking_items', 'sell_price')) {
                $table->decimal('sell_price', 18, 2)->default(0)->after('unit_price');
            }
            if (! Schema::hasColumn('booking_items', 'contract_rate')) {
                $table->decimal('contract_rate', 18, 2)->default(0)->after('sell_price');
            }
            if (! Schema::hasColumn('booking_items', 'markup_type')) {
                $table->string('markup_type', 20)->nullable()->after('contract_rate');
            }
            if (! Schema::hasColumn('booking_items', 'markup')) {
                $table->decimal('markup', 18, 2)->default(0)->after('markup_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        Schema::table('booking_items', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_items', 'markup')) {
                $table->dropColumn('markup');
            }
            if (Schema::hasColumn('booking_items', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
            if (Schema::hasColumn('booking_items', 'contract_rate')) {
                $table->dropColumn('contract_rate');
            }
            if (Schema::hasColumn('booking_items', 'sell_price')) {
                $table->dropColumn('sell_price');
            }
            if (Schema::hasColumn('booking_items', 'service_date')) {
                $table->dropColumn('service_date');
            }
            if (Schema::hasColumn('booking_items', 'vendor_id')) {
                try {
                    $table->dropConstrainedForeignId('vendor_id');
                } catch (\Throwable) {
                    $table->dropColumn('vendor_id');
                }
            }
        });
    }
};

