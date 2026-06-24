<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table): void {
            if (! Schema::hasColumn('vendors', 'types')) {
                $table->json('types')
                    ->nullable()
                    ->after('type');
            }
        });

        DB::table('vendors')
            ->whereNull('types')
            ->whereNotNull('type')
            ->orderBy('id')
            ->select(['id', 'type'])
            ->chunkById(100, function ($vendors): void {
                foreach ($vendors as $vendor) {
                    $type = trim((string) $vendor->type);

                    if ($type === '') {
                        continue;
                    }

                    DB::table('vendors')
                        ->where('id', $vendor->id)
                        ->update(['types' => json_encode([$type])]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table): void {
            if (Schema::hasColumn('vendors', 'types')) {
                $table->dropColumn('types');
            }
        });
    }
};
