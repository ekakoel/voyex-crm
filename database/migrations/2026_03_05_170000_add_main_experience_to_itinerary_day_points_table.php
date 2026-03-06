<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            $table->string('main_experience_type', 20)->nullable()->after('day_start_travel_minutes');
            $table->foreignId('main_tourist_attraction_id')->nullable()->after('main_experience_type')->constrained('tourist_attractions')->nullOnDelete();
            $table->foreignId('main_activity_id')->nullable()->after('main_tourist_attraction_id')->constrained('activities')->nullOnDelete();
            $table->foreignId('main_food_beverage_id')->nullable()->after('main_activity_id')->constrained('food_beverages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            $table->dropConstrainedForeignId('main_food_beverage_id');
            $table->dropConstrainedForeignId('main_activity_id');
            $table->dropConstrainedForeignId('main_tourist_attraction_id');
            $table->dropColumn('main_experience_type');
        });
    }
};
