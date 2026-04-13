<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('currencies')) {
            return;
        }

        DB::table('currencies')
            ->whereRaw('UPPER(code) = ?', ['USD'])
            ->update(['decimal_places' => 0]);
    }

    public function down(): void
    {
        // Keep USD decimal places at zero as system standard.
    }
};

