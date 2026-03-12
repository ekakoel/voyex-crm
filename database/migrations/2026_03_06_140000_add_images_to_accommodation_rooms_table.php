<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accommodation_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('accommodation_rooms', 'images')) {
                $table->json('images')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accommodation_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('accommodation_rooms', 'images')) {
                $table->dropColumn('images');
            }
        });
    }
};

