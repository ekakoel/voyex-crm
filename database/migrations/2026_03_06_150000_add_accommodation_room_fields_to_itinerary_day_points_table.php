<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            if (! Schema::hasColumn('itinerary_day_points', 'start_accommodation_room_id')) {
                $table->foreignId('start_accommodation_room_id')
                    ->nullable()
                    ->after('start_accommodation_id')
                    ->constrained('accommodation_rooms')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('itinerary_day_points', 'start_accommodation_room_qty')) {
                $table->unsignedInteger('start_accommodation_room_qty')
                    ->nullable()
                    ->after('start_accommodation_room_id');
            }
            if (! Schema::hasColumn('itinerary_day_points', 'end_accommodation_room_id')) {
                $table->foreignId('end_accommodation_room_id')
                    ->nullable()
                    ->after('end_accommodation_id')
                    ->constrained('accommodation_rooms')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('itinerary_day_points', 'end_accommodation_room_qty')) {
                $table->unsignedInteger('end_accommodation_room_qty')
                    ->nullable()
                    ->after('end_accommodation_room_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_day_points', function (Blueprint $table) {
            if (Schema::hasColumn('itinerary_day_points', 'end_accommodation_room_qty')) {
                $table->dropColumn('end_accommodation_room_qty');
            }
            if (Schema::hasColumn('itinerary_day_points', 'end_accommodation_room_id')) {
                $table->dropConstrainedForeignId('end_accommodation_room_id');
            }
            if (Schema::hasColumn('itinerary_day_points', 'start_accommodation_room_qty')) {
                $table->dropColumn('start_accommodation_room_qty');
            }
            if (Schema::hasColumn('itinerary_day_points', 'start_accommodation_room_id')) {
                $table->dropConstrainedForeignId('start_accommodation_room_id');
            }
        });
    }
};

