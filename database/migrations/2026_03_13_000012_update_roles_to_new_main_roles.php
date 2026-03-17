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

        $mergeRole = function (string $from, string $to) use ($roleTable, $modelHasRoles, $roleHasPermissions): void {
            $fromId = DB::table($roleTable)->where('name', $from)->value('id');
            if (! $fromId) {
                return;
            }

            $toId = DB::table($roleTable)->where('name', $to)->value('id');
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
                DB::table($roleTable)->where('id', $fromId)->update(['name' => $to]);
            }
        };

        $ensureRole = function (string $name) use ($roleTable): void {
            $exists = DB::table($roleTable)->where('name', $name)->exists();
            if (! $exists) {
                DB::table($roleTable)->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        };

        $mergeRole('Admin', 'Administrator');
        $mergeRole('Admin User', 'Administrator');
        $mergeRole('Sales Manager', 'Manager');
        $mergeRole('Sales Agent', 'Marketing');
        $mergeRole('Operations', 'Reservation');
        $mergeRole('Accountant', 'Finance');
        $ensureRole('Editor');
        $ensureRole('Finance');

        if (Schema::hasTable('feature_accesses')) {
            $roleMap = [
                'Admin' => 'Administrator',
                'Admin User' => 'Administrator',
                'Sales Manager' => 'Manager',
                'Sales Agent' => 'Marketing',
                'Operations' => 'Reservation',
                'Accountant' => 'Finance',
            ];

            foreach ($roleMap as $from => $to) {
                DB::table('feature_accesses')
                    ->where('roles', $from)
                    ->update(['roles' => $to]);
            }
        }
    }

    public function down(): void
    {
        // No-op: reverting merged roles safely requires domain decisions.
    }
};
