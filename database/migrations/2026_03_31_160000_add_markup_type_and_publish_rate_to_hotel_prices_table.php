<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotel_prices')) {
            return;
        }

        Schema::table('hotel_prices', function (Blueprint $table) {
            if (! Schema::hasColumn('hotel_prices', 'markup_type')) {
                $table->string('markup_type', 20)->default('fixed')->after('contract_rate');
            }
            if (! Schema::hasColumn('hotel_prices', 'publish_rate')) {
                $table->decimal('publish_rate', 15, 0)->nullable()->after('markup');
            }
        });

        if (Schema::hasColumn('hotel_prices', 'contract_rate')
            && Schema::hasColumn('hotel_prices', 'markup')
            && Schema::hasColumn('hotel_prices', 'markup_type')
            && Schema::hasColumn('hotel_prices', 'publish_rate')) {
            DB::statement("\n                UPDATE hotel_prices\n                SET\n                    markup_type = 'fixed',\n                    publish_rate = COALESCE(contract_rate, 0) + COALESCE(markup, 0)\n            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('hotel_prices')) {
            return;
        }

        Schema::table('hotel_prices', function (Blueprint $table) {
            if (Schema::hasColumn('hotel_prices', 'publish_rate')) {
                $table->dropColumn('publish_rate');
            }
            if (Schema::hasColumn('hotel_prices', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
        });
    }
};
