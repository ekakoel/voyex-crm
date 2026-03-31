<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        Schema::table('transports', function (Blueprint $table) {
            if (! Schema::hasColumn('transports', 'markup_type')) {
                $table->string('markup_type', 20)->default('fixed')->after('contract_rate');
            }
            if (! Schema::hasColumn('transports', 'markup')) {
                $table->decimal('markup', 15, 2)->default(0)->after('markup_type');
            }
        });

        if (Schema::hasColumn('transports', 'contract_rate')
            && Schema::hasColumn('transports', 'publish_rate')
            && Schema::hasColumn('transports', 'markup')
            && Schema::hasColumn('transports', 'markup_type')) {
            DB::statement("
                UPDATE transports
                SET
                    markup_type = 'fixed',
                    markup = GREATEST(COALESCE(publish_rate, 0) - COALESCE(contract_rate, 0), 0)
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        Schema::table('transports', function (Blueprint $table) {
            if (Schema::hasColumn('transports', 'markup')) {
                $table->dropColumn('markup');
            }
            if (Schema::hasColumn('transports', 'markup_type')) {
                $table->dropColumn('markup_type');
            }
        });
    }
};

