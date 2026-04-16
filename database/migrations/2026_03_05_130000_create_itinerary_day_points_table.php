<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itinerary_day_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('day_number');
            $table->string('start_point_type', 30)->nullable();
            $table->foreignId('start_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->string('end_point_type', 30)->nullable();
            $table->foreignId('end_airport_id')->nullable()->constrained('airports')->nullOnDelete();
            $table->timestamps();

            $table->unique(['itinerary_id', 'day_number'], 'itinerary_day_points_day_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_day_points');
    }
};
