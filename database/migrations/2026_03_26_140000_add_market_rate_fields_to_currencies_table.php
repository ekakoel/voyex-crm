<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table) {
            if (! Schema::hasColumn('currencies', 'market_rate_to_idr')) {
                $table->decimal('market_rate_to_idr', 18, 6)->nullable()->after('rate_to_idr');
            }
            if (! Schema::hasColumn('currencies', 'market_rate_synced_at')) {
                $table->timestamp('market_rate_synced_at')->nullable()->after('market_rate_to_idr');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        Schema::table('currencies', function (Blueprint $table) {
            if (Schema::hasColumn('currencies', 'market_rate_synced_at')) {
                $table->dropColumn('market_rate_synced_at');
            }
            if (Schema::hasColumn('currencies', 'market_rate_to_idr')) {
                $table->dropColumn('market_rate_to_idr');
            }
        });
    }
};
