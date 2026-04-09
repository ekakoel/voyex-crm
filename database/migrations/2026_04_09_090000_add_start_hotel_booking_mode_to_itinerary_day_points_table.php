<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table): void {
            if (! Schema::hasColumn('itinerary_day_points', 'start_hotel_booking_mode')) {
                $table->string('start_hotel_booking_mode', 20)
                    ->nullable()
                    ->after('start_hotel_room_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table): void {
            if (Schema::hasColumn('itinerary_day_points', 'start_hotel_booking_mode')) {
                $table->dropColumn('start_hotel_booking_mode');
            }
        });
    }
};
