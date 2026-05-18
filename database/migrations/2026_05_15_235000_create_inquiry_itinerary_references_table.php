<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inquiry_itinerary_references')) {
            return;
        }

        Schema::create('inquiry_itinerary_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('itinerary_id')->constrained('itineraries')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inquiry_id', 'itinerary_id'], 'inq_itinerary_ref_unique');
            $table->unique('itinerary_id', 'inq_itinerary_ref_itinerary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_itinerary_references');
    }
};

