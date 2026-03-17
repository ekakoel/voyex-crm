<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inquiries')) {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM inquiries WHERE Column_name = 'inquiry_number'");
        $hasUnique = collect($indexes)->contains(function ($index) {
            return (int) ($index->Non_unique ?? 1) === 0;
        });

        if (! $hasUnique) {
            Schema::table('inquiries', function (Blueprint $table) {
                $table->unique('inquiry_number');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inquiries')) {
            return;
        }

        $indexes = DB::select("SHOW INDEX FROM inquiries WHERE Column_name = 'inquiry_number'");
        $hasUnique = collect($indexes)->contains(function ($index) {
            return (int) ($index->Non_unique ?? 1) === 0;
        });

        if ($hasUnique) {
            Schema::table('inquiries', function (Blueprint $table) {
                $table->dropUnique(['inquiry_number']);
            });
        }
    }
};
