<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            $table->time('day_start_time')->nullable()->after('day_number');
            $table->unsignedInteger('day_start_travel_minutes')->default(0)->after('day_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            $table->dropColumn(['day_start_time', 'day_start_travel_minutes']);
        });
    }
};
