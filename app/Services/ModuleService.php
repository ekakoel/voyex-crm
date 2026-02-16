<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Support\Facades\Schema;

class ModuleService
{
    public function isEnabled(string $key): bool
    {
        return self::isEnabledStatic($key);
    }

    public static function isEnabledStatic(string $key): bool
    {
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
        if (! Schema::hasTable('modules')) {
            return collect();
        }

        return Module::query()->orderBy('name')->get();
    }
}
