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
            'Administrator' => $allPermissions,
            'Super Admin' => $allPermissions,
            'Manager' => [
                'dashboard.manager.view',
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
                'module.hotels.access',
            ],
            'Marketing' => [
                'dashboard.marketing.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.airports.access',
                'module.hotels.access',
            ],
            'Director' => [
                'dashboard.director.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.itineraries.access',
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
            'Reservation' => [
                'dashboard.reservation.view',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.airports.access',
                'module.hotels.access',
            ],
            'Editor' => [
                'dashboard.editor.view',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.food_beverages.access',
                'module.hotels.access',
                'module.airports.access',
                'module.transports.access',
                'module.tourist_attractions.access',
            ],
        ];

        $moduleKeys = array_keys($modules);

        foreach ($defaults as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            $permissionNames = $this->expandModulePermissions($permissionNames, $moduleKeys);
            $permissionNames = array_values(array_unique($permissionNames));

            $validPermissions = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('name')
                ->all();

            $role->syncPermissions($validPermissions);
        }
    }

    private function expandModulePermissions(array $permissions, array $moduleKeys): array
    {
        $expanded = $permissions;
        $permissionSet = array_fill_keys($permissions, true);

        foreach ($moduleKeys as $moduleKey) {
            $accessPermission = "module.{$moduleKey}.access";
            if (! isset($permissionSet[$accessPermission])) {
                continue;
            }

            foreach (['create', 'read', 'update', 'delete'] as $action) {
                $permissionName = "module.{$moduleKey}.{$action}";
                if (! isset($permissionSet[$permissionName])) {
                    $expanded[] = $permissionName;
                    $permissionSet[$permissionName] = true;
                }
            }
        }

        return $expanded;
    }
}
