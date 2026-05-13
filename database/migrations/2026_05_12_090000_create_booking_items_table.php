<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_items')) {
            return;
        }

        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->string('description');
            $table->integer('qty')->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->nullableMorphs('serviceable');
            $table->unsignedInteger('day_number')->nullable();
            $table->json('serviceable_meta')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'quotation_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};

