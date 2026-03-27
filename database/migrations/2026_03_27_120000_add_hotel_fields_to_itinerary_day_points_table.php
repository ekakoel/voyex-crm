<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            if (! Schema::hasColumn('itinerary_day_points', 'start_hotel_id')) {
                $table->foreignId('start_hotel_id')
                    ->nullable()
                    ->after('start_airport_id')
                    ->constrained('hotels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('itinerary_day_points', 'start_hotel_room_id')) {
                $table->foreignId('start_hotel_room_id')
                    ->nullable()
                    ->after('start_hotel_id')
                    ->constrained('hotel_rooms')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('itinerary_day_points', 'end_hotel_id')) {
                $table->foreignId('end_hotel_id')
                    ->nullable()
                    ->after('end_airport_id')
                    ->constrained('hotels')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('itinerary_day_points', 'end_hotel_room_id')) {
                $table->foreignId('end_hotel_room_id')
                    ->nullable()
                    ->after('end_hotel_id')
                    ->constrained('hotel_rooms')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            if (Schema::hasColumn('itinerary_day_points', 'end_hotel_room_id')) {
                $table->dropConstrainedForeignId('end_hotel_room_id');
            }
            if (Schema::hasColumn('itinerary_day_points', 'end_hotel_id')) {
                $table->dropConstrainedForeignId('end_hotel_id');
            }
            if (Schema::hasColumn('itinerary_day_points', 'start_hotel_room_id')) {
                $table->dropConstrainedForeignId('start_hotel_room_id');
            }
            if (Schema::hasColumn('itinerary_day_points', 'start_hotel_id')) {
                $table->dropConstrainedForeignId('start_hotel_id');
            }
        });
    }
};
