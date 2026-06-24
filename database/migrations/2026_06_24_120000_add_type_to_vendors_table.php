<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table): void {
            if (! Schema::hasColumn('vendors', 'type')) {
                $table->string('type', 50)
                    ->nullable()
                    ->after('name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendors')) {
            return;
        }

        Schema::table('vendors', function (Blueprint $table): void {
            if (Schema::hasColumn('vendors', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
