<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inquiries') || Schema::hasColumn('inquiries', 'handled_by')) {
            return;
        }

        Schema::table('inquiries', function (Blueprint $table) {
            $table->foreignId('handled_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('handled_by', 'inquiries_handled_by_index');
        });

        if (Schema::hasColumn('inquiries', 'assigned_to')) {
            DB::table('inquiries')
                ->whereNull('handled_by')
                ->whereNotNull('assigned_to')
                ->update(['handled_by' => DB::raw('assigned_to')]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inquiries') || ! Schema::hasColumn('inquiries', 'handled_by')) {
            return;
        }

        Schema::table('inquiries', function (Blueprint $table) {
            try {
                $table->dropForeign(['handled_by']);
            } catch (\Throwable $exception) {
            }

            try {
                $table->dropIndex('inquiries_handled_by_index');
            } catch (\Throwable $exception) {
            }

            $table->dropColumn('handled_by');
        });
    }
};

