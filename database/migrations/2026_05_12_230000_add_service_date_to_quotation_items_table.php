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

        Schema::table('quotation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('quotation_items', 'service_date')) {
                $table->date('service_date')->nullable()->after('day_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table): void {
            if (Schema::hasColumn('quotation_items', 'service_date')) {
                $table->dropColumn('service_date');
            }
        });
    }
};

