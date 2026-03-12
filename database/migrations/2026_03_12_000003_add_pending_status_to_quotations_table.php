<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft','pending','sent','approved','rejected') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('quotations')) {
            return;
        }

        DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('draft','sent','approved','rejected') NOT NULL DEFAULT 'draft'");
    }
};
