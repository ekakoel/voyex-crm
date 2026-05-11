<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            try {
                $table->dropForeign(['itinerary_id']);
            } catch (\Throwable $e) {
                // Foreign key may already be missing.
            }

            try {
                $table->dropUnique('quotations_itinerary_id_unique');
            } catch (\Throwable $e) {
                // Index might already be removed in some environments.
            }

            try {
                $table->index('itinerary_id', 'quotations_itinerary_id_index');
            } catch (\Throwable $e) {
                // Index might already exist.
            }

            try {
                $table->foreign('itinerary_id')
                    ->references('id')
                    ->on('itineraries')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Foreign key might already exist.
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            try {
                $table->dropForeign(['itinerary_id']);
            } catch (\Throwable $e) {
                // Foreign key might not exist.
            }

            try {
                $table->dropIndex('quotations_itinerary_id_index');
            } catch (\Throwable $e) {
                // Index might not exist.
            }

            try {
                $table->unique('itinerary_id', 'quotations_itinerary_id_unique');
            } catch (\Throwable $e) {
                // Re-adding unique can fail when duplicated data already exists.
            }

            try {
                $table->foreign('itinerary_id')
                    ->references('id')
                    ->on('itineraries')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Foreign key might already exist.
            }
        });
    }
};
