<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModuleService
{
    private static function ensureIslandTransferModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        Module::query()->firstOrCreate(
            ['key' => 'island_transfers'],
            [
                'name' => 'Island Transfers',
                'description' => 'Manage inter-island transfer services and sea routes.',
                'is_enabled' => true,
            ]
        );

        $permissions = [
            'module.island_transfers.access',
            'module.island_transfers.create',
            'module.island_transfers.read',
            'module.island_transfers.update',
            'module.island_transfers.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $roles = ['Administrator', 'Super Admin', 'Manager', 'Marketing', 'Reservation', 'Editor'];
        $roleModels = Role::query()->whereIn('name', $roles)->get();
        foreach ($roleModels as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    private static function ensureHotelModule(): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $module = Module::query()->firstOrCreate(
            ['key' => 'hotels'],
            [
                'name' => 'Hotels',
                'description' => 'Manage hotel master data, rooms, and pricing.',
                'is_enabled' => true,
            ]
        );

        $permissions = [
            'module.hotels.access',
            'module.hotels.create',
            'module.hotels.read',
            'module.hotels.update',
            'module.hotels.delete',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $roles = ['Administrator', 'Super Admin', 'Manager', 'Marketing', 'Reservation', 'Editor'];
        $roleModels = Role::query()->whereIn('name', $roles)->get();
        foreach ($roleModels as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function isEnabled(string $key): bool
    {
        return self::isEnabledStatic($key);
    }

    public static function isEnabledStatic(string $key): bool
    {
        self::ensureHotelModule();
        self::ensureIslandTransferModule();
        if (! Schema::hasTable('modules')) {
            return (bool) config('modules.fail_open', false);
        }

        $module = Module::query()
            ->select('is_enabled')
            ->where('key', $key)
            ->first();

        if (! $module) {
            return (bool) config('modules.fail_open', false);
        }

        return (bool) $module->is_enabled;
    }

    public function listAll()
    {
        self::ensureHotelModule();
        self::ensureIslandTransferModule();
        if (! Schema::hasTable('modules')) {
            return collect();
        }

        return Module::query()->orderBy('name')->get();
    }
}
