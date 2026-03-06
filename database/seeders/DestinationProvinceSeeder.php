<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DestinationProvinceSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            ['province' => 'Aceh', 'city' => 'Banda Aceh'],
            ['province' => 'Sumatera Utara', 'city' => 'Medan'],
            ['province' => 'Sumatera Barat', 'city' => 'Padang'],
            ['province' => 'Riau', 'city' => 'Pekanbaru'],
            ['province' => 'Kepulauan Riau', 'city' => 'Tanjung Pinang'],
            ['province' => 'Jambi', 'city' => 'Jambi'],
            ['province' => 'Sumatera Selatan', 'city' => 'Palembang'],
            ['province' => 'Kepulauan Bangka Belitung', 'city' => 'Pangkal Pinang'],
            ['province' => 'Bengkulu', 'city' => 'Bengkulu'],
            ['province' => 'Lampung', 'city' => 'Bandar Lampung'],
            ['province' => 'DKI Jakarta', 'city' => 'Jakarta'],
            ['province' => 'Banten', 'city' => 'Serang'],
            ['province' => 'Jawa Barat', 'city' => 'Bandung'],
            ['province' => 'Jawa Tengah', 'city' => 'Semarang'],
            ['province' => 'DI Yogyakarta', 'city' => 'Yogyakarta'],
            ['province' => 'Jawa Timur', 'city' => 'Surabaya'],
            ['province' => 'Bali', 'city' => 'Denpasar'],
            ['province' => 'Nusa Tenggara Barat', 'city' => 'Mataram'],
            ['province' => 'Kalimantan Barat', 'city' => 'Pontianak'],
            ['province' => 'Kalimantan Tengah', 'city' => 'Palangka Raya'],
            ['province' => 'Kalimantan Selatan', 'city' => 'Banjarbaru'],
            ['province' => 'Kalimantan Timur', 'city' => 'Samarinda'],
            ['province' => 'Kalimantan Utara', 'city' => 'Tanjung Selor'],
            ['province' => 'Sulawesi Utara', 'city' => 'Manado'],
            ['province' => 'Gorontalo', 'city' => 'Gorontalo'],
            ['province' => 'Sulawesi Tengah', 'city' => 'Palu'],
            ['province' => 'Sulawesi Barat', 'city' => 'Mamuju'],
            ['province' => 'Sulawesi Selatan', 'city' => 'Makassar'],
            ['province' => 'Sulawesi Tenggara', 'city' => 'Kendari'],
            ['province' => 'Nusa Tenggara Timur', 'city' => 'Kupang'],
            ['province' => 'Maluku', 'city' => 'Ambon'],
            ['province' => 'Maluku Utara', 'city' => 'Sofifi'],
            ['province' => 'Papua', 'city' => 'Jayapura'],
            ['province' => 'Papua Barat', 'city' => 'Manokwari'],
            ['province' => 'Papua Selatan', 'city' => 'Merauke'],
            ['province' => 'Papua Tengah', 'city' => 'Nabire'],
            ['province' => 'Papua Pegunungan', 'city' => 'Wamena'],
            ['province' => 'Papua Barat Daya', 'city' => 'Sorong'],
        ];

        foreach ($destinations as $row) {
            $province = trim((string) $row['province']);
            $city = trim((string) $row['city']);
            $slug = Str::slug($province);

            Destination::query()->updateOrCreate(
                ['province' => $province],
                [
                    'code' => 'DST-' . strtoupper(substr(sha1($province), 0, 8)),
                    'name' => $province,
                    'slug' => $slug !== '' ? $slug : Str::slug('destination-' . $province),
                    'location' => $city . ', ' . $province,
                    'city' => $city,
                    'country' => 'Indonesia',
                    'timezone' => $this->resolveTimezone($province),
                    'is_active' => true,
                ]
            );
        }
    }

    private function resolveTimezone(string $province): string
    {
        $witaProvinces = [
            'Bali',
            'Nusa Tenggara Barat',
            'Nusa Tenggara Timur',
            'Kalimantan Selatan',
            'Kalimantan Timur',
            'Kalimantan Utara',
            'Sulawesi Utara',
            'Gorontalo',
            'Sulawesi Tengah',
            'Sulawesi Barat',
            'Sulawesi Selatan',
            'Sulawesi Tenggara',
        ];

        $witProvinces = [
            'Maluku',
            'Maluku Utara',
            'Papua',
            'Papua Barat',
            'Papua Selatan',
            'Papua Tengah',
            'Papua Pegunungan',
            'Papua Barat Daya',
        ];

        if (in_array($province, $witProvinces, true)) {
            return 'Asia/Jayapura';
        }

        if (in_array($province, $witaProvinces, true)) {
            return 'Asia/Makassar';
        }

        return 'Asia/Jakarta';
    }
}
