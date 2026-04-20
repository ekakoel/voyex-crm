<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('island_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('transfer_type', 50)->default('fastboat');
            $table->string('departure_point_name', 150)->nullable();
            $table->decimal('departure_latitude', 10, 7)->nullable();
            $table->decimal('departure_longitude', 10, 7)->nullable();
            $table->string('arrival_point_name', 150)->nullable();
            $table->decimal('arrival_latitude', 10, 7)->nullable();
            $table->decimal('arrival_longitude', 10, 7)->nullable();
            $table->json('route_geojson')->nullable();
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('capacity_min')->nullable();
            $table->unsignedInteger('capacity_max')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('island_transfers');
    }
};

