<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_cancellation_policies')) {
            Schema::create('service_cancellation_policies', function (Blueprint $table): void {
                $table->id();
                $table->string('serviceable_type')->nullable();
                $table->unsignedBigInteger('serviceable_id')->nullable();
                $table->string('name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['serviceable_type', 'serviceable_id', 'is_active'], 'svc_cancel_policy_lookup_idx');
            });
        }

        if (! Schema::hasTable('service_cancellation_policy_rules')) {
            Schema::create('service_cancellation_policy_rules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('policy_id')->constrained('service_cancellation_policies')->cascadeOnDelete();
                $table->unsignedInteger('min_days_before')->nullable();
                $table->unsignedInteger('max_days_before')->nullable();
                $table->enum('fee_type', ['fixed', 'percent'])->default('fixed');
                $table->decimal('fee_value', 18, 2)->default(0);
                $table->string('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['policy_id', 'sort_order'], 'svc_cancel_policy_rules_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_cancellation_policy_rules');
        Schema::dropIfExists('service_cancellation_policies');
    }
};
