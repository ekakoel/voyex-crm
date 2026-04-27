<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('airports')) {
            return;
        }

        if (! Schema::hasColumn('airports', 'cover')) {
            Schema::table('airports', function (Blueprint $table) {
                $table->string('cover')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('airports') || ! Schema::hasColumn('airports', 'cover')) {
            return;
        }

        Schema::table('airports', function (Blueprint $table) {
            $table->dropColumn('cover');
        });
    }
};

