<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table): void {
            if (! Schema::hasColumn('itinerary_day_points', 'end_hotel_area')) {
                $table->string('end_hotel_area', 150)
                    ->nullable()
                    ->after('end_hotel_booking_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table): void {
            if (Schema::hasColumn('itinerary_day_points', 'end_hotel_area')) {
                $table->dropColumn('end_hotel_area');
            }
        });
    }
};

