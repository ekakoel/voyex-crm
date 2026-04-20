<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('island_transfers')) {
            return;
        }

        Schema::table('island_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('island_transfers', 'contract_rate')) {
                $table->decimal('contract_rate', 15, 0)->nullable()->after('duration_minutes');
            }
            if (! Schema::hasColumn('island_transfers', 'markup_type')) {
                $table->string('markup_type', 20)->default('fixed')->after('contract_rate');
            }
            if (! Schema::hasColumn('island_transfers', 'markup')) {
                $table->decimal('markup', 15, 0)->default(0)->after('markup_type');
            }
            if (! Schema::hasColumn('island_transfers', 'publish_rate')) {
                $table->decimal('publish_rate', 15, 0)->nullable()->after('markup');
            }
        });

        if (Schema::hasColumn('island_transfers', 'contract_rate')
            && Schema::hasColumn('island_transfers', 'markup_type')
            && Schema::hasColumn('island_transfers', 'markup')
            && Schema::hasColumn('island_transfers', 'publish_rate')) {
            DB::statement("
                UPDATE island_transfers
                SET
                    markup_type = COALESCE(NULLIF(markup_type, ''), 'fixed'),
                    markup = COALESCE(markup, 0),
                    publish_rate = CASE
                        WHEN publish_rate IS NOT NULL THEN publish_rate
                        WHEN contract_rate IS NULL THEN 0
                        ELSE COALESCE(contract_rate, 0) + COALESCE(markup, 0)
                    END
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('island_transfers')) {
            return;
        }

        Schema::table('island_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('island_transfers', 'publish_rate')) {
                $table->dropColumn('publish_rate');
            }
            if (Schema::hasColumn('island_transfers', 'markup')) {
                $table->dropColumn('markup');
            }
            if (Schema::hasColumn('island_transfers', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
            if (Schema::hasColumn('island_transfers', 'contract_rate')) {
                $table->dropColumn('contract_rate');
            }
        });
    }
};

