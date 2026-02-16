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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_number')->unique();
            $table->foreignId('customer_id')->constrained();
            $table->string('source')->nullable();
            $table->enum('status', ['new','follow_up','quoted','converted','closed'])->default('new');
            $table->string('priority')->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('reminder_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
