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
            $moduleKey = $module->key;
            $modulePermissions = [
                "module.{$moduleKey}.access",
                "module.{$moduleKey}.create",
                "module.{$moduleKey}.read",
                "module.{$moduleKey}.update",
                "module.{$moduleKey}.delete",
            ];

            foreach ($modulePermissions as $permissionName) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }
        }

        $dashboardPermissions = [
            'dashboard.administrator.view',
            'dashboard.manager.view',
            'dashboard.marketing.view',
            'dashboard.reservation.view',
            'dashboard.finance.view',
            'dashboard.director.view',
            'dashboard.editor.view',
            'company_settings.manage',
            'quotations.approve',
            'quotations.reject',
        ];

        foreach ($dashboardPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $permissions = Permission::query()->pluck('name')->all();
            $adminRole->syncPermissions($permissions);
        }
    }
}
