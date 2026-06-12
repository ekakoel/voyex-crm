<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('food_beverages')) {
            return;
        }

        Schema::table('food_beverages', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_beverages', 'adult_contract_rate')) {
                $table->decimal('adult_contract_rate', 15, 0)->nullable()->after('duration_minutes');
            }
            if (! Schema::hasColumn('food_beverages', 'child_contract_rate')) {
                $table->decimal('child_contract_rate', 15, 0)->nullable()->after('adult_contract_rate');
            }
            if (! Schema::hasColumn('food_beverages', 'adult_markup_type')) {
                $table->string('adult_markup_type', 20)->default('fixed')->after('child_contract_rate');
            }
            if (! Schema::hasColumn('food_beverages', 'adult_markup')) {
                $table->decimal('adult_markup', 15, 0)->default(0)->after('adult_markup_type');
            }
            if (! Schema::hasColumn('food_beverages', 'child_markup_type')) {
                $table->string('child_markup_type', 20)->default('fixed')->after('adult_markup');
            }
            if (! Schema::hasColumn('food_beverages', 'child_markup')) {
                $table->decimal('child_markup', 15, 0)->default(0)->after('child_markup_type');
            }
            if (! Schema::hasColumn('food_beverages', 'adult_publish_rate')) {
                $table->decimal('adult_publish_rate', 15, 0)->nullable()->after('child_markup');
            }
            if (! Schema::hasColumn('food_beverages', 'child_publish_rate')) {
                $table->decimal('child_publish_rate', 15, 0)->nullable()->after('adult_publish_rate');
            }
        });

        DB::statement("
            UPDATE food_beverages
            SET
                adult_contract_rate = COALESCE(adult_contract_rate, contract_rate, 0),
                adult_markup_type = COALESCE(NULLIF(adult_markup_type, ''), markup_type, 'fixed'),
                adult_markup = COALESCE(adult_markup, markup, 0),
                adult_publish_rate = COALESCE(adult_publish_rate, publish_rate, 0),
                child_contract_rate = COALESCE(child_contract_rate, 0),
                child_markup_type = COALESCE(NULLIF(child_markup_type, ''), 'fixed'),
                child_markup = COALESCE(child_markup, 0),
                child_publish_rate = COALESCE(child_publish_rate, 0)
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('food_beverages')) {
            return;
        }

        Schema::table('food_beverages', function (Blueprint $table): void {
            foreach ([
                'child_publish_rate',
                'adult_publish_rate',
                'child_markup',
                'child_markup_type',
                'adult_markup',
                'adult_markup_type',
                'child_contract_rate',
                'adult_contract_rate',
            ] as $column) {
                if (Schema::hasColumn('food_beverages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
