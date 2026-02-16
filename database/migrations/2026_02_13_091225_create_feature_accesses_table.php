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
        Schema::create('feature_accesses', function (Blueprint $table) {
            $table->id();
            $title->string('title');
            $title->string('route');
            $title->string('icon');
            $title->string('module');
            $title->string('roles');
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('feature_accesses')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_accesses');
    }
};
