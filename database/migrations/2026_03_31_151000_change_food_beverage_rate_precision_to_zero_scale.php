<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('food_beverages')) {
            return;
        }

        if (Schema::hasColumn('food_beverages', 'contract_rate')) {
            DB::statement('ALTER TABLE food_beverages MODIFY contract_rate DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('food_beverages', 'markup')) {
            DB::statement('ALTER TABLE food_beverages MODIFY markup DECIMAL(15,0) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('food_beverages', 'publish_rate')) {
            DB::statement('ALTER TABLE food_beverages MODIFY publish_rate DECIMAL(15,0) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('food_beverages')) {
            return;
        }

        if (Schema::hasColumn('food_beverages', 'contract_rate')) {
            DB::statement('ALTER TABLE food_beverages MODIFY contract_rate DECIMAL(12,2) NULL');
        }
        if (Schema::hasColumn('food_beverages', 'markup')) {
            DB::statement('ALTER TABLE food_beverages MODIFY markup DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('food_beverages', 'publish_rate')) {
            DB::statement('ALTER TABLE food_beverages MODIFY publish_rate DECIMAL(12,2) NULL');
        }
    }
};

