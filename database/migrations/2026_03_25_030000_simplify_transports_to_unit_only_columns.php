<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign(['transport_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign(['destination_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign('fk_transports_destination');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        $columns = [
            'transport_id',
            'provider_name',
            'service_scope',
            'location',
            'city',
            'province',
            'country',
            'timezone',
            'address',
            'google_maps_url',
            'latitude',
            'longitude',
            'destination_id',
            'contact_name',
            'contact_phone',
            'contact_email',
            'website',
            'gallery_images',
            'currency',
            'benefits',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('transports', $column)) {
                Schema::table('transports', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        // not supported intentionally: this migration is a hard simplification request
    }
};

