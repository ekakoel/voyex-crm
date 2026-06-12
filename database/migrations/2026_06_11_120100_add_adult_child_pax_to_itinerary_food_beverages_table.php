<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('itinerary_food_beverages')) {
            return;
        }

        Schema::table('itinerary_food_beverages', function (Blueprint $table): void {
            if (! Schema::hasColumn('itinerary_food_beverages', 'pax_adult')) {
                $table->unsignedInteger('pax_adult')->nullable()->after('pax');
            }
            if (! Schema::hasColumn('itinerary_food_beverages', 'pax_child')) {
                $table->unsignedInteger('pax_child')->nullable()->after('pax_adult');
            }
        });

        DB::statement("
            UPDATE itinerary_food_beverages
            SET
                pax_adult = COALESCE(pax_adult, pax, 1),
                pax_child = COALESCE(pax_child, 0)
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('itinerary_food_beverages')) {
            return;
        }

        Schema::table('itinerary_food_beverages', function (Blueprint $table): void {
            $dropColumns = [];
            if (Schema::hasColumn('itinerary_food_beverages', 'pax_adult')) {
                $dropColumns[] = 'pax_adult';
            }
            if (Schema::hasColumn('itinerary_food_beverages', 'pax_child')) {
                $dropColumns[] = 'pax_child';
            }
            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
