<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roleTable = config('permission.table_names.roles', 'roles');
        $modelHasRoles = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roleHasPermissions = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        $fromId = DB::table($roleTable)->where('name', 'Accountant')->value('id');
        if (! $fromId) {
            return;
        }

        $toId = DB::table($roleTable)->where('name', 'Finance')->value('id');
        if ($toId) {
            if (Schema::hasTable($modelHasRoles)) {
                DB::table($modelHasRoles)
                    ->where('role_id', $fromId)
                    ->update(['role_id' => $toId]);
            }

            if (Schema::hasTable($roleHasPermissions)) {
                $existingPermissions = DB::table($roleHasPermissions)
                    ->where('role_id', $toId)
                    ->pluck('permission_id')
                    ->all();

                if ($existingPermissions !== []) {
                    DB::table($roleHasPermissions)
                        ->where('role_id', $fromId)
                        ->whereIn('permission_id', $existingPermissions)
                        ->delete();
                }

                DB::table($roleHasPermissions)
                    ->where('role_id', $fromId)
                    ->update(['role_id' => $toId]);
            }

            DB::table($roleTable)->where('id', $fromId)->delete();
        } else {
            DB::table($roleTable)->where('id', $fromId)->update(['name' => 'Finance']);
        }

        if (Schema::hasTable('feature_accesses')) {
            DB::table('feature_accesses')
                ->where('roles', 'Accountant')
                ->update(['roles' => 'Finance']);
        }
    }

    public function down(): void
    {
        // No-op: reverting role rename is not automated.
    }
};
