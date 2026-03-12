<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('quotations', 'inquiry_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropForeign(['inquiry_id']);
            });

            DB::statement('ALTER TABLE quotations MODIFY inquiry_id BIGINT UNSIGNED NULL');

            Schema::table('quotations', function (Blueprint $table) {
                $table->foreign('inquiry_id')->references('id')->on('inquiries')->nullOnDelete();
            });

            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('quotations', 'inquiry_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            Schema::table('quotations', function (Blueprint $table) {
                $table->dropForeign(['inquiry_id']);
            });

            DB::statement('ALTER TABLE quotations MODIFY inquiry_id BIGINT UNSIGNED NOT NULL');

            Schema::table('quotations', function (Blueprint $table) {
                $table->foreign('inquiry_id')->references('id')->on('inquiries')->cascadeOnDelete();
            });

            return;
        }

        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_id')->nullable(false)->change();
        });
    }
};
