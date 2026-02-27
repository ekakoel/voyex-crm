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
        Schema::create('transports', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('transport_type', 50);
            $table->string('provider_name')->nullable();
            $table->string('service_scope')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->text('inclusions')->nullable();
            $table->text('exclusions')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('notes')->nullable();
            $table->json('gallery_images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transports');
    }
};

