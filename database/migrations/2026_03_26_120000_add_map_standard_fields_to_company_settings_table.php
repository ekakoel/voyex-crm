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
            if (! Schema::hasColumn('company_settings', 'province')) {
                $table->string('province')->nullable()->after('city');
            }
            if (! Schema::hasColumn('company_settings', 'destination_id')) {
                $table->unsignedBigInteger('destination_id')->nullable()->after('country');
            }
            if (! Schema::hasColumn('company_settings', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('destination_id');
            }
            if (! Schema::hasColumn('company_settings', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('google_maps_url');
            }
            if (! Schema::hasColumn('company_settings', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_settings')) {
            return;
        }

        Schema::table('company_settings', function (Blueprint $table) {
            foreach (['longitude', 'latitude', 'google_maps_url', 'destination_id', 'province'] as $column) {
                if (Schema::hasColumn('company_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
