<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_item_vouchers')) {
            return;
        }

        Schema::create('booking_item_vouchers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_item_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_number')->unique();
            $table->enum('status', ['draft', 'issued', 'used', 'cancelled'])->default('draft');
            $table->date('service_date')->nullable();
            $table->string('service_time', 20)->nullable();
            $table->string('vendor_contact_name')->nullable();
            $table->string('vendor_contact_phone')->nullable();
            $table->string('vendor_contact_email')->nullable();
            $table->string('pickup_location')->nullable();
            $table->text('notes')->nullable();
            $table->string('confirmation_code')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_vouchers');
    }
};

