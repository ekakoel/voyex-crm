<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            if (! Schema::hasColumn('activities', 'adult_markup_type')) {
                $table->string('adult_markup_type', 20)->default('fixed')->after('adult_contract_rate');
            }
            if (! Schema::hasColumn('activities', 'adult_markup')) {
                $table->decimal('adult_markup', 15, 2)->default(0)->after('adult_markup_type');
            }
            if (! Schema::hasColumn('activities', 'child_markup_type')) {
                $table->string('child_markup_type', 20)->default('fixed')->after('child_contract_rate');
            }
            if (! Schema::hasColumn('activities', 'child_markup')) {
                $table->decimal('child_markup', 15, 2)->default(0)->after('child_markup_type');
            }
        });

        if (Schema::hasColumn('activities', 'adult_contract_rate')
            && Schema::hasColumn('activities', 'adult_publish_rate')
            && Schema::hasColumn('activities', 'adult_markup')
            && Schema::hasColumn('activities', 'adult_markup_type')) {
            DB::statement("
                UPDATE activities
                SET
                    adult_markup_type = 'fixed',
                    adult_markup = GREATEST(COALESCE(adult_publish_rate, 0) - COALESCE(adult_contract_rate, 0), 0)
            ");
        }

        if (Schema::hasColumn('activities', 'child_contract_rate')
            && Schema::hasColumn('activities', 'child_publish_rate')
            && Schema::hasColumn('activities', 'child_markup')
            && Schema::hasColumn('activities', 'child_markup_type')) {
            DB::statement("
                UPDATE activities
                SET
                    child_markup_type = 'fixed',
                    child_markup = GREATEST(COALESCE(child_publish_rate, 0) - COALESCE(child_contract_rate, 0), 0)
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            if (Schema::hasColumn('activities', 'adult_markup')) {
                $table->dropColumn('adult_markup');
            }
            if (Schema::hasColumn('activities', 'adult_markup_type')) {
                $table->dropColumn('adult_markup_type');
            }
            if (Schema::hasColumn('activities', 'child_markup')) {
                $table->dropColumn('child_markup');
            }
            if (Schema::hasColumn('activities', 'child_markup_type')) {
                $table->dropColumn('child_markup_type');
            }
        });
    }
};

