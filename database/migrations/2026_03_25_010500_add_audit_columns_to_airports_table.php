<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('airports')) {
            return;
        }

        $hasCreatedBy = Schema::hasColumn('airports', 'created_by');
        $hasUpdatedBy = Schema::hasColumn('airports', 'updated_by');

        if (! $hasCreatedBy || ! $hasUpdatedBy) {
            Schema::table('airports', function (Blueprint $table) use ($hasCreatedBy, $hasUpdatedBy) {
                if (! $hasCreatedBy) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! $hasUpdatedBy) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        $fallbackUserId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 0);
        if ($fallbackUserId <= 0) {
            return;
        }

        DB::table('airports')->whereNull('created_by')->update(['created_by' => $fallbackUserId]);
        DB::table('airports')->whereNull('updated_by')->update(['updated_by' => $fallbackUserId]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('airports')) {
            return;
        }

        if (Schema::hasColumn('airports', 'updated_by')) {
            Schema::table('airports', function (Blueprint $table) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasColumn('airports', 'created_by')) {
            Schema::table('airports', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};

