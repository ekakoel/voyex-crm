<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('hotels', 'destination_id')) {
            Schema::table('hotels', function (Blueprint $table) {
                $table->foreignId('destination_id')
                    ->nullable()
                    ->after('region')
                    ->constrained('destinations')
                    ->nullOnDelete();
            });
        }

        DB::statement("\n            UPDATE hotels h\n            INNER JOIN destinations d ON LOWER(TRIM(d.province)) = LOWER(TRIM(h.region))\n            SET h.destination_id = d.id\n            WHERE h.destination_id IS NULL\n              AND COALESCE(TRIM(h.region), '') <> ''\n        ");

        DB::statement("\n            UPDATE hotels h\n            INNER JOIN destinations d ON LOWER(TRIM(d.name)) = LOWER(TRIM(h.region))\n            SET h.destination_id = d.id\n            WHERE h.destination_id IS NULL\n              AND COALESCE(TRIM(h.region), '') <> ''\n        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('hotels', 'destination_id')) {
            return;
        }

        Schema::table('hotels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
        });
    }
};
