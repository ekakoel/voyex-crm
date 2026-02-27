<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->json('gallery_images')->nullable()->after('notes');
        });

        Schema::table('tourist_attractions', function (Blueprint $table) {
            $table->json('gallery_images')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('gallery_images');
        });

        Schema::table('tourist_attractions', function (Blueprint $table) {
            $table->dropColumn('gallery_images');
        });
    }
};

