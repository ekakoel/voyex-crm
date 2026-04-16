<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotation_item_validations')) {
            return;
        }

        Schema::create('quotation_item_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->foreignId('validator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 50)->default('save_progress');
            $table->boolean('is_validated')->default(false);
            $table->text('validation_notes')->nullable();

            $table->decimal('old_contract_rate', 15, 2)->nullable();
            $table->decimal('new_contract_rate', 15, 2)->nullable();
            $table->string('old_markup_type', 20)->nullable();
            $table->string('new_markup_type', 20)->nullable();
            $table->decimal('old_markup', 15, 2)->nullable();
            $table->decimal('new_markup', 15, 2)->nullable();

            $table->string('source_rate_type', 120)->nullable();
            $table->unsignedBigInteger('source_rate_id')->nullable();
            $table->json('source_rate_snapshot')->nullable();

            $table->timestamps();

            $table->index(['quotation_id', 'quotation_item_id'], 'quotation_item_validations_item_idx');
            $table->index(['source_rate_type', 'source_rate_id'], 'quotation_item_validations_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_item_validations');
    }
};
