<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotel_prices')) {
            return;
        }

        if (Schema::hasColumn('hotel_prices', 'contract_rate')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY contract_rate DECIMAL(15,0) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('hotel_prices', 'markup')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY markup DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('hotel_prices', 'publish_rate')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY publish_rate DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('hotel_prices', 'kick_back')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY kick_back DECIMAL(15,0) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('hotel_prices')) {
            return;
        }

        if (Schema::hasColumn('hotel_prices', 'contract_rate')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY contract_rate INT NOT NULL');
        }
        if (Schema::hasColumn('hotel_prices', 'markup')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY markup INT NULL');
        }
        if (Schema::hasColumn('hotel_prices', 'publish_rate')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY publish_rate DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('hotel_prices', 'kick_back')) {
            DB::statement('ALTER TABLE hotel_prices MODIFY kick_back INT NULL');
        }
    }
};
