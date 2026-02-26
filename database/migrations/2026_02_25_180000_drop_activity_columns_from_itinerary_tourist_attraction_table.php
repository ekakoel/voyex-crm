<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            if (Schema::hasColumn('itinerary_tourist_attraction', 'activity_id')) {
                $table->dropConstrainedForeignId('activity_id');
            }

            if (Schema::hasColumn('itinerary_tourist_attraction', 'activity_pax')) {
                $table->dropColumn('activity_pax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            if (! Schema::hasColumn('itinerary_tourist_attraction', 'activity_id')) {
                $table->foreignId('activity_id')
                    ->nullable()
                    ->after('tourist_attraction_id')
                    ->constrained('activities')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('itinerary_tourist_attraction', 'activity_pax')) {
                $table->unsignedInteger('activity_pax')
                    ->nullable()
                    ->after('activity_id');
            }
        });
    }
};
