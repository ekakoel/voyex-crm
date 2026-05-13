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
            if (! Schema::hasColumn('booking_item_vouchers', 'source_hash')) {
                $table->string('source_hash', 64)->nullable()->after('confirmation_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('booking_item_vouchers')) {
            return;
        }

        Schema::table('booking_item_vouchers', function (Blueprint $table): void {
            if (Schema::hasColumn('booking_item_vouchers', 'source_hash')) {
                $table->dropColumn('source_hash');
            }
        });
    }
};

