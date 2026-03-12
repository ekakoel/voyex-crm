<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            $table->text('day_include')->nullable()->after('day_start_travel_minutes');
            $table->text('day_exclude')->nullable()->after('day_include');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            $table->dropColumn(['day_include', 'day_exclude']);
        });
    }
};

