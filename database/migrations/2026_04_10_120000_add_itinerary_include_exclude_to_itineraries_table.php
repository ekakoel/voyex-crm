<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            if (! Schema::hasColumn('itineraries', 'itinerary_include')) {
                $table->text('itinerary_include')->nullable()->after('description');
            }
            if (! Schema::hasColumn('itineraries', 'itinerary_exclude')) {
                $table->text('itinerary_exclude')->nullable()->after('itinerary_include');
            }
        });

        DB::table('itineraries')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($itineraries): void {
                foreach ($itineraries as $itinerary) {
                    $dayPoints = DB::table('itinerary_day_points')
                        ->select('day_number', 'day_include', 'day_exclude')
                        ->where('itinerary_id', $itinerary->id)
                        ->orderBy('day_number')
                        ->get();

                    $includeLines = [];
                    $excludeLines = [];

                    foreach ($dayPoints as $point) {
                        $day = (int) ($point->day_number ?? 0);
                        $dayInclude = trim((string) ($point->day_include ?? ''));
                        $dayExclude = trim((string) ($point->day_exclude ?? ''));

                        if ($day > 0 && $dayInclude !== '') {
                            $includeLines[] = 'Day ' . $day . ': ' . $dayInclude;
                        }
                        if ($day > 0 && $dayExclude !== '') {
                            $excludeLines[] = 'Day ' . $day . ': ' . $dayExclude;
                        }
                    }

                    $payload = [];
                    if ($includeLines !== []) {
                        $payload['itinerary_include'] = implode("\n", $includeLines);
                    }
                    if ($excludeLines !== []) {
                        $payload['itinerary_exclude'] = implode("\n", $excludeLines);
                    }

                    if ($payload !== []) {
                        DB::table('itineraries')
                            ->where('id', $itinerary->id)
                            ->update($payload);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('itineraries', 'itinerary_include')) {
                $dropColumns[] = 'itinerary_include';
            }
            if (Schema::hasColumn('itineraries', 'itinerary_exclude')) {
                $dropColumns[] = 'itinerary_exclude';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

