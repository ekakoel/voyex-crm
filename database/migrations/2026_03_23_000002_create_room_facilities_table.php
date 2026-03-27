<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('room_facilities')) {
            return;
        }

        Schema::create('room_facilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->foreignId('rooms_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->integer('wifi');
            $table->integer('single_bed');
            $table->integer('double_bed');
            $table->integer('extra_bed');
            $table->integer('air_conditioning');
            $table->integer('pool');
            $table->integer('tv_channel');
            $table->integer('water_heater');
            $table->integer('bathtub');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_facilities');
    }
};
