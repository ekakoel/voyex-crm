<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_item_booking_logs')) {
            return;
        }

        Schema::table('booking_item_booking_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_item_booking_logs', 'confirmation_number')) {
                $table->string('confirmation_number')->nullable()->after('service_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_item_booking_logs')) {
            return;
        }

        Schema::table('booking_item_booking_logs', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_item_booking_logs', 'confirmation_number')) {
                $table->dropColumn('confirmation_number');
            }
        });
    }
};

