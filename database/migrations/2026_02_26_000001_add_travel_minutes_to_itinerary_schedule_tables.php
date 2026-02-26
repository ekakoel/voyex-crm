<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->unsignedInteger('travel_minutes_to_next')->nullable()->after('end_time');
        });

        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->unsignedInteger('travel_minutes_to_next')->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->dropColumn('travel_minutes_to_next');
        });

        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->dropColumn('travel_minutes_to_next');
        });
    }
};

