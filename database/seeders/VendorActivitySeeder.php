<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorActivitySeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'name' => 'Bali Ocean Trips',
                'city' => 'Badung',
                'province' => 'Bali',
                'location' => 'Tanjung Benoa, Badung',
                'google_maps_url' => 'https://maps.google.com/?q=-8.748900,115.223000',
                'latitude' => -8.748900,
                'longitude' => 115.223000,
                'contact_name' => 'I Wayan Adi',
                'contact_email' => 'contact@balioceantrips.test',
                'contact_phone' => '+62 812-1111-1001',
                'address' => 'Jl. Pratama No. 88, Tanjung Benoa, Badung, Bali',
                'activities' => [
                    [
                        'name' => 'Tanjung Benoa Snorkeling',
                        'activity_type' => 'Water Activity',
                        'duration_minutes' => 120,
                        'agent_price' => 250000,
                        'contract_price' => 200000,
                    ],
                    [
                        'name' => 'Banana Boat Adventure',
                        'activity_type' => 'Water Activity',
                        'duration_minutes' => 60,
                        'agent_price' => 180000,
                        'contract_price' => 140000,
                    ],
                ],
            ],
            [
                'name' => 'Ubud Nature Experience',
                'city' => 'Gianyar',
                'province' => 'Bali',
                'location' => 'Ubud, Gianyar',
                'google_maps_url' => 'https://maps.google.com/?q=-8.506900,115.262500',
                'latitude' => -8.506900,
                'longitude' => 115.262500,
                'contact_name' => 'Ni Made Sari',
                'contact_email' => 'hello@ubudnature.test',
                'contact_phone' => '+62 812-1111-1002',
                'address' => 'Jl. Monkey Forest No. 12, Ubud, Gianyar, Bali',
                'activities' => [
                    [
                        'name' => 'Ubud ATV Ride',
                        'activity_type' => 'Adventure',
                        'duration_minutes' => 150,
                        'agent_price' => 450000,
                        'contract_price' => 390000,
                    ],
                    [
                        'name' => 'Campuhan Ridge Walk Guide',
                        'activity_type' => 'Nature Tour',
                        'duration_minutes' => 90,
                        'agent_price' => 150000,
                        'contract_price' => 120000,
                    ],
                ],
            ],
            [
                'name' => 'Kintamani Highland Tours',
                'city' => 'Bangli',
                'province' => 'Bali',
                'location' => 'Kintamani, Bangli',
                'google_maps_url' => 'https://maps.google.com/?q=-8.247900,115.375800',
                'latitude' => -8.247900,
                'longitude' => 115.375800,
                'contact_name' => 'Ketut Ariawan',
                'contact_email' => 'info@kintamanihighland.test',
                'contact_phone' => '+62 812-1111-1003',
                'address' => 'Jl. Raya Kintamani No. 5, Bangli, Bali',
                'activities' => [
                    [
                        'name' => 'Mount Batur Sunrise Jeep',
                        'activity_type' => 'Adventure',
                        'duration_minutes' => 240,
                        'agent_price' => 650000,
                        'contract_price' => 560000,
                    ],
                    [
                        'name' => 'Kintamani Coffee Plantation Visit',
                        'activity_type' => 'Cultural Tour',
                        'duration_minutes' => 90,
                        'agent_price' => 175000,
                        'contract_price' => 140000,
                    ],
                ],
            ],
            [
                'name' => 'Nusa Penida Fast Boats',
                'city' => 'Klungkung',
                'province' => 'Bali',
                'location' => 'Toyapakeh, Nusa Penida',
                'google_maps_url' => 'https://maps.google.com/?q=-8.727000,115.456900',
                'latitude' => -8.727000,
                'longitude' => 115.456900,
                'contact_name' => 'I Komang Putra',
                'contact_email' => 'ops@nusapenidaboats.test',
                'contact_phone' => '+62 812-1111-1004',
                'address' => 'Pelabuhan Banjar Nyuh, Nusa Penida, Klungkung, Bali',
                'activities' => [
                    [
                        'name' => 'West Nusa Penida One Day Tour',
                        'activity_type' => 'Island Tour',
                        'duration_minutes' => 480,
                        'agent_price' => 850000,
                        'contract_price' => 760000,
                    ],
                    [
                        'name' => 'Nusa Penida Snorkeling Trip',
                        'activity_type' => 'Water Activity',
                        'duration_minutes' => 180,
                        'agent_price' => 500000,
                        'contract_price' => 430000,
                    ],
                ],
            ],
            [
                'name' => 'North Bali Eco Travel',
                'city' => 'Buleleng',
                'province' => 'Bali',
                'location' => 'Lovina, Buleleng',
                'google_maps_url' => 'https://maps.google.com/?q=-8.159600,115.018300',
                'latitude' => -8.159600,
                'longitude' => 115.018300,
                'contact_name' => 'Made Pradnyana',
                'contact_email' => 'support@northbalieco.test',
                'contact_phone' => '+62 812-1111-1005',
                'address' => 'Jl. Raya Lovina No. 21, Buleleng, Bali',
                'activities' => [
                    [
                        'name' => 'Sekumpul Waterfall Trekking',
                        'activity_type' => 'Nature Tour',
                        'duration_minutes' => 210,
                        'agent_price' => 420000,
                        'contract_price' => 350000,
                    ],
                    [
                        'name' => 'Dolphin Watching Lovina',
                        'activity_type' => 'Marine Tour',
                        'duration_minutes' => 120,
                        'agent_price' => 300000,
                        'contract_price' => 250000,
                    ],
                ],
            ],
        ];

        foreach ($vendors as $vendorData) {
            $vendor = Vendor::query()->updateOrCreate(
                ['name' => $vendorData['name']],
                [
                    'location' => $vendorData['location'],
                    'google_maps_url' => $vendorData['google_maps_url'],
                    'latitude' => $vendorData['latitude'],
                    'longitude' => $vendorData['longitude'],
                    'city' => $vendorData['city'],
                    'province' => $vendorData['province'],
                    'contact_name' => $vendorData['contact_name'],
                    'contact_email' => $vendorData['contact_email'],
                    'contact_phone' => $vendorData['contact_phone'],
                    'address' => $vendorData['address'],
                    'is_active' => true,
                ]
            );

            foreach ($vendorData['activities'] as $activityData) {
                Activity::query()->updateOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'name' => $activityData['name'],
                    ],
                    [
                        'activity_type' => $activityData['activity_type'],
                        'duration_minutes' => $activityData['duration_minutes'],
                        'benefits' => 'Standard activity package',
                        'contract_price' => $activityData['contract_price'],
                        'agent_price' => $activityData['agent_price'],
                        'currency' => 'IDR',
                        'capacity_min' => 1,
                        'capacity_max' => 10,
                        'includes' => 'Guide and basic equipment',
                        'excludes' => 'Personal expenses',
                        'cancellation_policy' => 'Free cancellation up to 24 hours before activity',
                        'notes' => null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
