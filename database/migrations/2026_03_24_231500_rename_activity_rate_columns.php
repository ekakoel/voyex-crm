<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE activities CHANGE agent_price adult_publish_rate DECIMAL(12,2) NULL');
        DB::statement('ALTER TABLE activities CHANGE agent_rate_child child_publish_rate DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE activities CHANGE contract_rate_agent_adult adult_contract_rate DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE activities CHANGE contract_rate_agent_child child_contract_rate DECIMAL(15,2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE activities CHANGE adult_publish_rate agent_price DECIMAL(12,2) NULL');
        DB::statement('ALTER TABLE activities CHANGE child_publish_rate agent_rate_child DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE activities CHANGE adult_contract_rate contract_rate_agent_adult DECIMAL(15,2) NULL');
        DB::statement('ALTER TABLE activities CHANGE child_contract_rate contract_rate_agent_child DECIMAL(15,2) NULL');
    }
};
