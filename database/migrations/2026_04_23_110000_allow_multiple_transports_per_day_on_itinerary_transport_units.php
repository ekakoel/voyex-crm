<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return ! empty($rows);
    }

    public function up(): void
    {
        if (! Schema::hasTable('itinerary_transport_units')) {
            return;
        }

        // MySQL may use the existing unique index as a supporting index for FK(itinerary_id).
        // Add a replacement index first so dropping the unique index is allowed.
        if (! $this->hasIndex('itinerary_transport_units', 'itinerary_transport_units_itinerary_id_index')) {
            Schema::table('itinerary_transport_units', function (Blueprint $table) {
                $table->index('itinerary_id', 'itinerary_transport_units_itinerary_id_index');
            });
        }

        Schema::table('itinerary_transport_units', function (Blueprint $table) {
            if (Schema::hasColumn('itinerary_transport_units', 'day_number')) {
                $table->dropUnique('itinerary_transport_units_day_unique');
                $table->index(['itinerary_id', 'day_number'], 'itinerary_transport_units_itinerary_day_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('itinerary_transport_units')) {
            return;
        }

        Schema::table('itinerary_transport_units', function (Blueprint $table) {
            $table->dropIndex('itinerary_transport_units_itinerary_day_index');
            $table->unique(['itinerary_id', 'day_number'], 'itinerary_transport_units_day_unique');
        });

        // Optional cleanup of helper index if still present.
        if ($this->hasIndex('itinerary_transport_units', 'itinerary_transport_units_itinerary_id_index')) {
            Schema::table('itinerary_transport_units', function (Blueprint $table) {
                $table->dropIndex('itinerary_transport_units_itinerary_id_index');
            });
        }
    }
};
