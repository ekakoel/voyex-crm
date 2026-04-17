<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionTable = config('permission.table_names.permissions', 'permissions');

        $permissions = [
            'services.map.view',
            'superadmin.access_matrix.view',
        ];

        foreach ($permissions as $permissionName) {
            DB::table($permissionTable)->updateOrInsert(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionTable = config('permission.table_names.permissions', 'permissions');

        DB::table($permissionTable)
            ->whereIn('name', ['services.map.view', 'superadmin.access_matrix.view'])
            ->delete();
    }
};

