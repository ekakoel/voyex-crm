<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permissionTable = config('permission.table_names.permissions', 'permissions');
        $roleTable = config('permission.table_names.roles', 'roles');
        $roleHasPermissions = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        $permissions = [
            'dashboard.administrator.view',
            'dashboard.manager.view',
            'dashboard.marketing.view',
            'dashboard.reservation.view',
            'dashboard.finance.view',
            'dashboard.director.view',
            'dashboard.editor.view',
        ];

        foreach ($permissions as $permissionName) {
            DB::table($permissionTable)->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissionIds = DB::table($permissionTable)
            ->whereIn('name', $permissions)
            ->pluck('id', 'name')
            ->all();

        $roleMap = [
            'Administrator' => ['dashboard.administrator.view'],
            'Manager' => ['dashboard.manager.view'],
            'Marketing' => ['dashboard.marketing.view'],
            'Reservation' => ['dashboard.reservation.view'],
            'Finance' => ['dashboard.finance.view'],
            'Director' => ['dashboard.director.view'],
            'Editor' => ['dashboard.editor.view'],
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $roleId = DB::table($roleTable)->where('name', $roleName)->value('id');
            if (! $roleId) {
                continue;
            }

            foreach ($rolePermissions as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;
                if (! $permissionId) {
                    continue;
                }

                $exists = DB::table($roleHasPermissions)
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table($roleHasPermissions)->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // No-op: permissions should remain once introduced.
    }
};
