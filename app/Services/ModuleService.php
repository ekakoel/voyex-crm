<?php

namespace App\Services;

use App\Models\Module;
use App\Support\SchemaInspector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModuleService
{
    private const ENABLED_MAP_CACHE_KEY = 'modules:enabled_map:v1';
    private const MODULE_LIST_CACHE_KEY = 'modules:list_all:v1';
    private const BOOTSTRAP_CACHE_KEY = 'modules:bootstrap_defaults:v1';
    private const CACHE_TTL_SECONDS = 300;

    /**
     * @var array<string, bool>|null
     */
    private static ?array $enabledMap = null;

    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\Module>|null
     */
    private static ?Collection $moduleList = null;

    private static function ensureIslandTransferModule(): void
    {
        if (! SchemaInspector::hasTable('modules')) {
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
        if (! SchemaInspector::hasTable('modules')) {
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

    public static function flushCache(): void
    {
        self::$enabledMap = null;
        self::$moduleList = null;

        Cache::forget(self::ENABLED_MAP_CACHE_KEY);
        Cache::forget(self::MODULE_LIST_CACHE_KEY);
        Cache::forget(self::BOOTSTRAP_CACHE_KEY);
    }

    /**
     * @return array<string, bool>
     */
    public static function enabledMap(): array
    {
        if (self::$enabledMap !== null) {
            return self::$enabledMap;
        }

        if (! SchemaInspector::hasTable('modules')) {
            return self::$enabledMap = [];
        }

        self::ensureDefaultModulesBootstrapped();

        return self::$enabledMap = Cache::remember(
            self::ENABLED_MAP_CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn (): array => Module::query()
                ->pluck('is_enabled', 'key')
                ->map(fn ($enabled): bool => (bool) $enabled)
                ->all()
        );
    }

    public static function isEnabledStatic(string $key): bool
    {
        if (! SchemaInspector::hasTable('modules')) {
            return (bool) config('modules.fail_open', false);
        }

        $map = self::enabledMap();

        if (! array_key_exists($key, $map)) {
            return (bool) config('modules.fail_open', false);
        }

        return (bool) $map[$key];
    }

    public function listAll()
    {
        if (self::$moduleList !== null) {
            return self::$moduleList;
        }

        if (! SchemaInspector::hasTable('modules')) {
            return self::$moduleList = collect();
        }

        self::ensureDefaultModulesBootstrapped();

        return self::$moduleList = Cache::remember(
            self::MODULE_LIST_CACHE_KEY,
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            fn () => Module::query()->orderBy('name')->get()
        );
    }

    private static function ensureDefaultModulesBootstrapped(): void
    {
        if (! SchemaInspector::hasTable('modules')) {
            return;
        }

        Cache::remember(self::BOOTSTRAP_CACHE_KEY, now()->addSeconds(self::CACHE_TTL_SECONDS), function (): bool {
            self::ensureHotelModule();
            self::ensureIslandTransferModule();

            return true;
        });
    }
}
