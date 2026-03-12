<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('activity_type_id')
                ->nullable()
                ->after('activity_type')
                ->constrained('activity_types')
                ->nullOnDelete();
        });

        $types = DB::table('activity_types')
            ->select('id', 'name')
            ->pluck('id', 'name');

        DB::table('activities')
            ->select('id', 'activity_type')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($types) {
                foreach ($rows as $row) {
                    $name = trim((string) ($row->activity_type ?? ''));
                    if ($name === '') {
                        continue;
                    }
                    $typeId = $types[$name] ?? null;
                    if (! $typeId) {
                        continue;
                    }
                    DB::table('activities')
                        ->where('id', $row->id)
                        ->update(['activity_type_id' => (int) $typeId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_type_id');
        });
    }
};

