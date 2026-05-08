<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            if (! Schema::hasColumn('itineraries', 'term_conditions')) {
                $table->text('term_conditions')->nullable()->after('itinerary_exclude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            if (Schema::hasColumn('itineraries', 'term_conditions')) {
                $table->dropColumn('term_conditions');
            }
        });
    }
};

