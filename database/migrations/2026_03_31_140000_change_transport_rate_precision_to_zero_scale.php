<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        DB::statement('ALTER TABLE transports MODIFY contract_rate DECIMAL(15,0) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE transports MODIFY markup DECIMAL(15,0) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE transports MODIFY publish_rate DECIMAL(15,0) NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        DB::statement('ALTER TABLE transports MODIFY contract_rate DECIMAL(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE transports MODIFY markup DECIMAL(15,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE transports MODIFY publish_rate DECIMAL(15,2) NULL');
    }
};

