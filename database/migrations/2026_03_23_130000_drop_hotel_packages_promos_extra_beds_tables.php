<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hotel_packages');
        Schema::dropIfExists('hotel_promos');
        Schema::dropIfExists('extra_beds');
    }

    public function down(): void
    {
        // Tables removed permanently as part of module cleanup.
    }
};
