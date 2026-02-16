<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Sales Manager']);
        Role::firstOrCreate(['name' => 'Sales Agent']);
        Role::firstOrCreate(['name' => 'Director']);
        Role::firstOrCreate(['name' => 'Finance']);
        Role::firstOrCreate(['name' => 'Operations']);
    }
}
