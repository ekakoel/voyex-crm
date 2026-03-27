<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        if (Schema::hasColumn('transports', 'vehicle_type')) {
            Schema::table('transports', function (Blueprint $table) {
                $table->dropColumn('vehicle_type');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transports')) {
            return;
        }

        if (! Schema::hasColumn('transports', 'vehicle_type')) {
            Schema::table('transports', function (Blueprint $table) {
                $table->string('vehicle_type')->nullable()->after('name');
            });
        }
    }
};

