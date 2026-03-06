<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('destination_id')->nullable()->after('province')->constrained('destinations')->nullOnDelete();
        });
        Schema::table('accommodations', function (Blueprint $table) {
            $table->foreignId('destination_id')->nullable()->after('province')->constrained('destinations')->nullOnDelete();
        });
        Schema::table('tourist_attractions', function (Blueprint $table) {
            $table->foreignId('destination_id')->nullable()->after('province')->constrained('destinations')->nullOnDelete();
        });
        Schema::table('airports', function (Blueprint $table) {
            $table->foreignId('destination_id')->nullable()->after('province')->constrained('destinations')->nullOnDelete();
        });
        Schema::table('transports', function (Blueprint $table) {
            $table->foreignId('destination_id')->nullable()->after('province')->constrained('destinations')->nullOnDelete();
        });

        $resolveDestinationId = function (?string $city, ?string $province): ?int {
            $city = trim((string) $city);
            $province = trim((string) $province);
            $name = $province !== '' ? $province : $city;
            if ($name === '') {
                return null;
            }

            $slugBase = Str::slug($name);
            $slug = $slugBase !== '' ? $slugBase : 'destination';

            $existing = DB::table('destinations')->where('slug', $slug)->first();
            if ($existing) {
                return (int) $existing->id;
            }

            $candidateCode = 'DST-' . strtoupper(substr(md5($slug), 0, 8));
            $code = $candidateCode;
            $counter = 1;
            while (DB::table('destinations')->where('code', $code)->exists()) {
                $code = $candidateCode . '-' . $counter;
                $counter++;
            }

            $id = DB::table('destinations')->insertGetId([
                'code' => $code,
                'name' => $name,
                'slug' => $slug,
                'location' => $name,
                'city' => null,
                'province' => $province !== '' ? $province : ($city !== '' ? $city : null),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) $id;
        };

        $syncByCityProvince = function (string $tableName) use ($resolveDestinationId): void {
            DB::table($tableName)
                ->select(['id', 'city', 'province'])
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($tableName, $resolveDestinationId) {
                    foreach ($rows as $row) {
                        $destinationId = $resolveDestinationId($row->city ?? null, $row->province ?? null);
                        if (! $destinationId) {
                            continue;
                        }
                        DB::table($tableName)
                            ->where('id', $row->id)
                            ->update([
                                'destination_id' => $destinationId,
                                'updated_at' => now(),
                            ]);
                    }
                });
        };

        $syncByCityProvince('vendors');
        $syncByCityProvince('accommodations');
        $syncByCityProvince('tourist_attractions');
        $syncByCityProvince('airports');
        $syncByCityProvince('transports');
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
        });
        Schema::table('airports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
        });
        Schema::table('tourist_attractions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
        });
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
        });
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('destination_id');
        });
    }
};
