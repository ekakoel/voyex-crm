<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (! Schema::hasColumn('itineraries', 'duration_nights')) {
                $table->unsignedInteger('duration_nights')->default(0)->after('duration_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (Schema::hasColumn('itineraries', 'duration_nights')) {
                $table->dropColumn('duration_nights');
            }
        });
    }
};

