<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (! Schema::hasColumn('itineraries', 'destination_id')) {
                $table->foreignId('destination_id')
                    ->nullable()
                    ->after('destination')
                    ->constrained('destinations')
                    ->nullOnDelete();
            }
        });

        DB::statement(
            "UPDATE itineraries
            INNER JOIN destinations ON destinations.name = itineraries.destination
            SET itineraries.destination_id = destinations.id
            WHERE itineraries.destination_id IS NULL
              AND itineraries.destination IS NOT NULL
              AND itineraries.destination <> ''"
        );
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (Schema::hasColumn('itineraries', 'destination_id')) {
                $table->dropConstrainedForeignId('destination_id');
            }
        });
    }
};
