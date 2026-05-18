<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('itinerary_day_points')) {
            return;
        }

        DB::table('itinerary_day_points')
            ->where('start_point_type', 'hotel')
            ->where('start_hotel_booking_mode', 'self')
            ->update([
                'start_hotel_id' => null,
                'start_hotel_room_id' => null,
            ]);

        DB::table('itinerary_day_points')
            ->where('end_point_type', 'hotel')
            ->where('end_hotel_booking_mode', 'self')
            ->update([
                'end_hotel_id' => null,
                'end_hotel_room_id' => null,
            ]);
    }

    public function down(): void
    {
        // No-op: previous hotel linkage for self-booked rows cannot be restored safely.
    }
};

