<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        if (Schema::hasTable('transports_legacy')) {
            DB::statement("
                UPDATE transports tu
                INNER JOIN transports_legacy tl ON tl.id = tu.transport_id
                SET
                    tu.code = COALESCE(tu.code, tl.code),
                    tu.transport_type = COALESCE(tu.transport_type, tl.transport_type),
                    tu.provider_name = COALESCE(tu.provider_name, tl.provider_name),
                    tu.service_scope = COALESCE(tu.service_scope, tl.service_scope),
                    tu.location = COALESCE(tu.location, tl.location),
                    tu.city = COALESCE(tu.city, tl.city),
                    tu.province = COALESCE(tu.province, tl.province),
                    tu.country = COALESCE(tu.country, tl.country),
                    tu.timezone = COALESCE(tu.timezone, tl.timezone),
                    tu.address = COALESCE(tu.address, tl.address),
                    tu.google_maps_url = COALESCE(tu.google_maps_url, tl.google_maps_url),
                    tu.latitude = COALESCE(tu.latitude, tl.latitude),
                    tu.longitude = COALESCE(tu.longitude, tl.longitude),
                    tu.destination_id = COALESCE(tu.destination_id, tl.destination_id),
                    tu.vendor_id = COALESCE(tu.vendor_id, tl.vendor_id),
                    tu.contact_name = COALESCE(tu.contact_name, tl.contact_name),
                    tu.contact_phone = COALESCE(tu.contact_phone, tl.contact_phone),
                    tu.contact_email = COALESCE(tu.contact_email, tl.contact_email),
                    tu.website = COALESCE(tu.website, tl.website),
                    tu.description = COALESCE(tu.description, tl.description),
                    tu.inclusions = COALESCE(tu.inclusions, tl.inclusions),
                    tu.exclusions = COALESCE(tu.exclusions, tl.exclusions),
                    tu.cancellation_policy = COALESCE(tu.cancellation_policy, tl.cancellation_policy),
                    tu.gallery_images = COALESCE(tu.gallery_images, tl.gallery_images),
                    tu.notes = COALESCE(tu.notes, tl.notes),
                    tu.deleted_at = COALESCE(tu.deleted_at, tl.deleted_at)
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

        if (DB::table('transports')->whereNull('vendor_id')->exists()) {
            $fallbackVendorId = (int) (DB::table('vendors')->min('id') ?? 0);
            if ($fallbackVendorId > 0) {
                DB::table('transports')->whereNull('vendor_id')->update(['vendor_id' => $fallbackVendorId]);
            }
        }

        $legacyTransportFks = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'transports'
              AND REFERENCED_TABLE_NAME = 'transports_legacy'
        ");
        foreach ($legacyTransportFks as $fk) {
            $name = (string) ($fk->CONSTRAINT_NAME ?? '');
            if ($name === '') {
                continue;
            }
            DB::statement("ALTER TABLE transports DROP FOREIGN KEY `{$name}`");
        }

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign(['transport_id']);
            });
        } catch (\Throwable $e) {
            // ignore
        }

        DB::statement('UPDATE transports SET transport_id = id');
        DB::statement('ALTER TABLE transports MODIFY code VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE transports MODIFY vendor_id BIGINT UNSIGNED NOT NULL');

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropUnique('transports_code_unique');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->unique('code');
        });

        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign('fk_transports_destination');
            });
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropForeign('fk_transports_vendor');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->foreign('destination_id', 'fk_transports_destination')->references('id')->on('destinations')->nullOnDelete();
            $table->foreign('vendor_id', 'fk_transports_vendor')->references('id')->on('vendors')->restrictOnDelete();
        });

        if (Schema::hasColumn('itineraries', 'arrival_transport_id')) {
            try {
                Schema::table('itineraries', function (Blueprint $table) {
                    $table->dropForeign(['arrival_transport_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
            Schema::table('itineraries', function (Blueprint $table) {
                $table->foreign('arrival_transport_id')->references('id')->on('transports')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('itineraries', 'departure_transport_id')) {
            try {
                Schema::table('itineraries', function (Blueprint $table) {
                    $table->dropForeign(['departure_transport_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
            Schema::table('itineraries', function (Blueprint $table) {
                $table->foreign('departure_transport_id')->references('id')->on('transports')->nullOnDelete();
            });
        }

        if (Schema::hasTable('transports_legacy')) {
            try {
                Schema::table('transports_legacy', function (Blueprint $table) {
                    $table->dropForeign(['destination_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                Schema::table('transports_legacy', function (Blueprint $table) {
                    $table->dropForeign(['vendor_id']);
                });
            } catch (\Throwable $e) {
                // ignore
            }

            Schema::drop('transports_legacy');
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Rollback is not supported for transport promotion finalizer migration.');
    }
};
