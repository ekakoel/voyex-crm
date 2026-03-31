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

        if (Schema::hasColumn('activities', 'contract_price')) {
            DB::statement('ALTER TABLE activities MODIFY contract_price DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('activities', 'adult_contract_rate')) {
            DB::statement('ALTER TABLE activities MODIFY adult_contract_rate DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('activities', 'child_contract_rate')) {
            DB::statement('ALTER TABLE activities MODIFY child_contract_rate DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('activities', 'adult_markup')) {
            DB::statement('ALTER TABLE activities MODIFY adult_markup DECIMAL(15,0) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('activities', 'child_markup')) {
            DB::statement('ALTER TABLE activities MODIFY child_markup DECIMAL(15,0) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('activities', 'adult_publish_rate')) {
            DB::statement('ALTER TABLE activities MODIFY adult_publish_rate DECIMAL(15,0) NULL');
        }
        if (Schema::hasColumn('activities', 'child_publish_rate')) {
            DB::statement('ALTER TABLE activities MODIFY child_publish_rate DECIMAL(15,0) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        if (Schema::hasColumn('activities', 'contract_price')) {
            DB::statement('ALTER TABLE activities MODIFY contract_price DECIMAL(12,2) NULL');
        }
        if (Schema::hasColumn('activities', 'adult_contract_rate')) {
            DB::statement('ALTER TABLE activities MODIFY adult_contract_rate DECIMAL(15,2) NULL');
        }
        if (Schema::hasColumn('activities', 'child_contract_rate')) {
            DB::statement('ALTER TABLE activities MODIFY child_contract_rate DECIMAL(15,2) NULL');
        }
        if (Schema::hasColumn('activities', 'adult_markup')) {
            DB::statement('ALTER TABLE activities MODIFY adult_markup DECIMAL(15,2) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('activities', 'child_markup')) {
            DB::statement('ALTER TABLE activities MODIFY child_markup DECIMAL(15,2) NOT NULL DEFAULT 0');
        }
        if (Schema::hasColumn('activities', 'adult_publish_rate')) {
            DB::statement('ALTER TABLE activities MODIFY adult_publish_rate DECIMAL(12,2) NULL');
        }
        if (Schema::hasColumn('activities', 'child_publish_rate')) {
            DB::statement('ALTER TABLE activities MODIFY child_publish_rate DECIMAL(15,2) NULL');
        }
    }
};

