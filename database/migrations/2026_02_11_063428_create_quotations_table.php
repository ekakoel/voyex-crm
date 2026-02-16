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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft','sent','approved','rejected'])->default('draft');
            $table->date('validity_date');
            $table->foreignId('template_id')->nullable()->constrained('quotation_templates')->nullOnDelete();
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->string('promo_code')->nullable();
            $table->decimal('promo_discount', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2)->default(0);
            $table->enum('approval_status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
