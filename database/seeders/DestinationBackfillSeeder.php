<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DestinationBackfillSeeder extends Seeder
{
    public function run(): void
    {
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

            return (int) DB::table('destinations')->insertGetId([
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
        };

        $syncByCityProvince = function (string $tableName) use ($resolveDestinationId): void {
            DB::table($tableName)
                ->select(['id', 'city', 'province', 'destination_id'])
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
        $syncByCityProvince('hotels');
        $syncByCityProvince('tourist_attractions');
        $syncByCityProvince('airports');
        $syncByCityProvince('transports');
    }
}
