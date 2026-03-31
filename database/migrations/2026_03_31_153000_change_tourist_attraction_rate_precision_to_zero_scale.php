<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        if (Schema::hasColumn('tourist_attractions', 'contract_rate_per_pax')) {
            DB::statement('ALTER TABLE tourist_attractions MODIFY contract_rate_per_pax DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('tourist_attractions', 'markup')) {
            DB::statement('ALTER TABLE tourist_attractions MODIFY markup DECIMAL(15,0) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('tourist_attractions', 'publish_rate_per_pax')) {
            DB::statement('ALTER TABLE tourist_attractions MODIFY publish_rate_per_pax DECIMAL(15,0) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        if (Schema::hasColumn('tourist_attractions', 'contract_rate_per_pax')) {
            DB::statement('ALTER TABLE tourist_attractions MODIFY contract_rate_per_pax DECIMAL(12,2) NULL');
        }
        if (Schema::hasColumn('tourist_attractions', 'markup')) {
            DB::statement('ALTER TABLE tourist_attractions MODIFY markup DECIMAL(12,2) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('tourist_attractions', 'publish_rate_per_pax')) {
            DB::statement('ALTER TABLE tourist_attractions MODIFY publish_rate_per_pax DECIMAL(12,2) NULL');
        }
    }
};

