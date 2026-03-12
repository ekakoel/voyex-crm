<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $createdByTables = [
            'inquiries',
            'quotations',
            'bookings',
            'invoices',
            'vendors',
        ];

        $updatedByTables = [
            'customers',
            'inquiries',
            'itineraries',
            'quotations',
            'bookings',
            'invoices',
            'vendors',
        ];

        foreach ($createdByTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $hasCreatedBy = Schema::hasColumn($tableName, 'created_by');
            if (! $hasCreatedBy) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                });
            }
        }

        foreach ($updatedByTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $hasUpdatedBy = Schema::hasColumn($tableName, 'updated_by');
            if (! $hasUpdatedBy) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                });
            }
        }

        $superAdminId = $this->resolveSuperAdminId() ?? $this->resolveFallbackUserId();
        if (! $superAdminId) {
            return;
        }

        $allTables = array_values(array_unique(array_merge($createdByTables, $updatedByTables)));
        foreach ($allTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'created_by')) {
                DB::table($tableName)->whereNull('created_by')->update(['created_by' => $superAdminId]);
            }

            if (Schema::hasColumn($tableName, 'updated_by')) {
                DB::table($tableName)->whereNull('updated_by')->update(['updated_by' => $superAdminId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $createdByTables = [
            'inquiries',
            'quotations',
            'bookings',
            'invoices',
            'vendors',
        ];

        $updatedByTables = [
            'customers',
            'inquiries',
            'itineraries',
            'quotations',
            'bookings',
            'invoices',
            'vendors',
        ];

        foreach ($updatedByTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'updated_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            });
        }

        foreach ($createdByTables as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'created_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }

    private function resolveSuperAdminId(): ?int
    {
        $roleTable = config('permission.table_names.roles', 'roles');
        $modelRoleTable = config('permission.table_names.model_has_roles', 'model_has_roles');

        $roleId = DB::table($roleTable)->where('name', 'Super Admin')->value('id');
        if (! $roleId) {
            return null;
        }

        $modelType = config('permission.models.user', \App\Models\User::class);

        $userId = DB::table($modelRoleTable)
            ->where('role_id', $roleId)
            ->where('model_type', $modelType)
            ->value('model_id');

        return $userId ? (int) $userId : null;
    }

    private function resolveFallbackUserId(): ?int
    {
        $userId = DB::table('users')->orderBy('id')->value('id');

        return $userId ? (int) $userId : null;
    }
};
