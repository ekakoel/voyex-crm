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
        'itineraries.manual_item_queue.view',
        'itineraries.manual_item_queue.validate',
        'company_settings.manage',
        'quotations.approve',
        'quotations.reject',
        'quotations.validate',
        'quotations.set_pending',
        'quotations.set_final',
        'quotations.global_discount',
        'services.map.view',
        'superadmin.access_matrix.view',
        'payments.view',
        'payments.create',
        'payments.confirm',
        'payments.reject',
        'payments.cancel',
        'bookings.operation.view',
        'bookings.operation.prepare',
        'bookings.operation.start',
        'bookings.operation.complete',
        'bookings.operation.issue',
        'bookings.operation.dispatch',
        'bookings.operation.vendor_confirm',
        'bookings.operation.assign_driver',
        'bookings.operation.assign_guide',
        'bookings.operation.spk.view',
        'bookings.operation.spk.print',
        'booking_adjustments.view',
        'booking_adjustments.create',
        'booking_adjustments.update',
        'booking_adjustments.submit',
        'booking_adjustments.approve',
        'booking_adjustments.reject',
        'booking_adjustments.apply',
        'booking_adjustments.cancel',
        'booking_settlements.view',
        'booking_settlements.review',
        'booking_settlements.mark_settled',
        'booking_settlements.close_booking',
        'locations.resolve_google_map',
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
