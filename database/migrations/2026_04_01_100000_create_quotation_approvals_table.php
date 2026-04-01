<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotation_approvals')) {
            return;
        }

        Schema::create('quotation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('approval_role', ['manager', 'director', 'reservation']);
            $table->text('note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['quotation_id', 'user_id'], 'quotation_approvals_quote_user_unique');
            $table->index(['quotation_id', 'approval_role'], 'quotation_approvals_quote_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_approvals');
    }
};

