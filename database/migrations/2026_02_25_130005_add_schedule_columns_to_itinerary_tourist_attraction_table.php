<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->unsignedInteger('day_number')->default(1)->after('tourist_attraction_id');
            $table->time('start_time')->nullable()->after('day_number');
            $table->time('end_time')->nullable()->after('start_time');
            $table->unsignedInteger('visit_order')->default(1)->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->dropColumn(['day_number', 'start_time', 'end_time', 'visit_order']);
        });
    }
};
