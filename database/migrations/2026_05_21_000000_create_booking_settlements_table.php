<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('settlement_number')->unique();
            $table->string('status', 32)->default('pending_review');

            $table->boolean('service_completed_check')->default(false);
            $table->boolean('invoice_check')->default(false);
            $table->boolean('payment_check')->default(false);
            $table->boolean('adjustment_check')->default(false);
            $table->boolean('overpayment_check')->default(false);

            $table->decimal('total_invoice_amount', 15, 2)->default(0);
            $table->decimal('total_paid_amount', 15, 2)->default(0);
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->decimal('overpaid_amount', 15, 2)->default(0);
            $table->text('settlement_notes')->nullable();

            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('booking_id');
            $table->index('status');
            $table->index('reviewed_at');
            $table->index('finalized_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_settlements');
    }
};
