<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Global (non-module) permissions managed from one place for easier deploys.
     *
     * @var array<int, string>
     */
    private const GLOBAL_PERMISSIONS = [
        'dashboard.superadmin.view',
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
        'quotations.validate',
        'quotations.set_pending',
        'quotations.set_final',
        'quotations.global_discount',
        'services.map.view',
        'superadmin.access_matrix.view',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        foreach (self::GLOBAL_PERMISSIONS as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
