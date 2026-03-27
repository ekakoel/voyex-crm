<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            if (Schema::hasColumn('company_settings', 'usd_rate')) {
                $table->dropColumn('usd_rate');
            }
            if (Schema::hasColumn('company_settings', 'currency')) {
                $table->dropColumn('currency');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('company_settings', 'currency')) {
                $table->string('currency', 3)->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('company_settings', 'usd_rate')) {
                $table->decimal('usd_rate', 18, 6)->nullable()->after('currency');
            }
        });
    }
};
