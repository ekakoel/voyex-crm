<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasTable('permissions')) {
            return;
        }

        $permissionTable = config('permission.table_names.permissions', 'permissions');
        $roleTable = config('permission.table_names.roles', 'roles');
        $roleHasPermissions = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        $modules = DB::table('modules')->select(['key'])->get();
        $permissionIds = [];

        foreach ($modules as $module) {
            $moduleKey = $module->key;
            $permissionNames = [
                "module.{$moduleKey}.access",
                "module.{$moduleKey}.create",
                "module.{$moduleKey}.read",
                "module.{$moduleKey}.update",
                "module.{$moduleKey}.delete",
            ];

            foreach ($permissionNames as $permissionName) {
                DB::table($permissionTable)->updateOrInsert(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        $permissionIds = DB::table($permissionTable)
            ->whereIn('name', $modules->flatMap(function ($module) {
                $moduleKey = $module->key;
                return [
                    "module.{$moduleKey}.access",
                    "module.{$moduleKey}.create",
                    "module.{$moduleKey}.read",
                    "module.{$moduleKey}.update",
                    "module.{$moduleKey}.delete",
                ];
            })->all())
            ->pluck('id', 'name')
            ->all();

        $roles = DB::table($roleTable)->select(['id'])->get();

        foreach ($roles as $role) {
            $rolePermissionIds = DB::table($roleHasPermissions)
                ->where('role_id', $role->id)
                ->pluck('permission_id')
                ->all();

            if ($rolePermissionIds === []) {
                continue;
            }

            $rolePermissionsByName = DB::table($permissionTable)
                ->whereIn('id', $rolePermissionIds)
                ->pluck('name')
                ->all();

            $permissionSet = array_fill_keys($rolePermissionsByName, true);

            foreach ($modules as $module) {
                $moduleKey = $module->key;
                $accessPermission = "module.{$moduleKey}.access";
                if (! isset($permissionSet[$accessPermission])) {
                    continue;
                }

                foreach (['create', 'read', 'update', 'delete'] as $action) {
                    $permissionName = "module.{$moduleKey}.{$action}";
                    $permissionId = $permissionIds[$permissionName] ?? null;
                    if (! $permissionId) {
                        continue;
                    }

                    $exists = DB::table($roleHasPermissions)
                        ->where('role_id', $role->id)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (! $exists) {
                        DB::table($roleHasPermissions)->insert([
                            'role_id' => $role->id,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: permissions stay once introduced.
    }
};
