<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->unsignedInteger('activity_pax')->nullable()->after('activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->dropColumn('activity_pax');
        });
    }
};
