<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hotels') || ! Schema::hasColumn('hotels', 'map')) {
            return;
        }

        DB::statement('ALTER TABLE hotels MODIFY map TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('hotels') || ! Schema::hasColumn('hotels', 'map')) {
            return;
        }

        DB::statement('ALTER TABLE hotels MODIFY map VARCHAR(255) NULL');
    }
};
