<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('activity_type', 100);
            $table->unsignedInteger('duration_minutes');
            $table->text('benefits')->nullable();
            $table->decimal('contract_price', 12, 2)->nullable();
            $table->decimal('agent_price', 12, 2)->nullable();
            $table->char('currency', 3)->default('IDR');
            $table->unsignedInteger('capacity_min')->nullable();
            $table->unsignedInteger('capacity_max')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('meeting_point')->nullable();
            $table->text('includes')->nullable();
            $table->text('excludes')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
