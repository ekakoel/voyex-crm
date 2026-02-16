<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $modules = Module::query()->select(['key', 'name'])->get();

        foreach ($modules as $module) {
            Permission::firstOrCreate([
                'name' => "module.{$module->key}.access",
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissions = Permission::query()->pluck('name')->all();
            $adminRole->syncPermissions($permissions);
        }
    }
}
