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

        // Skip on fresh-empty database. Permission baseline is handled by seeders.
        if (DB::table($roleTable)->count() === 0) {
            return;
        }

        $permissions = [
            'quotations.validate',
            'quotations.set_pending',
            'quotations.global_discount',
        ];

        foreach ($permissions as $permissionName) {
            DB::table($permissionTable)->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $permissionIds = DB::table($permissionTable)
            ->whereIn('name', $permissions)
            ->pluck('id', 'name');

        $rolePermissions = [
            'Super Admin' => $permissions,
            'Administrator' => $permissions,
            'Director' => $permissions,
            'Reservation' => ['quotations.validate'],
            'Manager' => ['quotations.validate', 'quotations.global_discount'],
        ];

        $roles = DB::table($roleTable)
            ->whereIn('name', array_keys($rolePermissions))
            ->pluck('id', 'name');

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = $roles[$roleName] ?? null;
            if (! $roleId) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
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
        // No-op: keep permissions once introduced to avoid breaking live access matrices.
    }
};
