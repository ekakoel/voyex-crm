<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'country')) {
                $table->string('country')->nullable()->after('province');
            }
            if (! Schema::hasColumn('vendors', 'timezone')) {
                $table->string('timezone')->nullable()->after('country');
            }
        });

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (! Schema::hasColumn('tourist_attractions', 'country')) {
                $table->string('country')->nullable()->after('province');
            }
            if (! Schema::hasColumn('tourist_attractions', 'timezone')) {
                $table->string('timezone')->nullable()->after('country');
            }
            if (! Schema::hasColumn('tourist_attractions', 'address')) {
                $table->string('address')->nullable()->after('timezone');
            }
        });

        Schema::table('airports', function (Blueprint $table) {
            if (! Schema::hasColumn('airports', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('destination_id');
            }
        });

        Schema::table('transports', function (Blueprint $table) {
            if (! Schema::hasColumn('transports', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('destination_id');
            }
            if (! Schema::hasColumn('transports', 'country')) {
                $table->string('country')->nullable()->after('province');
            }
            if (! Schema::hasColumn('transports', 'timezone')) {
                $table->string('timezone')->nullable()->after('country');
            }
            if (! Schema::hasColumn('transports', 'address')) {
                $table->string('address')->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('transports', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('address');
            }
            if (! Schema::hasColumn('transports', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });

        Schema::table('destinations', function (Blueprint $table) {
            if (! Schema::hasColumn('destinations', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('destinations', 'address')) {
                $table->string('address')->nullable()->after('timezone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('vendors', 'country')) {
                $table->dropColumn('country');
            }
        });

        Schema::table('tourist_attractions', function (Blueprint $table) {
            if (Schema::hasColumn('tourist_attractions', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('tourist_attractions', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('tourist_attractions', 'country')) {
                $table->dropColumn('country');
            }
        });

        Schema::table('airports', function (Blueprint $table) {
            if (Schema::hasColumn('airports', 'google_maps_url')) {
                $table->dropColumn('google_maps_url');
            }
        });

        Schema::table('transports', function (Blueprint $table) {
            if (Schema::hasColumn('transports', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('transports', 'latitude')) {
                $table->dropColumn('latitude');
            }
            if (Schema::hasColumn('transports', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('transports', 'timezone')) {
                $table->dropColumn('timezone');
            }
            if (Schema::hasColumn('transports', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('transports', 'google_maps_url')) {
                $table->dropColumn('google_maps_url');
            }
        });

        Schema::table('destinations', function (Blueprint $table) {
            if (Schema::hasColumn('destinations', 'address')) {
                $table->dropColumn('address');
            }
            if (Schema::hasColumn('destinations', 'google_maps_url')) {
                $table->dropColumn('google_maps_url');
            }
        });
    }
};
