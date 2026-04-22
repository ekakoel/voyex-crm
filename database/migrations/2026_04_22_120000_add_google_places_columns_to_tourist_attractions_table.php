<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (! Schema::hasColumn('tourist_attractions', 'google_place_id')) {
                $table->string('google_place_id')->nullable()->after('destination_id');
            }
            if (! Schema::hasColumn('tourist_attractions', 'source')) {
                $table->string('source', 32)->default('manual')->after('google_place_id');
            }
            if (! Schema::hasColumn('tourist_attractions', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable()->after('source');
            }
        });

        Schema::table('tourist_attractions', function (Blueprint $table) {
            try {
                $table->unique('google_place_id', 'tourist_attractions_google_place_id_unique');
            } catch (\Throwable) {
                // Ignore when index already exists.
            }

            try {
                $table->index(['source', 'last_synced_at'], 'tourist_attractions_source_last_synced_idx');
            } catch (\Throwable) {
                // Ignore when index already exists.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tourist_attractions')) {
            return;
        }

        Schema::table('tourist_attractions', function (Blueprint $table) {
            try {
                $table->dropIndex('tourist_attractions_source_last_synced_idx');
            } catch (\Throwable) {
                // Ignore when index is missing.
            }

            try {
                $table->dropUnique('tourist_attractions_google_place_id_unique');
            } catch (\Throwable) {
                // Ignore when index is missing.
            }
        });

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (Schema::hasColumn('tourist_attractions', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
            if (Schema::hasColumn('tourist_attractions', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('tourist_attractions', 'google_place_id')) {
                $table->dropColumn('google_place_id');
            }
        });
    }
};
