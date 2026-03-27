<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        $requiredColumns = [
            'contract_price',
            'adult_contract_rate',
            'child_contract_rate',
            'adult_publish_rate',
            'child_publish_rate',
        ];

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn('activities', $column)) {
                throw new RuntimeException("Smoke check failed: missing activities.{$column} column after rename migration.");
            }
        }

        // Backfill safety for legacy rows: keep adult contract rate aligned with existing contract_price.
        $missingAdultContractBefore = DB::table('activities')
            ->whereNotNull('contract_price')
            ->whereNull('adult_contract_rate')
            ->count();

        if ($missingAdultContractBefore > 0) {
            DB::table('activities')
                ->whereNotNull('contract_price')
                ->whereNull('adult_contract_rate')
                ->update([
                    'adult_contract_rate' => DB::raw('contract_price'),
                    'updated_at' => now(),
                ]);
        }

        $missingAdultContractAfter = DB::table('activities')
            ->whereNotNull('contract_price')
            ->whereNull('adult_contract_rate')
            ->count();

        if ($missingAdultContractAfter > 0) {
            throw new RuntimeException('Smoke check failed: some legacy activities rows still have NULL adult_contract_rate while contract_price is not NULL.');
        }

        // Read smoke-check: verify new columns can be selected successfully.
        DB::table('activities')
            ->select(['id', 'adult_publish_rate', 'child_publish_rate', 'adult_contract_rate', 'child_contract_rate'])
            ->limit(5)
            ->get();
    }

    public function down(): void
    {
        // Audit-only migration, no schema rollback needed.
    }
};
