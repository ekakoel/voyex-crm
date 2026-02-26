<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TouristAttractionSeeder;
use Database\Seeders\InquirySeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VendorActivitySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ModuleSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(InquirySeeder::class);
        $this->call(TouristAttractionSeeder::class);
        $this->call(VendorActivitySeeder::class);
    }
}
