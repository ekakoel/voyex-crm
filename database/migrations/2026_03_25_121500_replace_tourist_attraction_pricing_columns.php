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
            if (! Schema::hasColumn('tourist_attractions', 'contract_rate_per_pax')) {
                $table->decimal('contract_rate_per_pax', 12, 2)->nullable()->after('ideal_visit_minutes');
            }
            if (! Schema::hasColumn('tourist_attractions', 'publish_rate_per_pax')) {
                $table->decimal('publish_rate_per_pax', 12, 2)->nullable()->after('contract_rate_per_pax');
            }
        });

        $hasEntrance = Schema::hasColumn('tourist_attractions', 'entrance_fee_per_pax');
        $hasOther = Schema::hasColumn('tourist_attractions', 'other_fee_per_pax');

        if ($hasEntrance || $hasOther) {
            $priceExpression = $hasEntrance && $hasOther
                ? '(COALESCE(entrance_fee_per_pax, 0) + COALESCE(other_fee_per_pax, 0))'
                : ($hasEntrance ? 'COALESCE(entrance_fee_per_pax, 0)' : 'COALESCE(other_fee_per_pax, 0)');

            DB::statement("UPDATE tourist_attractions SET contract_rate_per_pax = COALESCE(contract_rate_per_pax, {$priceExpression}), publish_rate_per_pax = COALESCE(publish_rate_per_pax, {$priceExpression})");
        }

        Schema::table('tourist_attractions', function (Blueprint $table) {
            foreach (['currency', 'other_fee_label', 'other_fee_per_pax', 'entrance_fee_per_pax'] as $column) {
                if (Schema::hasColumn('tourist_attractions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (! Schema::hasColumn('tourist_attractions', 'entrance_fee_per_pax')) {
                $table->decimal('entrance_fee_per_pax', 12, 2)->nullable()->after('ideal_visit_minutes');
            }
            if (! Schema::hasColumn('tourist_attractions', 'other_fee_per_pax')) {
                $table->decimal('other_fee_per_pax', 12, 2)->nullable()->after('entrance_fee_per_pax');
            }
            if (! Schema::hasColumn('tourist_attractions', 'other_fee_label')) {
                $table->string('other_fee_label', 100)->nullable()->after('other_fee_per_pax');
            }
            if (! Schema::hasColumn('tourist_attractions', 'currency')) {
                $table->char('currency', 3)->default('IDR')->after('other_fee_label');
            }
        });

        DB::statement('UPDATE tourist_attractions SET entrance_fee_per_pax = COALESCE(entrance_fee_per_pax, publish_rate_per_pax, contract_rate_per_pax), other_fee_per_pax = COALESCE(other_fee_per_pax, 0), other_fee_label = COALESCE(other_fee_label, \'Other Fee\'), currency = COALESCE(currency, \'IDR\')');

        Schema::table('tourist_attractions', function (Blueprint $table) {
            foreach (['publish_rate_per_pax', 'contract_rate_per_pax'] as $column) {
                if (Schema::hasColumn('tourist_attractions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
