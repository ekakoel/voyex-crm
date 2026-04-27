<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('quotations', 'order_number')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('order_number', 100)->nullable()->after('quotation_number');
            $table->index('order_number', 'quotations_order_number_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('quotations', 'order_number')) {
            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex('quotations_order_number_index');
            $table->dropColumn('order_number');
        });
    }
};
