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
        Schema::create('transport_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transport_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('vehicle_type')->nullable();
            $table->string('brand_model')->nullable();
            $table->unsignedInteger('seat_capacity')->default(4);
            $table->unsignedInteger('luggage_capacity')->nullable();
            $table->decimal('contract_rate', 15, 2);
            $table->decimal('publish_rate', 15, 2)->nullable();
            $table->decimal('overtime_rate', 15, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->boolean('air_conditioned')->default(true);
            $table->boolean('with_driver')->default(true);
            $table->text('benefits')->nullable();
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
        Schema::dropIfExists('transport_units');
    }
};

