<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('itineraries')) {
            return;
        }

        Schema::table('itineraries', function (Blueprint $table): void {
            if (! Schema::hasColumn('itineraries', 'revision_of_id')) {
                $table->unsignedBigInteger('revision_of_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('itineraries', 'revision_number')) {
                $table->unsignedInteger('revision_number')->default(1)->after('revision_of_id');
            }
            if (! Schema::hasColumn('itineraries', 'revision_reason')) {
                $table->string('revision_reason', 255)->nullable()->after('revision_number');
            }
            if (! Schema::hasColumn('itineraries', 'revised_from_quotation_id')) {
                $table->unsignedBigInteger('revised_from_quotation_id')->nullable()->after('revision_reason');
            }
        });

        Schema::table('itineraries', function (Blueprint $table): void {
            try {
                $table->foreign('revision_of_id', 'itineraries_revision_of_id_foreign')
                    ->references('id')
                    ->on('itineraries')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore when FK already exists.
            }

            try {
                $table->foreign('revised_from_quotation_id', 'itineraries_revised_from_quotation_id_foreign')
                    ->references('id')
                    ->on('quotations')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Ignore when FK already exists.
            }

            try {
                $table->index(['revision_of_id', 'revision_number'], 'itineraries_revision_chain_index');
            } catch (\Throwable $e) {
                // Ignore when index already exists.
            }
            try {
                $table->index('revised_from_quotation_id', 'itineraries_revised_from_quotation_id_index');
            } catch (\Throwable $e) {
                // Ignore when index already exists.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('itineraries')) {
            return;
        }

        Schema::table('itineraries', function (Blueprint $table): void {
            try {
                $table->dropForeign('itineraries_revision_of_id_foreign');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign('itineraries_revised_from_quotation_id_foreign');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('itineraries_revision_chain_index');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('itineraries_revised_from_quotation_id_index');
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('itineraries', 'revised_from_quotation_id')) {
                $table->dropColumn('revised_from_quotation_id');
            }
            if (Schema::hasColumn('itineraries', 'revision_reason')) {
                $table->dropColumn('revision_reason');
            }
            if (Schema::hasColumn('itineraries', 'revision_number')) {
                $table->dropColumn('revision_number');
            }
            if (Schema::hasColumn('itineraries', 'revision_of_id')) {
                $table->dropColumn('revision_of_id');
            }
        });
    }
};

