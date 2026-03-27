<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->unsignedInteger('pax_adult')->nullable()->after('pax');
            $table->unsignedInteger('pax_child')->nullable()->after('pax_adult');
        });
    }

    public function down(): void
    {
        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->dropColumn(['pax_adult', 'pax_child']);
        });
    }
};
