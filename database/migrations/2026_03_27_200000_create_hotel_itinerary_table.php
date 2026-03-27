<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotel_itinerary')) {
            Schema::create('hotel_itinerary', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('itinerary_id')->constrained('itineraries')->cascadeOnDelete();
                $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
                $table->unsignedInteger('day_number')->default(1);
                $table->unsignedInteger('night_count')->default(1);
                $table->unsignedInteger('room_count')->default(1);
                $table->timestamps();

                $table->unique(['itinerary_id', 'hotel_id', 'day_number'], 'hotel_itinerary_itin_hotel_day_unique');
            });
        }

        if (
            Schema::hasTable('hotel_itinerary')
            && Schema::hasTable('itinerary_day_points')
            && Schema::hasColumn('itinerary_day_points', 'end_hotel_id')
            && DB::table('hotel_itinerary')->count() === 0
        ) {
            $points = DB::table('itinerary_day_points')
                ->select(['itinerary_id', 'day_number', 'end_hotel_id'])
                ->whereNotNull('end_hotel_id')
                ->orderBy('itinerary_id')
                ->orderBy('day_number')
                ->get();

            $payload = [];
            $activeStay = null;
            $timestamp = now();

            foreach ($points as $point) {
                $itineraryId = (int) $point->itinerary_id;
                $dayNumber = (int) $point->day_number;
                $hotelId = (int) $point->end_hotel_id;

                if ($hotelId <= 0) {
                    continue;
                }

                $isContinuation = $activeStay !== null
                    && $activeStay['itinerary_id'] === $itineraryId
                    && $activeStay['hotel_id'] === $hotelId
                    && ($activeStay['day_number'] + $activeStay['night_count']) === $dayNumber;

                if ($isContinuation) {
                    $activeStay['night_count']++;
                    continue;
                }

                if ($activeStay !== null) {
                    $payload[] = $activeStay;
                }

                $activeStay = [
                    'itinerary_id' => $itineraryId,
                    'hotel_id' => $hotelId,
                    'day_number' => $dayNumber,
                    'night_count' => 1,
                    'room_count' => 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if ($activeStay !== null) {
                $payload[] = $activeStay;
            }

            if ($payload !== []) {
                DB::table('hotel_itinerary')->insert($payload);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_itinerary');
    }
};
