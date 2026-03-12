<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_items', 'day_number')) {
                $table->unsignedInteger('day_number')->nullable()->after('discount');
            }
            if (! Schema::hasColumn('quotation_items', 'serviceable_meta')) {
                $table->json('serviceable_meta')->nullable()->after('day_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            if (Schema::hasColumn('quotation_items', 'serviceable_meta')) {
                $table->dropColumn('serviceable_meta');
            }
            if (Schema::hasColumn('quotation_items', 'day_number')) {
                $table->dropColumn('day_number');
            }
        });
    }
};
