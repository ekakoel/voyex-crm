<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('booking_item_vouchers')) {
            return;
        }

        Schema::table('booking_item_vouchers', function (Blueprint $table): void {
            if (! Schema::hasColumn('booking_item_vouchers', 'tour_name')) {
                $table->string('tour_name')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_item_vouchers')) {
            return;
        }

        Schema::table('booking_item_vouchers', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_item_vouchers', 'tour_name')) {
                $table->dropColumn('tour_name');
            }
        });
    }
};

