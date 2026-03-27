<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transport_units')) {
            return;
        }

        if (Schema::hasTable('transports') && ! Schema::hasTable('transports_legacy')) {
            Schema::rename('transports', 'transports_legacy');
        }

        if (! Schema::hasTable('transports')) {
            Schema::rename('transport_units', 'transports');
        }

        Schema::table('transports', function (Blueprint $table) {
            if (! Schema::hasColumn('transports', 'code')) {
                $table->string('code')->nullable()->after('id');
            }
            if (! Schema::hasColumn('transports', 'transport_type')) {
                $table->string('transport_type', 50)->nullable()->after('name');
            }
            if (! Schema::hasColumn('transports', 'provider_name')) {
                $table->string('provider_name')->nullable()->after('transport_type');
            }
            if (! Schema::hasColumn('transports', 'service_scope')) {
                $table->string('service_scope')->nullable()->after('provider_name');
            }
            if (! Schema::hasColumn('transports', 'location')) {
                $table->string('location')->nullable()->after('service_scope');
            }
            if (! Schema::hasColumn('transports', 'city')) {
                $table->string('city')->nullable()->after('location');
            }
            if (! Schema::hasColumn('transports', 'province')) {
                $table->string('province')->nullable()->after('city');
            }
            if (! Schema::hasColumn('transports', 'country')) {
                $table->string('country')->nullable()->after('province');
            }
            if (! Schema::hasColumn('transports', 'timezone')) {
                $table->string('timezone')->nullable()->after('country');
            }
            if (! Schema::hasColumn('transports', 'address')) {
                $table->text('address')->nullable()->after('timezone');
            }
            if (! Schema::hasColumn('transports', 'google_maps_url')) {
                $table->text('google_maps_url')->nullable()->after('address');
            }
            if (! Schema::hasColumn('transports', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('google_maps_url');
            }
            if (! Schema::hasColumn('transports', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('transports', 'destination_id')) {
                $table->unsignedBigInteger('destination_id')->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('transports', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('destination_id');
            }
            if (! Schema::hasColumn('transports', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('vendor_id');
            }
            if (! Schema::hasColumn('transports', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_name');
            }
            if (! Schema::hasColumn('transports', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_phone');
            }
            if (! Schema::hasColumn('transports', 'website')) {
                $table->string('website')->nullable()->after('contact_email');
            }
            if (! Schema::hasColumn('transports', 'description')) {
                $table->text('description')->nullable()->after('website');
            }
            if (! Schema::hasColumn('transports', 'inclusions')) {
                $table->text('inclusions')->nullable()->after('description');
            }
            if (! Schema::hasColumn('transports', 'exclusions')) {
                $table->text('exclusions')->nullable()->after('inclusions');
            }
            if (! Schema::hasColumn('transports', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable()->after('exclusions');
            }
            if (! Schema::hasColumn('transports', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('cancellation_policy');
            }
            if (! Schema::hasColumn('transports', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        if (Schema::hasTable('transports_legacy')) {
            DB::statement("
                UPDATE transports tu
                INNER JOIN transports_legacy tl ON tl.id = tu.transport_id
                SET
                    tu.code = tl.code,
                    tu.transport_type = tl.transport_type,
                    tu.provider_name = tl.provider_name,
                    tu.service_scope = tl.service_scope,
                    tu.location = tl.location,
                    tu.city = tl.city,
                    tu.province = tl.province,
                    tu.country = tl.country,
                    tu.timezone = tl.timezone,
                    tu.address = tl.address,
                    tu.google_maps_url = tl.google_maps_url,
                    tu.latitude = tl.latitude,
                    tu.longitude = tl.longitude,
                    tu.destination_id = tl.destination_id,
                    tu.vendor_id = tl.vendor_id,
                    tu.contact_name = tl.contact_name,
                    tu.contact_phone = tl.contact_phone,
                    tu.contact_email = tl.contact_email,
                    tu.website = tl.website,
                    tu.description = tl.description,
                    tu.inclusions = tl.inclusions,
                    tu.exclusions = tl.exclusions,
                    tu.cancellation_policy = tl.cancellation_policy,
                    tu.gallery_images = COALESCE(tu.images, tl.gallery_images),
                    tu.notes = COALESCE(tu.notes, tl.notes),
                    tu.is_active = CASE
                        WHEN tl.deleted_at IS NOT NULL THEN 0
                        ELSE tu.is_active
                    END,
                    tu.deleted_at = tl.deleted_at
            ");

            $mapping = DB::table('transports')
                ->selectRaw('transport_id as legacy_id, MIN(id) as new_id')
                ->whereNotNull('transport_id')
                ->groupBy('transport_id')
                ->pluck('new_id', 'legacy_id');

            foreach ($mapping as $legacyId => $newId) {
                DB::table('itineraries')
                    ->where('arrival_transport_id', (int) $legacyId)
                    ->update(['arrival_transport_id' => (int) $newId]);

                DB::table('itineraries')
                    ->where('departure_transport_id', (int) $legacyId)
                    ->update(['departure_transport_id' => (int) $newId]);
            }
        }

        $rows = DB::table('transports')->select('id', 'code')->orderBy('id')->get();
        $usedCodes = [];
        foreach ($rows as $row) {
            $rawCode = strtoupper(trim((string) ($row->code ?? '')));
            if ($rawCode === '' || isset($usedCodes[$rawCode])) {
                $rawCode = 'TRN-UNIT-' . (int) $row->id;
            }
            $usedCodes[$rawCode] = true;
            DB::table('transports')->where('id', (int) $row->id)->update(['code' => $rawCode]);
        }

        DB::table('transports')
            ->whereNull('vendor_id')
            ->update(['vendor_id' => DB::table('vendors')->min('id')]);

        $remainingNullVendor = DB::table('transports')->whereNull('vendor_id')->count();
        if ($remainingNullVendor > 0) {
            throw new RuntimeException('Cannot promote transport units: vendor_id still null on transports.');
        }

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign(['transport_id']);
            });
        } catch (\Throwable $e) {
            // ignore when FK name/schema differs across environments
        }

        if (Schema::hasTable('transports_legacy')) {
            try {
                Schema::table('transports_legacy', function (Blueprint $table) {
                    $table->dropForeign(['destination_id']);
                });
            } catch (\Throwable $e) {
                // ignore when FK name/schema differs across environments
            }

            try {
                Schema::table('transports_legacy', function (Blueprint $table) {
                    $table->dropForeign(['vendor_id']);
                });
            } catch (\Throwable $e) {
                // ignore when FK name/schema differs across environments
            }
        }

        DB::statement('UPDATE transports SET transport_id = id');
        DB::statement('ALTER TABLE transports MODIFY code VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE transports MODIFY vendor_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE transports MODIFY contract_rate DECIMAL(15,2) NULL');

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropUnique('transports_code_unique');
            });
        } catch (\Throwable $e) {
            // ignore if not present
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->unique('code');
        });

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign('fk_transports_destination');
            });
        } catch (\Throwable $e) {
            // ignore when FK is not present
        }

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign('fk_transports_vendor');
            });
        } catch (\Throwable $e) {
            // ignore when FK is not present
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->foreign('destination_id', 'fk_transports_destination')->references('id')->on('destinations')->nullOnDelete();
            $table->foreign('vendor_id', 'fk_transports_vendor')->references('id')->on('vendors')->restrictOnDelete();
        });

        if (Schema::hasTable('transports_legacy')) {
            Schema::drop('transports_legacy');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback is not supported for this transport schema promotion migration.');
    }
};
