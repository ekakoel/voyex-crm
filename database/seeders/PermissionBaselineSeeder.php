<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionBaselineSeeder extends Seeder
{
    /**
     * Seed consolidated permission + role-permission baseline.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);
        $this->call(RolePermissionSeeder::class);
    }
}

