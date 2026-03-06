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
            'Admin User' => [
                'dashboard.admin.view',
                'company_settings.manage',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.itineraries.access',
                'module.quotations.access',
                'quotations.approve',
                'quotations.reject',
                'module.bookings.access',
                'module.invoices.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.accommodations.access',
                'module.airports.access',
                'module.transports.access',
                'module.tourist_attractions.access',
                'module.user_manager.access',
                'module.quotation_templates.access',
            ],
            'Sales Manager' => [
                'dashboard.sales.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'quotations.approve',
                'quotations.reject',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.airports.access',
            ],
            'Sales Agent' => [
                'dashboard.sales.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.airports.access',
            ],
            'Director' => [
                'dashboard.director.view',
                'dashboard.sales.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'quotations.approve',
                'quotations.reject',
                'module.bookings.access',
                'module.invoices.access',
                'dashboard.finance.view',
                'company_settings.manage',
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
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.airports.access',
            ],
        ];

        foreach ($defaults as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $validPermissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('name')
                ->all();

            $role->syncPermissions($validPermissions);
        }
    }
}
