<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accommodation_itinerary', function (Blueprint $table) {
            if (! Schema::hasColumn('accommodation_itinerary', 'room_count')) {
                $table->unsignedInteger('room_count')->default(1)->after('night_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_itinerary', function (Blueprint $table) {
            if (Schema::hasColumn('accommodation_itinerary', 'room_count')) {
                $table->dropColumn('room_count');
            }
        });
    }
};
