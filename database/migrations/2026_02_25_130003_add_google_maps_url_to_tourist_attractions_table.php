<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_attractions', function (Blueprint $table) {
            $table->string('google_maps_url')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('tourist_attractions', function (Blueprint $table) {
            $table->dropColumn('google_maps_url');
        });
    }
};
