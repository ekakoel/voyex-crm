<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('food_beverages', 'contract_price') && ! Schema::hasColumn('food_beverages', 'contract_rate')) {
            DB::statement('ALTER TABLE food_beverages CHANGE contract_price contract_rate DECIMAL(12,2) NULL');
        }

        if (Schema::hasColumn('food_beverages', 'agent_price') && ! Schema::hasColumn('food_beverages', 'publish_rate')) {
            DB::statement('ALTER TABLE food_beverages CHANGE agent_price publish_rate DECIMAL(12,2) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('food_beverages', 'contract_rate') && ! Schema::hasColumn('food_beverages', 'contract_price')) {
            DB::statement('ALTER TABLE food_beverages CHANGE contract_rate contract_price DECIMAL(12,2) NULL');
        }

        if (Schema::hasColumn('food_beverages', 'publish_rate') && ! Schema::hasColumn('food_beverages', 'agent_price')) {
            DB::statement('ALTER TABLE food_beverages CHANGE publish_rate agent_price DECIMAL(12,2) NULL');
        }
    }
};
