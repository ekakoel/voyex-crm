<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transports', 'vendor_id')) {
            return;
        }

        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('This migration currently supports MySQL/MariaDB only.');
        }

        // Backfill legacy rows where vendor is still null but provider_name is available.
        DB::statement("
            UPDATE transports t
            INNER JOIN (
                SELECT LOWER(TRIM(name)) AS normalized_name, MIN(id) AS vendor_id
                FROM vendors
                GROUP BY LOWER(TRIM(name))
            ) v ON v.normalized_name = LOWER(TRIM(t.provider_name))
            SET t.vendor_id = v.vendor_id
            WHERE t.vendor_id IS NULL
              AND t.provider_name IS NOT NULL
              AND TRIM(t.provider_name) <> ''
        ");

        $legacyRows = DB::table('transports')
            ->select(
                'id',
                DB::raw('TRIM(provider_name) as provider_name'),
                'city',
                'province',
                'country',
                'contact_name',
                'contact_email',
                'contact_phone',
                'website',
                'address'
            )
            ->whereNull('vendor_id')
            ->whereNotNull('provider_name')
            ->whereRaw("TRIM(provider_name) <> ''")
            ->orderBy('id')
            ->get();

        foreach ($legacyRows as $row) {
            $vendorId = DB::table('vendors')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower((string) $row->provider_name)])
                ->value('id');

            if (! $vendorId) {
                $vendorId = DB::table('vendors')->insertGetId([
                    'name' => $row->provider_name,
                    'location' => $this->buildLocation((string) ($row->city ?? ''), (string) ($row->province ?? ''), (string) ($row->country ?? '')),
                    'city' => $this->nullableTrim($row->city),
                    'province' => $this->nullableTrim($row->province),
                    'country' => $this->nullableTrim($row->country),
                    'contact_name' => $this->nullableTrim($row->contact_name),
                    'contact_email' => $this->nullableTrim($row->contact_email),
                    'contact_phone' => $this->nullableTrim($row->contact_phone),
                    'website' => $this->nullableTrim($row->website),
                    'address' => $this->nullableTrim($row->address),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('transports')
                ->where('id', $row->id)
                ->update([
                    'vendor_id' => $vendorId,
                    'provider_name' => $row->provider_name,
                ]);
        }

        $remainingNulls = DB::table('transports')->whereNull('vendor_id')->count();
        if ($remainingNulls > 0) {
            throw new RuntimeException(
                "Cannot enforce NOT NULL on transports.vendor_id: {$remainingNulls} row(s) still have null vendor_id."
            );
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });

        DB::statement('ALTER TABLE transports MODIFY vendor_id BIGINT UNSIGNED NOT NULL');

        Schema::table('transports', function (Blueprint $table) {
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transports', 'vendor_id')) {
            return;
        }

        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('This migration currently supports MySQL/MariaDB only.');
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });

        DB::statement('ALTER TABLE transports MODIFY vendor_id BIGINT UNSIGNED NULL');

        Schema::table('transports', function (Blueprint $table) {
            $table->foreign('vendor_id')
                ->references('id')
                ->on('vendors')
                ->nullOnDelete();
        });
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    private function buildLocation(string $city, string $province, string $country): ?string
    {
        $parts = array_values(array_filter([
            trim($city),
            trim($province),
            trim($country),
        ], fn (string $part) => $part !== ''));

        return $parts !== [] ? implode(', ', $parts) : null;
    }
};
