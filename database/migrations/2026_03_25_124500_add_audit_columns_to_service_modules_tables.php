<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'destinations',
            'tourist_attractions',
            'transports',
            'food_beverages',
            'hotels',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        $fallbackUserId = $this->resolveFallbackUserId();
        if (! $fallbackUserId) {
            return;
        }

        if (Schema::hasTable('hotels') && Schema::hasColumn('hotels', 'author_id') && Schema::hasColumn('hotels', 'created_by')) {
            DB::table('hotels')
                ->whereNull('created_by')
                ->whereNotNull('author_id')
                ->whereIn('author_id', DB::table('users')->select('id'))
                ->update(['created_by' => DB::raw('author_id')]);
        }

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'created_by')) {
                DB::table($tableName)->whereNull('created_by')->update(['created_by' => $fallbackUserId]);
            }

            if (Schema::hasColumn($tableName, 'updated_by')) {
                DB::table($tableName)->whereNull('updated_by')->update(['updated_by' => $fallbackUserId]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'hotels',
            'food_beverages',
            'transports',
            'tourist_attractions',
            'destinations',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (Schema::hasColumn($tableName, 'updated_by')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropForeign(['updated_by']);
                    $table->dropColumn('updated_by');
                });
            }

            if (Schema::hasColumn($tableName, 'created_by')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropForeign(['created_by']);
                    $table->dropColumn('created_by');
                });
            }
        }
    }

    private function resolveFallbackUserId(): ?int
    {
        $userId = DB::table('users')->orderBy('id')->value('id');

        return $userId ? (int) $userId : null;
    }
};
