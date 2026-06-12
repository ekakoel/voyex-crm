<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'inquiry_id')) {
            return;
        }

        // Deterministic backfill for legacy rows:
        // 1) Primary source: inquiry_itinerary_references by itinerary_id.
        // 2) Fallback: itineraries.inquiry_id (legacy schema path).
        DB::statement(
            'UPDATE quotations q
             INNER JOIN inquiry_itinerary_references r ON r.itinerary_id = q.itinerary_id
             SET q.inquiry_id = r.inquiry_id
             WHERE q.inquiry_id IS NULL'
        );

        DB::statement(
            'UPDATE quotations q
             INNER JOIN itineraries i ON i.id = q.itinerary_id
             SET q.inquiry_id = i.inquiry_id
             WHERE q.inquiry_id IS NULL
               AND i.inquiry_id IS NOT NULL'
        );

        $remainingNulls = (int) DB::table('quotations')->whereNull('inquiry_id')->count();
        if ($remainingNulls > 0) {
            throw new RuntimeException(
                "Cannot enforce NOT NULL on quotations.inquiry_id. {$remainingNulls} quotation row(s) still have NULL inquiry_id. ".
                'Please map each quotation to a valid inquiry first, then re-run migration.'
            );
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

    public function down(): void
    {
        if (! Schema::hasTable('quotations') || ! Schema::hasColumn('quotations', 'inquiry_id')) {
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
};

