<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['city', 'province', 'meeting_point']);
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('capacity_max');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('meeting_point')->nullable()->after('province');
        });
    }
};
