<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'itinerary_id')) {
                $table->foreignId('itinerary_id')
                    ->nullable()
                    ->after('inquiry_id')
                    ->constrained('itineraries')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'itinerary_id')) {
                $table->dropForeign(['itinerary_id']);
                $table->dropColumn('itinerary_id');
            }
        });
    }
};
