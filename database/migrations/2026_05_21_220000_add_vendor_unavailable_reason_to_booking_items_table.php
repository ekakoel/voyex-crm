<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        Schema::table('booking_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_items', 'vendor_unavailable_reason')) {
                $table->text('vendor_unavailable_reason')
                    ->nullable()
                    ->after('vendor_confirmed_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_items')) {
            return;
        }

        Schema::table('booking_items', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_items', 'vendor_unavailable_reason')) {
                $table->dropColumn('vendor_unavailable_reason');
            }
        });
    }
};

