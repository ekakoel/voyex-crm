<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activities') || ! Schema::hasColumn('activities', 'currency')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('activities') || Schema::hasColumn('activities', 'currency')) {
            return;
        }

        Schema::table('activities', function (Blueprint $table) {
            $table->string('currency', 3)->default('IDR')->after('child_publish_rate');
        });
    }
};
