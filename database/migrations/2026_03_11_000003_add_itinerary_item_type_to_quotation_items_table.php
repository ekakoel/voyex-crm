<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_items', 'itinerary_item_type')) {
                $table->string('itinerary_item_type', 50)->nullable()->after('serviceable_meta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            if (Schema::hasColumn('quotation_items', 'itinerary_item_type')) {
                $table->dropColumn('itinerary_item_type');
            }
        });
    }
};
