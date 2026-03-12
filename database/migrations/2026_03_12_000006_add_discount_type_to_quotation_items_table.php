<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('quotation_items', 'discount_type')) {
                $table->enum('discount_type', ['percent', 'fixed'])->default('fixed')->after('unit_price');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table) {
            if (Schema::hasColumn('quotation_items', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });
    }
};
