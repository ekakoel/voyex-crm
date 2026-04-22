<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('island_transfers')) {
            return;
        }

        Schema::table('island_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('island_transfers', 'distance_km')) {
                $table->decimal('distance_km', 10, 2)->nullable()->after('duration_minutes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('island_transfers')) {
            return;
        }

        Schema::table('island_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('island_transfers', 'distance_km')) {
                $table->dropColumn('distance_km');
            }
        });
    }
};

