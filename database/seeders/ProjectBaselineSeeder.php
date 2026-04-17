<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProjectBaselineSeeder extends Seeder
{
    /**
     * Single entry point for baseline deploy seeding.
     */
    public function run(): void
    {
        $this->call(ModuleSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionBaselineSeeder::class);
        $this->call(FeatureAccessSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(InquirySeeder::class);
        $this->call(TouristAttractionSeeder::class);
        $this->call(VendorActivitySeeder::class);
        $this->call(TransportSeeder::class);
        $this->call(DestinationProvinceSeeder::class);
        $this->call(DestinationBackfillSeeder::class);
    }
}

