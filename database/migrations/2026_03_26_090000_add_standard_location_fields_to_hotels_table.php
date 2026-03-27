<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (! Schema::hasColumn('hotels', 'location')) {
                $table->string('location')->nullable()->after('name');
            }
            if (! Schema::hasColumn('hotels', 'city')) {
                $table->string('city', 100)->nullable()->after('location');
            }
            if (! Schema::hasColumn('hotels', 'province')) {
                $table->string('province', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('hotels', 'country')) {
                $table->string('country', 100)->nullable()->after('province');
            }
        });

        DB::table('hotels')
            ->whereNull('location')
            ->orWhere('location', '')
            ->update([
                'location' => DB::raw("NULLIF(region, '')"),
            ]);

        DB::statement("\n            UPDATE hotels h\n            INNER JOIN destinations d ON d.id = h.destination_id\n            SET h.province = COALESCE(NULLIF(h.province, ''), NULLIF(d.province, ''), NULLIF(d.name, '')),\n                h.country = COALESCE(NULLIF(h.country, ''), NULLIF(d.country, ''))\n            WHERE h.destination_id IS NOT NULL\n        ");

        DB::table('hotels')
            ->whereNull('region')
            ->orWhere('region', '')
            ->update([
                'region' => DB::raw("COALESCE(NULLIF(location, ''), NULLIF(province, ''), NULLIF(city, ''))"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (Schema::hasColumn('hotels', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('hotels', 'province')) {
                $table->dropColumn('province');
            }
            if (Schema::hasColumn('hotels', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('hotels', 'location')) {
                $table->dropColumn('location');
            }
        });
    }
};
