<?php

namespace Database\Seeders;

use App\Models\TouristAttraction;
use Illuminate\Database\Seeder;

class TouristAttractionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Tanah Lot',
                'ideal_visit_minutes' => 120,
                'location' => 'Tabanan, Bali',
                'city' => 'Tabanan',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.621215,115.086805',
                'latitude' => -8.6212150,
                'longitude' => 115.0868050,
                'description' => 'Pura di atas batu karang dengan panorama matahari terbenam.',
                'is_active' => true,
            ],
            [
                'name' => 'Uluwatu Temple',
                'ideal_visit_minutes' => 120,
                'location' => 'Badung, Bali',
                'city' => 'Badung',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.829112,115.084923',
                'latitude' => -8.8291120,
                'longitude' => 115.0849230,
                'description' => 'Pura di tebing dengan pemandangan laut selatan Bali.',
                'is_active' => true,
            ],
            [
                'name' => 'Tegallalang Rice Terrace',
                'ideal_visit_minutes' => 90,
                'location' => 'Gianyar, Bali',
                'city' => 'Gianyar',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.430052,115.279332',
                'latitude' => -8.4300520,
                'longitude' => 115.2793320,
                'description' => 'Hamparan sawah berundak populer di kawasan Ubud.',
                'is_active' => true,
            ],
            [
                'name' => 'Sacred Monkey Forest',
                'ideal_visit_minutes' => 90,
                'location' => 'Ubud, Bali',
                'city' => 'Gianyar',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.519267,115.258644',
                'latitude' => -8.5192670,
                'longitude' => 115.2586440,
                'description' => 'Hutan konservasi dengan ratusan kera ekor panjang.',
                'is_active' => true,
            ],
            [
                'name' => 'Besakih Temple',
                'ideal_visit_minutes' => 120,
                'location' => 'Karangasem, Bali',
                'city' => 'Karangasem',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.373113,115.450620',
                'latitude' => -8.3731130,
                'longitude' => 115.4506200,
                'description' => 'Kompleks pura terbesar dan paling sakral di Bali.',
                'is_active' => true,
            ],
            [
                'name' => 'Lempuyang Temple',
                'ideal_visit_minutes' => 120,
                'location' => 'Karangasem, Bali',
                'city' => 'Karangasem',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.369735,115.629887',
                'latitude' => -8.3697350,
                'longitude' => 115.6298870,
                'description' => 'Gerbang surga dengan latar Gunung Agung.',
                'is_active' => true,
            ],
            [
                'name' => 'Nusa Penida - Kelingking Beach',
                'ideal_visit_minutes' => 150,
                'location' => 'Nusa Penida, Bali',
                'city' => 'Klungkung',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.744506,115.472870',
                'latitude' => -8.7445060,
                'longitude' => 115.4728700,
                'description' => 'Tebing ikonik dengan bentuk menyerupai T-Rex.',
                'is_active' => true,
            ],
            [
                'name' => 'Ulun Danu Beratan',
                'ideal_visit_minutes' => 90,
                'location' => 'Bedugul, Bali',
                'city' => 'Tabanan',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.275309,115.166801',
                'latitude' => -8.2753090,
                'longitude' => 115.1668010,
                'description' => 'Pura terapung di tepi Danau Beratan.',
                'is_active' => true,
            ],
            [
                'name' => 'Sekumpul Waterfall',
                'ideal_visit_minutes' => 180,
                'location' => 'Buleleng, Bali',
                'city' => 'Buleleng',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.169090,115.181650',
                'latitude' => -8.1690900,
                'longitude' => 115.1816500,
                'description' => 'Air terjun bertingkat dengan jalur trekking menantang.',
                'is_active' => true,
            ],
            [
                'name' => 'Jatiluwih Rice Terraces',
                'ideal_visit_minutes' => 120,
                'location' => 'Tabanan, Bali',
                'city' => 'Tabanan',
                'province' => 'Bali',
                'google_maps_url' => 'https://maps.google.com/?q=-8.368263,115.131046',
                'latitude' => -8.3682630,
                'longitude' => 115.1310460,
                'description' => 'Lanskap sawah terasering warisan budaya dunia UNESCO.',
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            TouristAttraction::query()->updateOrCreate(
                ['name' => $item['name']],
                $item
            );
        }
    }
}
