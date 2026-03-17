<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotels_id')->constrained('hotels')->onDelete('cascade');
            $table->foreignId('rooms_id')->constrained('hotel_rooms')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('markup')->nullable();
            $table->integer('kick_back')->nullable();
            $table->integer('contract_rate');
            $table->integer('author');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_prices');
    }
};
