<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->foreignId('activity_id')->nullable()->after('tourist_attraction_id')->constrained('activities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_id');
        });
    }
};
