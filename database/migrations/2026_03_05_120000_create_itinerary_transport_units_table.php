<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_transport_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transport_unit_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_number')->default(1);
            $table->timestamps();

            $table->unique(['itinerary_id', 'day_number'], 'itinerary_transport_units_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_transport_units');
    }
};
