<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accommodation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('room_type')->nullable();
            $table->string('bed_type')->nullable();
            $table->string('view_type')->nullable();
            $table->unsignedInteger('max_occupancy')->default(2);
            $table->decimal('room_size_sqm', 8, 2)->nullable();
            $table->decimal('contract_rate', 15, 2);
            $table->decimal('publish_rate', 15, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('meal_plan')->nullable();
            $table->text('amenities')->nullable();
            $table->text('benefits')->nullable();
            $table->boolean('is_refundable')->default(false);
            $table->unsignedInteger('quantity_available')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_rooms');
    }
};

