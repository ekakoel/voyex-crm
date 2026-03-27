<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (!Schema::hasColumn('hotels', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('map');
            }
            if (!Schema::hasColumn('hotels', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            if (Schema::hasColumn('hotels', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('hotels', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};
