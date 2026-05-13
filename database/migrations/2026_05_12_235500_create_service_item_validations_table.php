<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_item_validations')) {
            return;
        }

        Schema::create('service_item_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->string('serviceable_type', 120);
            $table->unsignedBigInteger('serviceable_id');
            $table->foreignId('validator_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 50)->default('save_item');
            $table->boolean('is_validated')->default(false);
            $table->text('validation_notes')->nullable();

            $table->decimal('old_contract_rate', 15, 2)->nullable();
            $table->decimal('new_contract_rate', 15, 2)->nullable();
            $table->string('old_markup_type', 20)->nullable();
            $table->string('new_markup_type', 20)->nullable();
            $table->decimal('old_markup', 15, 2)->nullable();
            $table->decimal('new_markup', 15, 2)->nullable();
            $table->unsignedInteger('old_qty')->nullable();
            $table->unsignedInteger('new_qty')->nullable();

            $table->json('source_rate_snapshot')->nullable();

            $table->timestamps();

            $table->index(['serviceable_type', 'serviceable_id'], 'service_item_validations_service_idx');
            $table->index(['quotation_id', 'quotation_item_id'], 'service_item_validations_quotation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_item_validations');
    }
};

