<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::table('tourist_attractions', function (Blueprint $table) {
            foreach (['currency', 'other_fee_label', 'other_fee_per_pax', 'entrance_fee_per_pax'] as $column) {
                if (Schema::hasColumn('tourist_attractions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
