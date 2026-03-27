<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropForeign(['hotels_id']);
        });

        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('hotels_id')->nullable()->change();
            $table->index('hotels_id');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->dropIndex(['hotels_id']);
        });

        Schema::table('hotel_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('hotels_id')->nullable()->change();
            $table->foreign('hotels_id')
                ->references('id')
                ->on('hotels')
                ->nullOnDelete();
        });
    }
};
