<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_tourist_attraction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tourist_attraction_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['itinerary_id', 'tourist_attraction_id'], 'itinerary_tourist_attraction_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_tourist_attraction');
    }
};
