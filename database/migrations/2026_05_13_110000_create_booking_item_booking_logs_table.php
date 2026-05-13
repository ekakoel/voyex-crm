<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_item_booking_logs')) {
            return;
        }

        Schema::create('booking_item_booking_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_item_id')->constrained('booking_items')->cascadeOnDelete();
            $table->dateTime('booked_at');
            $table->string('vendor_provider_item_name');
            $table->string('contact_channel', 50);
            $table->string('contact_value')->nullable();
            $table->string('contacted_person_name');
            $table->date('service_date')->nullable();
            $table->unsignedInteger('pax_adult')->default(0);
            $table->unsignedInteger('pax_child')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['booking_item_id', 'booked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_booking_logs');
    }
};

