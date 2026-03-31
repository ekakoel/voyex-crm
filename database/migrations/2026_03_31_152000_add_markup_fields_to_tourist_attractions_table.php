<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (! Schema::hasColumn('tourist_attractions', 'markup_type')) {
                $table->string('markup_type', 20)->default('fixed')->after('contract_rate_per_pax');
            }
            if (! Schema::hasColumn('tourist_attractions', 'markup')) {
                $table->decimal('markup', 15, 2)->default(0)->after('markup_type');
            }
        });

        if (Schema::hasColumn('tourist_attractions', 'contract_rate_per_pax')
            && Schema::hasColumn('tourist_attractions', 'publish_rate_per_pax')
            && Schema::hasColumn('tourist_attractions', 'markup')
            && Schema::hasColumn('tourist_attractions', 'markup_type')) {
            DB::statement("
                UPDATE tourist_attractions
                SET
                    markup_type = 'fixed',
                    markup = GREATEST(COALESCE(publish_rate_per_pax, 0) - COALESCE(contract_rate_per_pax, 0), 0)
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (Schema::hasColumn('tourist_attractions', 'markup')) {
                $table->dropColumn('markup');
            }
            if (Schema::hasColumn('tourist_attractions', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
        });
    }
};

