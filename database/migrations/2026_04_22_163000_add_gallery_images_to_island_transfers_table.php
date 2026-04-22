<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('island_transfers')) {
            return;
        }

        Schema::table('island_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('island_transfers', 'gallery_images')) {
                $table->json('gallery_images')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('island_transfers')) {
            return;
        }

        Schema::table('island_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('island_transfers', 'gallery_images')) {
                $table->dropColumn('gallery_images');
            }
        });
    }
};

