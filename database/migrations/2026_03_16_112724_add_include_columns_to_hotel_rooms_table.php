<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->longText('include')->nullable()->after('additional_info_simplified');
            $table->longText('include_traditional')->nullable()->after('include');
            $table->longText('include_simplified')->nullable()->after('include_traditional');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropColumn(['include', 'include_traditional', 'include_simplified']);
        });
    }
};
