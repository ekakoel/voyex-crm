<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'activities',
            'food_beverages',
            'accommodations',
            'airports',
            'transports',
            'destinations',
            'tourist_attractions',
            'inquiries',
            'itineraries',
            'quotations',
            'customers',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'activities',
            'food_beverages',
            'accommodations',
            'airports',
            'transports',
            'destinations',
            'tourist_attractions',
            'inquiries',
            'itineraries',
            'quotations',
            'customers',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
