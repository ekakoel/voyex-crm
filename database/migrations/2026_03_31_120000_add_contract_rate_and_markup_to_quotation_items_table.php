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
            if (! Schema::hasColumn('quotation_items', 'contract_rate')) {
                $table->decimal('contract_rate', 15, 2)->default(0)->after('qty');
            }
            if (! Schema::hasColumn('quotation_items', 'markup_type')) {
                $table->string('markup_type', 20)->default('fixed')->after('contract_rate');
            }
            if (! Schema::hasColumn('quotation_items', 'markup')) {
                $table->decimal('markup', 15, 2)->default(0)->after('markup_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotation_items')) {
            return;
        }

        Schema::table('quotation_items', function (Blueprint $table) {
            if (Schema::hasColumn('quotation_items', 'markup')) {
                $table->dropColumn('markup');
            }
            if (Schema::hasColumn('quotation_items', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
            if (Schema::hasColumn('quotation_items', 'contract_rate')) {
                $table->dropColumn('contract_rate');
            }
        });
    }
};

