<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_beverages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('service_type', 100)->default('restaurant');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->decimal('contract_price', 12, 2)->nullable();
            $table->decimal('agent_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->string('meal_period', 50)->nullable();
            $table->text('menu_highlights')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_beverages');
    }
};
