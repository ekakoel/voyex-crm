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

        $adminPermissions = array_values(array_filter(
            $allPermissions,
            fn (string $permission): bool => $permission !== 'dashboard.superadmin.view'
        ));

        $defaults = [
            'Administrator' => $adminPermissions,
            'Super Admin' => $allPermissions,
            'Manager' => [
                'dashboard.manager.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'quotations.approve',
                'quotations.reject',
                'quotations.validate',
                'quotations.set_final',
                'quotations.global_discount',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.island_transfers.access',
                'module.food_beverages.access',
                'module.airports.access',
                'module.hotels.access',
            ],
            'Marketing' => [
                'dashboard.marketing.view',
                'module.customer_management.access',
                'module.inquiries.access',
                'module.quotations.access',
                'quotations.set_final',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.island_transfers.access',
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
                'quotations.validate',
                'quotations.set_pending',
                'quotations.set_final',
                'quotations.global_discount',
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
                'module.quotations.access',
                'quotations.approve',
                'quotations.validate',
                'quotations.set_final',
                'module.bookings.access',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.island_transfers.access',
                'module.food_beverages.access',
                'module.airports.access',
                'module.hotels.access',
            ],
            'Editor' => [
                'dashboard.editor.view',
                'module.itineraries.access',
                'itineraries.manual_item_queue.view',
                'itineraries.manual_item_queue.validate',
                'module.vendor_management.access',
                'module.destinations.access',
                'module.activities.access',
                'module.island_transfers.access',
                'module.food_beverages.access',
                'module.hotels.access',
                'module.airports.access',
                'module.transports.access',
                'module.tourist_attractions.access',
            ],
        ];

        // Keep access-matrix privilege explicit (not bundled into generic admin defaults by exclusion rule only).
        // If needed, assign this permission per-role from Role & Permissions screen.
        $adminPermissions = array_values(array_filter(
            $adminPermissions,
            fn (string $permission): bool => $permission !== 'superadmin.access_matrix.view'
        ));
        $defaults['Administrator'] = $adminPermissions;

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
