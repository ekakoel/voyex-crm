<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            if (Schema::hasColumn('airports', 'iata_code')) {
                $table->dropColumn('iata_code');
            }
            if (Schema::hasColumn('airports', 'icao_code')) {
                $table->dropColumn('icao_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            if (! Schema::hasColumn('airports', 'iata_code')) {
                $table->string('iata_code', 10)->nullable()->index();
            }
            if (! Schema::hasColumn('airports', 'icao_code')) {
                $table->string('icao_code', 10)->nullable()->index();
            }
        });
    }
};
