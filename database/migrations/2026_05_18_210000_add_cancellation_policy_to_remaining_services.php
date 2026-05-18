<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('food_beverages') && ! Schema::hasColumn('food_beverages', 'cancellation_policy')) {
            Schema::table('food_beverages', function (Blueprint $table): void {
                $table->text('cancellation_policy')->nullable()->after('menu_highlights');
            });
        }

        if (Schema::hasTable('island_transfers') && ! Schema::hasColumn('island_transfers', 'cancellation_policy')) {
            Schema::table('island_transfers', function (Blueprint $table): void {
                $table->text('cancellation_policy')->nullable()->after('capacity_max');
            });
        }

        if (Schema::hasTable('tourist_attractions') && ! Schema::hasColumn('tourist_attractions', 'cancellation_policy')) {
            Schema::table('tourist_attractions', function (Blueprint $table): void {
                $table->text('cancellation_policy')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('food_beverages') && Schema::hasColumn('food_beverages', 'cancellation_policy')) {
            Schema::table('food_beverages', function (Blueprint $table): void {
                $table->dropColumn('cancellation_policy');
            });
        }

        if (Schema::hasTable('island_transfers') && Schema::hasColumn('island_transfers', 'cancellation_policy')) {
            Schema::table('island_transfers', function (Blueprint $table): void {
                $table->dropColumn('cancellation_policy');
            });
        }

        if (Schema::hasTable('tourist_attractions') && Schema::hasColumn('tourist_attractions', 'cancellation_policy')) {
            Schema::table('tourist_attractions', function (Blueprint $table): void {
                $table->dropColumn('cancellation_policy');
            });
        }
    }
};
