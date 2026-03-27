<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('agent_rate_child', 15, 2)->nullable()->after('agent_price');
            $table->decimal('contract_rate_agent_adult', 15, 2)->nullable()->after('contract_price');
            $table->decimal('contract_rate_agent_child', 15, 2)->nullable()->after('contract_rate_agent_adult');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'agent_rate_child',
                'contract_rate_agent_adult',
                'contract_rate_agent_child',
            ]);
        });
    }
};

