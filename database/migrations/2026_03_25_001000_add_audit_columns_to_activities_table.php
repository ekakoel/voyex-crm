<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        $hasCreatedBy = Schema::hasColumn('activities', 'created_by');
        $hasUpdatedBy = Schema::hasColumn('activities', 'updated_by');

        if (! $hasCreatedBy || ! $hasUpdatedBy) {
            Schema::table('activities', function (Blueprint $table) use ($hasCreatedBy, $hasUpdatedBy) {
                if (! $hasCreatedBy) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! $hasUpdatedBy) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        $fallbackUserId = DB::table('users')->orderBy('id')->value('id');
        if (! $fallbackUserId) {
            return;
        }

        DB::table('activities')->whereNull('created_by')->update(['created_by' => $fallbackUserId]);
        DB::table('activities')->whereNull('updated_by')->update(['updated_by' => $fallbackUserId]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('activities')) {
            return;
        }

        if (Schema::hasColumn('activities', 'updated_by')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            });
        }

        if (Schema::hasColumn('activities', 'created_by')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
