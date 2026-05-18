<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inquiries') || ! Schema::hasColumn('inquiries', 'assigned_to')) {
            return;
        }

        Schema::table('inquiries', function (Blueprint $table): void {
            try {
                $table->dropForeign(['assigned_to']);
            } catch (\Throwable $exception) {
                // Ignore when constraint name differs or does not exist.
            }

            $table->dropColumn('assigned_to');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inquiries') || Schema::hasColumn('inquiries', 'assigned_to')) {
            return;
        }

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->foreignId('assigned_to')->nullable()->after('priority')->constrained('users');
        });
    }
};
