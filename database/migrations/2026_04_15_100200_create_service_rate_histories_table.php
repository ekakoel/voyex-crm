<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_rate_histories')) {
            return;
        }

        Schema::create('service_rate_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();

            $table->string('serviceable_type', 120);
            $table->unsignedBigInteger('serviceable_id');

            $table->decimal('contract_rate', 15, 2)->nullable();
            $table->string('markup_type', 20)->nullable();
            $table->decimal('markup', 15, 2)->nullable();
            $table->decimal('publish_rate', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['serviceable_type', 'serviceable_id', 'created_at'], 'service_rate_histories_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_rate_histories');
    }
};