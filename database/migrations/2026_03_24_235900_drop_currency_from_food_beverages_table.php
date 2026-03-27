<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('food_beverages', 'currency')) {
            Schema::table('food_beverages', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('food_beverages', 'currency')) {
            Schema::table('food_beverages', function (Blueprint $table) {
                $table->char('currency', 3)->default('IDR')->after('agent_price');
            });
        }
    }
};

