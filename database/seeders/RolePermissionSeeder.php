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
            'Sales Manager' => [
                'module.sales_dashboard.access',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.bookings.access',
                'module.sales_target.access',
                'module.vendor_management.access',
                'module.services.access',
                'module.services_accommodations.access',
                'module.services_transports.access',
                'module.services_guides.access',
                'module.services_attractions.access',
                'module.services_travel_activities.access',
                'module.promotions.access',
            ],
            'Sales Agent' => [
                'module.sales_dashboard.access',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.vendor_management.access',
                'module.services.access',
                'module.services_accommodations.access',
                'module.services_transports.access',
                'module.services_guides.access',
                'module.services_attractions.access',
                'module.services_travel_activities.access',
            ],
            'Director' => [
                'module.director_dashboard.access',
                'module.sales_dashboard.access',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.bookings.access',
                'module.sales_target.access',
                'module.finance_dashboard.access',
                'module.promotions.access',
            ],
            'Finance' => [
                'module.finance_dashboard.access',
                'module.bookings.access',
            ],
            'Operations' => [
                'module.operations_dashboard.access',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.services.access',
                'module.services_accommodations.access',
                'module.services_transports.access',
                'module.services_guides.access',
                'module.services_attractions.access',
                'module.services_travel_activities.access',
            ],
        ];

        foreach ($defaults as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            if ($roleName === 'Admin') {
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
