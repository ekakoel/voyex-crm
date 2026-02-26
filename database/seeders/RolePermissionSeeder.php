<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $modules = Module::query()->pluck('name', 'key')->all();

        foreach (array_keys($modules) as $key) {
            Permission::firstOrCreate([
                'name' => "module.{$key}.access",
                'guard_name' => 'web',
            ]);
        }

        $allPermissions = Permission::query()->pluck('name')->all();

        $defaults = [
            'Admin' => $allPermissions,
            'Super Admin' => $allPermissions,
            'Sales Manager' => [
                'dashboard.sales.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.activities.access',
            ],
            'Sales Agent' => [
                'dashboard.sales.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.vendor_management.access',
                'module.activities.access',
            ],
            'Director' => [
                'dashboard.director.view',
                'dashboard.sales.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.bookings.access',
                'module.invoices.access',
                'dashboard.finance.view',
            ],
            'Finance' => [
                'dashboard.finance.view',
                'module.bookings.access',
                'module.invoices.access',
            ],
            'Operations' => [
                'dashboard.operations.view',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.activities.access',
            ],
        ];

        foreach ($defaults as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            if ($roleName === 'Admin' || $roleName === 'Super Admin') {
                $role->syncPermissions($permissionNames);
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
